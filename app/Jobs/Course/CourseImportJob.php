<?php

namespace App\Jobs\Course;

use App\Events\Job\JobEvent;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobError;
use App\Models\Schoolbranches;
use App\Models\Schooladmin;
use App\Models\Courses;
use App\Models\Semester;
use App\Models\Course\CourseType;
use App\Services\Helpers\Job\JobHelperService;
use App\Services\Helpers\Job\JobProgressReporterService;
use App\Services\Helpers\Import\SpreadSheetReadException;
use App\Services\Helpers\Import\SpreadSheetReaderService;
use App\Services\Job\JobBroadCastPolicyService;
use App\Models\Job\SystemJobDetail;
use App\Models\Specialty;
use App\Services\Helpers\Import\ColumnIndexResolverService;
use App\Services\Helpers\Import\ImportMapRowService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CourseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected string $completedStatus = 'completed';
    protected string $failedStatus = 'failed';
    protected string $inProgressStatus = 'processing';
    protected string $completedWithIssuesStatus = 'completed_with_issues';

    protected string $adminId;
    protected string $schoolBranchId;
    protected string $categoryId;
    protected string $jobId;
    public array $requiredFields = ['course_code', 'course_credit', 'course_title', 'description', 'semester', 'level', 'specialty', "course_types"];
    public array $optionalFields = [];

    private JobProgressReporterService $progressReporter;
    private JobBroadCastPolicyService $policy;
    private JobHelperService $jobHelperService;
    private SpreadSheetReaderService $spreadsheetService;
    private ImportMapRowService $importMapRowService;

    private int $lastBroadcastProgress = 0;
    private ?string $lastBroadcastStatus = null;
    private ?Carbon $lastBroadcastAt = null;

    private int $batchProcessed = 0;
    private int $batchCreated = 0;
    private int $batchUpdated = 0;
    private int $batchSkipped = 0;
    private int $batchSize = 50;

    public function __construct(
        string $adminId,
        string $schoolBranchId,
        string $categoryId,
        string $jobId
    ) {
        $this->adminId = $adminId;
        $this->schoolBranchId = $schoolBranchId;
        $this->categoryId = $categoryId;
        $this->jobId = $jobId;
    }


    public function handle(): void
    {
        $this->jobHelperService = app(JobHelperService::class);
        $this->progressReporter = app(JobProgressReporterService::class);
        $this->policy = app(JobBroadCastPolicyService::class);
        $this->spreadsheetService = app(SpreadSheetReaderService::class);
        $this->importMapRowService = app(ImportMapRowService::class);

        $schoolBranch = Schoolbranches::find($this->schoolBranchId);
        $systemJob = SystemJob::find($this->jobId);
        $schoolAdmin = Schooladmin::where("school_branch_id", $this->schoolBranchId)->find($this->adminId);
        $systemJobDetails = SystemJobDetail::where("job_id", $this->jobId)->first();
        $mapping = $systemJobDetails->input['mapping'];
        $filePath = $systemJobDetails->input['file_path'];

        $this->jobHelperService->updateJobProgress($systemJob, $this->inProgressStatus, 'Reading file', 0);
        $this->progressReporter->maybeBroadcast($systemJob, 0, $this->inProgressStatus);

        try {
            $result = $this->spreadsheetService->read($filePath);
        } catch (SpreadSheetReadException $e) {
            $this->jobHelperService->failJob($systemJob, $e->getMessage(), $e->getCode() ?: 422);
            $this->progressReporter->maybeBroadcast($systemJob, 0, $this->failedStatus);
            return;
        }

        $header = $result->header;
        $dataRows = $result->dataRows;
        $total = $result->total;

        $systemJob->update([
            'total_items' => $total,
            'processed_items' => 0,
            'successful_items' => 0,
            'failed_items' => 0,
        ]);

        $columnIndexes = ColumnIndexResolverService::resolve(
            $header,
            $mapping
        );

        if ($columnIndexes === null) {
            $this->jobHelperService->failJob($systemJob, 'One or more required mapped columns were not found in the file header.', 422);
            $this->progressReporter->maybeBroadcast($systemJob, 0, $this->failedStatus);
            event(new JobEvent(
                $schoolAdmin,
                $schoolBranch,
                [
                    'job_id'   => $systemJob->id,
                    'status'   => "failed",
                    'stage'    => $systemJob->stage,
                    'progress' => 100,
                    'system_job'   => $systemJob ?? null,
                ]
            ));
            return;
        }

        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $this->jobHelperService->updateJobProgress($systemJob, $this->inProgressStatus, 'Importing teachers', 5);
        $this->progressReporter->maybeBroadcast($systemJob, 5, $this->inProgressStatus);

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2;
            $payload = $this->importMapRowService->mapRow($row->toArray(), $columnIndexes);
            $normalizePayload = $this->normalizeRow($payload, $schoolBranch->id);
            $validationError = $this->validateRow($normalizePayload, $rowNumber);
            if ($validationError !== null) {
                $skipped++;
                $errors[] = $validationError;
                $processed++;
                $this->batchTrackProgress($systemJob, $processed, $created, $updated, $skipped, $total);
                continue;
            }

            try {
                DB::transaction(function () use (
                    $normalizePayload,
                    &$created,
                    &$updated,
                    $schoolBranch
                ) {


                    $course = Courses::query()
                        ->where('school_branch_id', $schoolBranch->id)
                        ->where('course_title', $normalizePayload['course_title'])
                        ->where("course_code", $normalizePayload['course_code'])
                        ->first();

                    if ($course) {
                        $course->update($normalizePayload);
                        $updated++;
                    } else {
                        $course = Courses::create([...$normalizePayload, "school_branch_id" => $schoolBranch->id]);
                        $created++;
                    }

                    if (!empty($$normalizePayload['typeIds'])) {
                        $syncData = collect($normalizePayload['typeIds'])
                            ->pluck('type_id')
                            ->mapWithKeys(fn($typeId) => [
                                $typeId => ['school_branch_id' => $schoolBranch->id],
                            ])
                            ->toArray();

                        $course->types()->sync($syncData);
                    }


                    if (!empty($normalizePayload['specialty_id'])) {
                        $course->specialties()->sync([
                            $normalizePayload['specialty_id'] => [
                                'school_branch_id' => $schoolBranch->id
                            ]
                        ]);
                    }
                });
            } catch (Throwable $e) {
                $skipped++;
                $errors[] = [
                    'code' => 500,
                    'message' => "Row {$rowNumber}: " . $e->getMessage(),
                    'stage' => 'import',
                    'data' => [
                        'row' => $rowNumber,
                        'exception' => [
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ],
                        'payload' => $payload,
                        'timestamp' => Carbon::now()->toISOString()
                    ]
                ];
            }

            $processed++;
            $this->batchTrackProgress($systemJob, $processed, $created, $updated, $skipped, $total);
        }

        $this->flushBatchUpdate($systemJob, $processed, $created, $updated, $skipped);

        foreach ($errors as $error) {
            SystemJobError::create([
                'job_id' => $systemJob->id,
                'school_branch_id' => $schoolBranch->id,
                'code' => $error['code'] ?? 500,
                'message' => $error['message'] ?? 'Unknown error occurred',
                'stage' => $error['stage'] ?? 'import',
                'data' => $error['data'] ?? []
            ]);
        }

        $finalStatus = empty($errors) ? $this->completedStatus : $this->completedWithIssuesStatus;
        $finalStage = empty($errors) ? 'Completed' : 'Completed with issues';
        $summary = [
            'total'   => $total,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors'  => array_slice($errors, 0, 50),
        ];

        $systemJob->update([
            'status'      => $finalStatus,
            'stage'       => $finalStage,
            'progress'    => 100,
            'result'      => $summary,
            'updated_at'  => Carbon::now(),
            'finished_at' => Carbon::now(),
        ]);

        $this->progressReporter->maybeBroadcast($systemJob, 100, $finalStatus);

        $this->spreadsheetService->deleteIfExists($filePath);

        event(new JobEvent(
            $schoolAdmin,
            $schoolBranch,
            [
                'job_id'   => $systemJob->id,
                'status'   => "completed",
                'stage'    => $systemJob->stage,
                'progress' => 100,
                'system_job'   => $systemJob ?? null,
            ]
        ));
    }
    private function batchTrackProgress(
        SystemJob $systemJob,
        int $processed,
        int $created,
        int $updated,
        int $skipped,
        int $total
    ): void {
        $this->batchProcessed++;
        $this->batchCreated += $created - ($this->batchCreated ?? 0);
        $this->batchUpdated += $updated - ($this->batchUpdated ?? 0);
        $this->batchSkipped += $skipped - ($this->batchSkipped ?? 0);

        if ($this->batchProcessed >= $this->batchSize) {
            $this->flushBatchUpdate($systemJob, $processed, $created, $updated, $skipped);
        }

        $this->reportProgress($systemJob, $processed, $total);
    }

    private function flushBatchUpdate(
        SystemJob $systemJob,
        int $processed,
        int $created,
        int $updated,
        int $skipped
    ): void {
        if ($this->batchProcessed > 0) {
            $systemJob->update([
                'processed_items' => $processed,
                'successful_items' => $created + $updated,
                'failed_items' => $skipped,
            ]);

            $this->batchProcessed = 0;
            $this->batchCreated = 0;
            $this->batchUpdated = 0;
            $this->batchSkipped = 0;
        }
    }

    private function normalizeRow(array $payload, string $schoolBranchId): array
    {

        $payload['semester_id'] = !empty($payload['semester'])
            ? Semester::where('name', $payload['semester'])->value('id')
            : null;

        $formattedTypes = !empty($payload['course_types']) && is_iterable($payload['course_types'])
            ? collect($payload['course_types'])->pluck('course_type')->filter()->values()->toArray()
            : [];

        $payload['typeIds'] = !empty($formattedTypes)
            ? CourseType::whereIn('name', $formattedTypes)
            ->pluck('id')
            ->map(fn($id) => ['type_id' => $id])
            ->all()
            : [];

        $payload['credit'] = $payload['course_credit'] ?? null;

        $payload['specialty_id'] = (!empty($payload['specialty_name']) && !empty($payload['level_name']))
            ? Specialty::where('school_branch_id', $schoolBranchId)
            ->where('specialty_name', $payload['specialty_name'])
            ->whereHas(
                'level',
                fn($query) => $query
                    ->where('program_name', $payload['level_name'])
                    ->whereHas('levelType', fn($q) => $q->where('program_name', 'level_start_200'))
            )
            ->value('id')
            : null;

        return $payload;
    }
    private function validateRow(array $payload, int $rowNumber): ?array
    {
        $request = new CreateCourseRequest();
        $validator = Validator::make($payload, $request->rules());

        if ($validator->fails()) {
            $messages = collect($validator->errors()->all())
                ->map(fn($msg) => "Row {$rowNumber}: {$msg}")
                ->implode('; ');

            return [
                'code' => 422,
                'message' => $messages,
                'stage' => 'validation',
                'data' => [
                    'row' => $rowNumber,
                    'validation_errors' => $validator->errors()->toArray(),
                    'payload' => $payload,
                    'timestamp' => Carbon::now()->toISOString()
                ]
            ];
        }

        return null;
    }

    private function reportProgress(
        SystemJob $systemJob,
        int $processed,
        int $total
    ): void {
        $progress = $total > 0
            ? (int) min(99, floor(($processed / $total) * 95) + 5)
            : 5;

        $this->jobHelperService->updateJobProgress($systemJob, $this->inProgressStatus, 'Importing teachers', $progress);
        $this->maybeBroadcast($systemJob, $progress, $this->inProgressStatus);
    }

    private function maybeBroadcast(
        SystemJob $systemJob,
        int $progress,
        string $status
    ): void {
        $schoolAdmin = Schooladmin::where("school_branch_id", $this->schoolBranchId)->find($this->adminId);
        $schoolBranch = Schoolbranches::find($this->schoolBranchId);

        if (!$this->policy->shouldBroadcast(
            $progress,
            $this->lastBroadcastProgress,
            $this->lastBroadcastAt,
            $status,
            $this->lastBroadcastStatus
        )) {
            return;
        }

        event(new JobEvent(
            $schoolAdmin,
            $schoolBranch,
            [
                'job_id'   => $systemJob->id,
                'status'   => $status,
                'stage'    => $systemJob->stage,
                'progress' => $progress,
                'system_job'   => $systemJob ?? null,
            ]
        ));

        $this->lastBroadcastProgress = $progress;
        $this->lastBroadcastStatus = $status;
        $this->lastBroadcastAt = now();
    }

    public function failed(Throwable $exception): void
    {
        $schoolAdmin = Schooladmin::where("school_branch_id", $this->schoolBranchId)->find($this->adminId);
        $schoolBranch = Schoolbranches::find($this->schoolBranchId);
        $systemJob = SystemJob::find($this->jobId);

        Log::error("TeacherImportJob [" . ($systemJob?->id ?? $this->jobId) . "] permanently failed.", [
            'school_id' => $schoolBranch?->id,
            'error'     => $exception->getMessage(),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'payload'   => $systemJob?->input['origin'] ?? null,
            'trace'     => $exception->getTraceAsString(),
        ]);

        if ($systemJob) {
            SystemJobError::create([
                'job_id' => $systemJob->id,
                'school_branch_id' => $this->schoolBranchId,
                'code' => 500,
                'message' => 'Job failed permanently: ' . $exception->getMessage(),
                'stage' => 'job_failure',
                'data' => [
                    'exception' => [
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString()
                    ],
                    'timestamp' => Carbon::now()->toISOString()
                ]
            ]);

            $jobHelperService = app(JobHelperService::class);
            $jobHelperService->failJob($systemJob, $exception->getMessage(), 500);

            event(new JobEvent(
                $schoolAdmin,
                $schoolBranch,
                [
                    'job_id'   => $systemJob->id,
                    'status'   => 'failed',
                    'stage'    => 'Failed',
                    'progress' => 0,
                    'error'    => $exception->getMessage(),
                ]
            ));
        }
    }
}
