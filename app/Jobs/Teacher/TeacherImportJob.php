<?php

namespace App\Jobs\Teacher;

use App\Events\Job\JobEvent;
use App\Models\Job\SystemJob;
use App\Models\Teacher;
use App\Services\Job\JobBroadCastPolicyService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class TeacherImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected object $authAdmin;
    protected object $currentSchool;
    protected object $category;
    protected object $systemJob;
    protected array $request;

    private int $lastBroadcastProgress = 0;
    private ?string $lastBroadcastStatus = null;
    private ?Carbon $lastBroadcastAt = null;

    public function __construct(
        object $authAdmin,
        object $currentSchool,
        object $category,
        object $systemJob,
        array $request
    ) {
        $this->authAdmin = $authAdmin;
        $this->currentSchool = $currentSchool;
        $this->category = $category;
        $this->systemJob = $systemJob;
        $this->request = $request;
    }

    public function handle(): void
    {
        $systemJob = $this->systemJob;
        $mapping = $this->request['map'];
        $filePath = $this->request['file_path'] ?? $systemJob->input_file_path;
        $policy = app(JobBroadCastPolicyService::class);

        $this->updateJobProgress($systemJob, 'processing', 'Reading file', 0);
        $this->maybeBroadcast($systemJob, 0, 'processing', $policy);

        if (!Storage::disk('r2')->exists($filePath)) {
            $this->failJob($systemJob, 'Uploaded file could not be found on storage.', 404);
            $this->maybeBroadcast($systemJob, 0, 'failed', $policy);
            return;
        }

        $temporaryLocalPath = tempnam(sys_get_temp_dir(), 'teacher_import_');

        try {
            $stream = Storage::disk('r2')->readStream($filePath);
            file_put_contents($temporaryLocalPath, stream_get_contents($stream));
            if (is_resource($stream)) {
                fclose($stream);
            }

            $rows = Excel::toCollection(null, $temporaryLocalPath)->first();
        } catch (Throwable $e) {
            $this->failJob($systemJob, 'Unable to read spreadsheet: ' . $e->getMessage(), 422);
            $this->maybeBroadcast($systemJob, 0, 'failed', $policy);
            return;
        } finally {
            if (file_exists($temporaryLocalPath)) {
                unlink($temporaryLocalPath);
            }
        }

        if ($rows === null || $rows->isEmpty()) {
            $this->failJob($systemJob, 'The uploaded file contains no data rows.', 422);
            $this->maybeBroadcast($systemJob, 0, 'failed', $policy);
            return;
        }

        $header = $rows->first()->map(fn($value) => strtolower(trim((string) $value)))->toArray();
        $dataRows = $rows->slice(1)->values();

        $columnIndexes = $this->resolveColumnIndexes($header, $mapping);

        if ($columnIndexes === null) {
            $this->failJob($systemJob, 'One or more required mapped columns were not found in the file header.', 422);
            $this->maybeBroadcast($systemJob, 0, 'failed', $policy);
            return;
        }

        $total = $dataRows->count();
        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $this->updateJobProgress($systemJob, 'processing', 'Importing teachers', 5);
        $this->maybeBroadcast($systemJob, 5, 'processing', $policy);

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2;
            $payload = $this->mapRow($row->toArray(), $columnIndexes);

            $validationError = $this->validateRow($payload, $rowNumber);
            if ($validationError !== null) {
                $skipped++;
                $errors[] = $validationError;
                $processed++;
                $this->reportProgress($systemJob, $processed, $total, $policy);
                continue;
            }

            try {
                DB::transaction(function () use ($payload, &$created, &$updated) {
                    $teacher = Teacher::query()
                        ->where('school_branch_id', $this->currentSchool->id)
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
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }

            $processed++;
            $this->reportProgress($systemJob, $processed, $total, $policy);
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

        $this->maybeBroadcast($systemJob, 100, $finalStatus, $policy);

        if (Storage::disk('r2')->exists($filePath)) {
            Storage::disk('r2')->delete($filePath);
        }
    }

    private function resolveColumnIndexes(array $header, array $mapping): ?array
    {
        $required = ['email', 'full_names', 'first_name', 'last_name', 'phone'];
        $indexes = [];

        foreach ($required as $key) {
            $columnName = strtolower(trim($mapping[$key] ?? ''));
            $index = array_search($columnName, $header, true);
            if ($index === false) {
                return null;
            }
            $indexes[$key] = $index;
        }

        foreach (['address', 'gender'] as $optional) {
            if (!empty($mapping[$optional])) {
                $columnName = strtolower(trim($mapping[$optional]));
                $index = array_search($columnName, $header, true);
                if ($index !== false) {
                    $indexes[$optional] = $index;
                }
            }
        }

        return $indexes;
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

        return [
            'school_branch_id' => $this->currentSchool->id,
            'email'            => strtolower((string) $get('email')),
            'name'             => (string) $get('full_names'),
            'first_name'       => (string) $get('first_name'),
            'last_name'        => (string) $get('last_name'),
            'phone'            => (string) $get('phone'),
            'password'        => $this->generateRandomPassword(),
            'username'         => $this->generateUsername($get('full_names')),
            'address'          => $get('address'),
            'status'           => 'active',
        ];
    }

    private function validateRow(array $payload, int $rowNumber): ?string
    {
        if (empty($payload['email']) || !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            return "Row {$rowNumber}: Invalid or missing email.";
        }
        if (empty($payload['name'])) {
            return "Row {$rowNumber}: Missing full name.";
        }
        if (empty($payload['first_name'])) {
            return "Row {$rowNumber}: Missing first name.";
        }
        if (empty($payload['last_name'])) {
            return "Row {$rowNumber}: Missing last name.";
        }
        if (empty($payload['phone'])) {
            return "Row {$rowNumber}: Missing phone.";
        }
        return null;
    }

    private function reportProgress(
        SystemJob $systemJob,
        int $processed,
        int $total,
        JobBroadCastPolicyService $policy
    ): void {
        $progress = $total > 0
            ? (int) min(99, floor(($processed / $total) * 95) + 5)
            : 5;

        $this->updateJobProgress($systemJob, 'processing', 'Importing teachers', $progress);
        $this->maybeBroadcast($systemJob, $progress, 'processing', $policy);
    }

    private function maybeBroadcast(
        SystemJob $systemJob,
        int $progress,
        string $status,
        JobBroadCastPolicyService $policy
    ): void {
        if (!$policy->shouldBroadcast(
            $progress,
            $this->lastBroadcastProgress,
            $this->lastBroadcastAt,
            $status,
            $this->lastBroadcastStatus
        )) {
            return;
        }

        event(new JobEvent(
            $this->authAdmin,
            $this->currentSchool,
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

    private function updateJobProgress(SystemJob $systemJob, string $status, string $stage, int $progress): void
    {
        $systemJob->update([
            'status'     => $status,
            'stage'      => $stage,
            'progress'   => $progress,
            'updated_at' => Carbon::now(),
        ]);
    }

    private function failJob(SystemJob $systemJob, string $message, int|string $code): void
    {
        $systemJob->update([
            'status'        => 'failed',
            'stage'         => 'Failed',
            'progress'      => 0,
            'error_code'    => (string) $code,
            'error_message' => $message,
            'updated_at'    => Carbon::now(),
            'finished_at'   => Carbon::now(),
        ]);

        $systemJob->systemJobEvent()->create([
            'event_type' => 'error',
            'message'    => $message,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error("TeacherImportJob [{$this->systemJob->id}] permanently failed.", [
            'school_id' => $this->currentSchool->id,
            'error'     => $exception->getMessage(),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'payload'   => $this->request,
            'trace'     => $exception->getTraceAsString(),
        ]);

        if ($this->systemJob) {
            $this->failJob($this->systemJob, $exception->getMessage(), 500);

            event(new JobEvent(
                $this->authAdmin,
                $this->currentSchool,
                [
                    'job_id'   => $this->systemJob->id,
                    'status'   => 'failed',
                    'stage'    => 'Failed',
                    'progress' => 0,
                    'error'    => $exception->getMessage(),
                ]
            ));
        }
    }

    private function generateUsername(string $name): string
    {
        $normalized = strtolower(trim($name));
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);

        $parts = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        $base = match (true) {
            empty($parts)       => 'user',
            count($parts) === 1 => $parts[0],
            default             => $parts[0][0] . end($parts),
        };

        $base = strlen($base) < 3 ? str_pad($base, 3, '0') : $base;

        $base = substr($base, 0, 20);

        $username = $base;
        $counter = 1;
        while (Teacher::where('username', $username)->exists()) {
            $username = $base . $counter++;
        }

        return $username;
    }
    private function generateRandomPassword($length = 10): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
