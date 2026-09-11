<?php

namespace App\Jobs\Exam;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Exams;
use App\Models\Student;
use App\Models\Exam\ExamCandidate;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CreateExamCandidateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $maxExceptions = 3;

    public $backoff = [10, 30, 60];

    public $timeout = 300;

    public $failOnTimeout = true;

    public $deleteWhenMissingModels = true;

    protected string $examId;
    protected string $schoolBranchId;

    public function __construct(string $examId, string $schoolBranchId)
    {
        $this->examId = $examId;
        $this->schoolBranchId = $schoolBranchId;
    }

    public function handle(): void
    {
        $schoolBranchId = $this->schoolBranchId;
        $examId = $this->examId;

        DB::beginTransaction();

        try {
            $exam = Exams::with(['schoolYear.specialty'])
                ->where("school_branch_id", $schoolBranchId)
                ->findOrFail($examId);

            $students = Student::where("school_branch_id", $schoolBranchId)
                ->where("specialty_id", $exam->schoolYear->specialty->id)
                ->get();

            if ($students->isEmpty()) {
                Log::warning('No students found for exam creation', [
                    'exam_id' => $examId,
                    'school_branch_id' => $schoolBranchId,
                    'specialty_id' => $exam->schoolYear->specialty->id,
                    'level_id' => $exam->schoolYear->specialty->level_id
                ]);
                DB::commit();
                return;
            }

            $existingStudentIds = ExamCandidate::where('exam_id', $examId)
                ->where('school_branch_id', $schoolBranchId)
                ->pluck('student_id')
                ->toArray();

            $candidates = [];
            $duplicateCount = 0;

            foreach ($students as $student) {
                if (in_array($student->id, $existingStudentIds)) {
                    $duplicateCount++;
                    continue;
                }

                $candidates[] = [
                    'id' => Str::uuid()->toString(),
                    'school_branch_id' => $schoolBranchId,
                    'student_id' => $student->id,
                    'exam_id' => $examId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($candidates)) {
                $chunks = array_chunk($candidates, 100);
                foreach ($chunks as $chunk) {
                    DB::table('exam_candidates')->insert($chunk);
                }

                Log::info('Exam candidates created successfully', [
                    'exam_id' => $examId,
                    'total_students' => $students->count(),
                    'created' => count($candidates),
                    'duplicates_skipped' => $duplicateCount,
                    'school_branch_id' => $schoolBranchId
                ]);
            } else {
                Log::warning('No new exam candidates to create', [
                    'exam_id' => $examId,
                    'total_students' => $students->count(),
                    'duplicates_skipped' => $duplicateCount
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create exam candidates', [
                'exam_id' => $examId,
                'school_branch_id' => $schoolBranchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CreateExamCandidateJob failed after all retries', [
            'exam_id' => $this->examId,
            'school_branch_id' => $this->schoolBranchId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
