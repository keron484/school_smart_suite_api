<?php

namespace App\Jobs\Teacher;

use App\Events\Job\JobEvent;
use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobError;
use App\Models\Schoolbranches;
use App\Models\Teacher;
use App\Services\Helpers\Actor\ActorHelperService;
use App\Services\Helpers\Job\JobHelperService;
use App\Services\Helpers\Job\JobProgressReporterService;
use App\Services\Helpers\Import\SpreadSheetReadException;
use App\Services\Helpers\Import\SpreadSheetReaderService;
use App\Services\Job\JobBroadCastPolicyService;
use App\Http\Requests\Teacher\CreateTeacherRequest;
use App\Models\Gender;
use App\Models\Job\SystemJobDetail;
use App\Models\Schooladmin;
use App\Services\Helpers\Import\ColumnIndexResolverService;
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

class TeacherImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected string $adminId;
    protected string $schoolBranchId;
    protected string $categoryId;
    protected string $jobId;
    public array $requiredFields = ['email', 'full_names', 'first_name', 'last_name', 'phone'];
    public array $optionalFields = ['address', 'gender'];

    private JobProgressReporterService $progressReporter;
    private JobBroadCastPolicyService $policy;
    private JobHelperService $jobHelperService;
    private SpreadSheetReaderService $spreadsheetService;

    private int $lastBroadcastProgress = 0;
    private ?string $lastBroadcastStatus = null;
    private ?Carbon $lastBroadcastAt = null;

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

        $schoolBranch = Schoolbranches::find($this->schoolBranchId);
        $systemJob = SystemJob::find($this->jobId);
        $systemJobDetails = SystemJobDetail::where("job_id", $this->jobId)->first();
        $mapping = $systemJobDetails->input['map'];
        $filePath = $systemJobDetails->input['file_path'];

        $this->jobHelperService->updateJobProgress($systemJob, 'processing', 'Reading file', 0);
        $this->progressReporter->maybeBroadcast($systemJob, 0, 'processing');

        try {
            $result = $this->spreadsheetService->read($filePath);
        } catch (SpreadSheetReadException $e) {
            $this->jobHelperService->failJob($systemJob, $e->getMessage(), $e->getCode() ?: 422);
            $this->progressReporter->maybeBroadcast($systemJob, 0, 'failed');
            return;
        }

        $header = $result->header;
        $dataRows = $result->dataRows;
        $total = $result->total;

        $columnIndexes = ColumnIndexResolverService::resolve(
            $header,
            $mapping,
            $this->requiredFields,
            $this->optionalFields
        );

        if ($columnIndexes === null) {
            $this->jobHelperService->failJob($systemJob, 'One or more required mapped columns were not found in the file header.', 422);
            $this->progressReporter->maybeBroadcast($systemJob, 0, 'failed');
            return;
        }

        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $this->jobHelperService->updateJobProgress($systemJob, 'processing', 'Importing teachers', 5);
        $this->progressReporter->maybeBroadcast($systemJob, 5, 'processing');

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2;
            $payload = $this->mapRow($row->toArray(), $columnIndexes);

            $validationError = $this->validateRow($payload, $rowNumber);
            if ($validationError !== null) {
                $skipped++;
                $errors[] = $validationError;
                $processed++;
                $this->reportProgress($systemJob, $processed, $total);
                continue;
            }

            try {
                DB::transaction(function () use ($payload, &$created, &$updated, $schoolBranch) {
                    $teacher = Teacher::query()
                        ->where('school_branch_id', $schoolBranch->id)
                        ->where('email', $payload['email'])
                        ->first();

                    if ($teacher) {
                        $teacher->update($payload);
                        $updated++;
                    } else {
                        Teacher::create($payload);
                        $created++;
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
            $this->reportProgress($systemJob, $processed, $total);
        }

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

        $finalStatus = empty($errors) ? 'completed' : 'completed_with_issues';
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
    }

    private function mapRow(array $row, array $columnIndexes): array
    {
        $get = function (string $key) use ($row, $columnIndexes) {
            if (!isset($columnIndexes[$key])) {
                return null;
            }
            $value = $row[$columnIndexes[$key]] ?? null;
            return is_string($value) ? trim($value) : $value;
        };

        $genderName = $get('gender');
        $genderId = null;
        if ($genderName !== null && $genderName !== '') {
            $gender = Gender::where('name', $genderName)->first();
            $genderId = $gender ? $gender->id : null;
        }

        return [
            'school_branch_id' => $this->schoolBranchId,
            'email'            => strtolower((string) $get('email')),
            'name'             => (string) $get('full_names'),
            'first_name'       => (string) $get('first_name'),
            'last_name'        => (string) $get('last_name'),
            'phone'            => (string) $get('phone'),
            'password'         => app(ActorHelperService::class)->generateRandomPassword(),
            'username'         => app(ActorHelperService::class)->generateUsername($get('full_names'), Teacher::class),
            'gender_id'        => $genderId,
            'address'          => $get('address'),
            'status'           => 'active',
        ];
    }

    private function validateRow(array $payload, int $rowNumber): ?array
    {
        $request = new CreateTeacherRequest();
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

        $this->jobHelperService->updateJobProgress($systemJob, 'processing', 'Importing teachers', $progress);
        $this->maybeBroadcast($systemJob, $progress, 'processing');
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
                'result'   => $systemJob->result ?? null,
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
