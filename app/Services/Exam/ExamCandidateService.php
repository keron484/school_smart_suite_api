<?php

namespace App\Services\Exam;

use App\Exceptions\AppException;
use App\Models\Exams;
use App\Models\Exam\ExamCandidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Throwable;
class ExamCandidateService
{
    public function getAccessedStudents(object $currentSchool)
    {
        try {
            $getExamCandidates = ExamCandidate::where("school_branch_id", $currentSchool->id)
                ->with([
                    'student',
                    'exam.schoolYear.specialty.level',
                    'exam.examType.semesters',
                    'exam.schoolYear.systemAcademicYear',
                    'exam.examScore'
                ])
                ->get();

            if ($getExamCandidates->isEmpty()) {
                throw new AppException(
                    "No exam candidates found for this school branch.",
                    404,
                    "No Candidates Found",
                    "There are no exam candidates available. Candidates are automatically created when you create an exam.",
                    "/exams"
                );
            }

            return $getExamCandidates;
        } catch (AppException $e) {
            throw $e;
        } catch (Throwable $e) {
            // Log exact exception details (message, file, line, and stack trace)
            Log::error("Failed to fetch accessed students: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'school_branch_id' => $currentSchool->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            // Option A: Append raw error to description (Ideal for local/debugging)
            $debugDescription = config('app.debug')
                ? "Error: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}"
                : "We encountered an unexpected issue while retrieving the list of exam candidates.";

            throw new AppException(
                "An unexpected error occurred while fetching exam candidates. Please try again later.",
                500,
                "Server Error",
                $debugDescription,
                null
            );
        }
    }

    public function deleteAccessedStudent(string $candidateId, object $currentSchool)
    {
        try {
            DB::beginTransaction();
            $deleteAccessedStudent = ExamCandidate::where("school_branch_id", $currentSchool->id)->findOrFail($candidateId);
            $exam = Exams::findOrFail($deleteAccessedStudent->exam_id);

            $deleteAccessedStudent->delete();
            $exam->decrement('expected_candidate_number');

            DB::commit();

            return true;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new AppException(
                "The student record you are trying to delete was not found.",
                404,
                "Record Not Found",
                "The student or associated exam record does not exist. It may have already been deleted.",
                "/accessed-students"
            );
        } catch (\Exception $e) {
            DB::rollBack();
            throw new AppException(
                "An unexpected error occurred. Please try again later.",
                500,
                "Server Error",
                "We were unable to complete the deletion due to a server error.",
                "/accessed-students"
            );
        }
    }
}
