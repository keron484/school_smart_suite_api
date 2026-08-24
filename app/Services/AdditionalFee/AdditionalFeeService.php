<?php

namespace App\Services\AdditionalFee;


use App\Models\AdditionalFees;
use App\Models\Student;
use App\Notifications\AdditionalFee;
use Illuminate\Support\Str;
use Exception;
use App\Exceptions\AppException;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Jobs\NotificationJobs\SendAdditionalFeeNotification;
use App\Jobs\NotificationJobs\SendAdminAdditionalFeeReminderNotificationJob;
use App\Notifications\AdditionalFee\Student\StudentAdditionalFeeNotification;
use App\Notifications\AdditionalFee\Admin\AdminAdditionalFeeNotification;
use Carbon\Carbon;
use App\Events\Actions\AdminActionEvent;
use App\Events\Actions\StudentActionEvent;
use App\Events\Analytics\FinancialAnalyticsEvent;
use App\Constant\Analytics\Financial\FinancialAnalyticsEvent as FinancialEventConstant;

class AdditionalFeeService
{
    public function createStudentAdditionalFees(array $data, object $currentSchool, object $authAdmin)
    {
        $student = Student::where("school_branch_id", $currentSchool->id)->find($data['student_id']);
        $additionalFee = new AdditionalFees();
        $additionalFeeId = Str::uuid();
        $additionalFee->id = $additionalFeeId;
        $additionalFee->reason = $data['reason'];
        $additionalFee->amount = $data['amount'];
        $additionalFee->status = "unpaid";
        $additionalFee->due_date  = $data['due_date'];
        $additionalFee->additionalfee_category_id = $data['additionalfee_category_id'];
        $additionalFee->school_branch_id = $currentSchool->id;
        $additionalFee->specialty_id = $student->specialty_id;
        $additionalFee->level_id = $student->level_id;
        $additionalFee->student_id = $student->id;
        $additionalFee->save();

        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.additionalFee.create"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "additionalFeeManagement",
                "action" => "additionalFee.charged",
                "authAdmin" => $authAdmin,
                "data" => $additionalFee,
                "message" => "Additional Fee Created",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch' => $currentSchool->id,
            'studentIds'   => [$additionalFee->student_id],
            'feature'      => 'studentAdditionalFeeCreated',
            'message'      => 'Student Additional Fee Created',
            'data'         =>  $additionalFee,
        ]);
        event(new FinancialAnalyticsEvent(
            eventType: FinancialEventConstant::ADDITIONAL_FEE_INCURRED,
            version: 1,
            payload: [
                "school_branch_id" => $currentSchool->id,
                "category_id" => $data['additionalfee_category_id'],
                "amount" => $data['amount'],
                "specialty_id" => $student->specialty_id,
                "department_id" => $student->department_id,
                "level_id" => $student->level_id
            ]
        ));
        $student->notify(new StudentAdditionalFeeNotification($data['amount'], $data['reason']));
        $authAdmin->notify(new AdminAdditionalFeeNotification(1, $data['amount'], $data['reason']));
        return $additionalFee;
    }
    public function deleteStudentAdditionalFees(string $feeId, object $currentSchool, object $authAdmin)
    {
        $additionalFee = AdditionalFees::where("school_branch_id", $currentSchool->id)
            ->find($feeId);

        if (!$additionalFee) {
            throw new AppException(
                "Student Additional Fee record ID '{$feeId}' not found for school branch ID '{$currentSchool->id}'.",
                404,
                "Fee Record Not Found 🗑️",
                "We couldn't find the specific additional fee record you are trying to delete. It may have already been removed or the ID is incorrect.",
                null
            );
        }

        try {
            $additionalFee->delete();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.additionalFee.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "additionalFeeManagement",
                    "action" => "additionalFee.deleted",
                    "authAdmin" => $authAdmin,
                    "data" =>  $additionalFee,
                    "message" => "Additional Fee Deleted",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => [$additionalFee->student_id],
                'feature'      => 'studentAdditionalFeeDelete',
                'message'      => 'Student Additional Fee Deleted',
                'data'         =>  $additionalFee,
            ]);
            return $additionalFee;
        } catch (Exception $e) {
            throw new AppException(
                "Failed to delete Student Additional Fee record ID '{$feeId}'. Error: " . $e->getMessage(),
                500,
                "Deletion Failed Due to System Error 🛑",
                "We were unable to remove the fee record due to a system error. Please try again or contact support.",
                null
            );
        }
    }
    public function updateStudentAdditionalFees(array $data, string $feeId, object $currentSchool, object $authAdmin)
    {
        $additionalFee = AdditionalFees::where("school_branch_id", $currentSchool->id)
            ->with(['student'])->find($feeId);

        if (!$additionalFee) {
            throw new AppException(
                "Student Additional Fee record ID '{$feeId}' not found for school branch ID '{$currentSchool->id}'.",
                404,
                "Fee Record Not Found 🔎",
                "We couldn't find the specific additional fee record you are trying to update. It may have already been deleted or the ID is incorrect.", // Detailed User Message
                null
            );
        }

        $removedEmptyInputs = array_filter($data);

        if (empty($removedEmptyInputs)) {
            throw new AppException(
                "Attempted update on fee ID '{$feeId}' with empty data.",
                400,
                "No Data Provided for Update 📝",
                "Please provide the specific fields and values you wish to update for this additional fee record.",
                null
            );
        }

        try {
            $additionalFee->update($removedEmptyInputs);
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.additionalFee.update"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "additionalFeeManagement",
                    "action" => "additionalFee.deleted",
                    "authAdmin" => $authAdmin,
                    "data" =>  $additionalFee,
                    "message" => "Additional Fee Updated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => [$additionalFee->student_id],
                'feature'      => 'studentAdditionalFeeUpdate',
                'message'      => 'Student Additional Fee Updated',
                'data'         =>  $additionalFee,
            ]);
            event(new FinancialAnalyticsEvent(
                eventType: FinancialEventConstant::ADDITIONAL_FEE_UPDATED,
                version: 1,
                payload: [
                    "school_branch_id" => $currentSchool->id,
                    "new_category_id" => $data['additionalfee_category_id'],
                    "old_category_id" => $additionalFee->additionalfee_category_id,
                    "new_amount" => $data['amount'],
                    "old_amount" => $additionalFee->amount,
                    "specialty_id" => $additionalFee->student->specialty_id,
                    "department_id" => $additionalFee->student->department_id,
                    "level_id" => $additionalFee->student->level
                ]
            ));
            return $additionalFee;
        } catch (Exception $e) {
            throw new AppException(
                "Failed to update Student Additional Fee record ID '{$feeId}'. Error: " . $e->getMessage(),
                500,
                "Update Failed Due to System Error 🛑",
                "We were unable to save the changes to the fee record due to a system error. Please try again or contact support.",
                null
            );
        }
    }
    public function getStudentAdditionalFeesStudentId(string $studentId, object $currentSchool)
    {
        try {
            $additionalFee = AdditionalFees::where("school_branch_id", $currentSchool->id)
                ->where("student_id", $studentId)
                ->with(['student', 'specialty', 'level', 'feeCategory'])
                ->get();

            if ($additionalFee->isEmpty()) {
                throw new AppException(
                    "No additional fees were found for this student.",
                    404,
                    "No Additional Fees Found",
                    "There are no additional fee records available for the specified student in the system.",
                    null
                );
            }

            return $additionalFee;
        } catch (AppException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected error occurred while retrieving student additional fees.",
                500,
                "Internal Server Error",
                "A server-side issue prevented the additional fee data from being retrieved successfully.",
                null
            );
        }
    }
    public function getAdditionalFeeDetails(object $currentSchool, string $feeId)
    {
        $additionalFeeDetails = AdditionalFees::where("school_branch_id", $currentSchool->id)
            ->with(['student', 'specialty', 'level', 'feeCategory'])
            ->find($feeId);

        if (!$additionalFeeDetails) {
            throw new AppException(
                "Additional Fee record ID '{$feeId}' not found for school branch ID '{$currentSchool->id}'.",
                404,
                "Fee Details Not Found 🔎",
                "We couldn't find the detailed record for the additional fee you requested. Please verify the Fee ID and ensure it belongs to a student at your school branch.", // Detailed User Message
                null
            );
        }
        return $additionalFeeDetails;
    }
    public function getAdditionalFees(object $currentSchool)
    {
        try {
            $data = AdditionalFees::where("school_branch_id", $currentSchool->id)
                ->with(['student', 'specialty', 'level', 'feeCategory'])
                ->get();

            if ($data->isEmpty()) {
                throw new AppException(
                    "No additional fees were found for this school branch.",
                    404,
                    "No Additional Fees Found",
                    "There are no additional fee records available in the system for your school branch.",
                    null
                );
            }

            return $data;
        } catch (AppException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected error occurred while retrieving additional fees.",
                500,
                "Internal Server Error",
                "A server-side issue prevented the list of additional fees from being retrieved successfully.",
                null
            );
        }
    }
    public function bulkUpdateStudentAdditionalFees($data, $currentSchool, $authAdmin)
    {
        if (empty($data)) {
            throw new AppException(
                "No fee data was provided for bulk update.",
                400,
                "No Data Provided for Update 📝",
                "Please provide a list of additional fee records you wish to update.",
                null
            );
        }
        $studentIds = [];
        $successfulUpdates = [];
        $failedUpdates = [];
        $updateAttempts = collect($data);

        $updates = $updateAttempts
            ->filter(fn($fee) => isset($fee['fee_id']) && $fee['fee_id'] !== null)
            ->map(function ($fee) {
                return collect($fee)->filter(fn($value) => !is_null($value));
            });

        $feeIds = $updates->pluck('fee_id')->toArray();
        $nonExistentIds = [];

        DB::beginTransaction();

        try {
            $existingFees = AdditionalFees::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $feeIds)
                ->get()
                ->keyBy('id');

            foreach ($feeIds as $id) {
                if (!$existingFees->has($id)) {
                    $nonExistentIds[] = $id;
                }
            }

            $updates->each(function ($data) use ($existingFees, &$successfulUpdates, &$failedUpdates) {
                $feeId = $data['fee_id'];

                if (!$existingFees->has($feeId)) {
                    return;
                }

                $fee = $existingFees->get($feeId);
                $updateData = $data->except('fee_id');
                $originalAmount = $fee->amount;

                if ($fee->status === 'paid' && $updateData->has('amount')) {
                    if (bccomp($originalAmount, $updateData['amount'], 2) !== 0) {
                        $failedUpdates[] = [
                            'id' => $feeId,
                            'reason' => "Cannot update 'amount' because the fee is already paid (Current Amount: {$originalAmount}).",
                            'status' => 'paid',
                        ];
                        $updateData = $updateData->except('amount');
                    }
                }

                if ($updateData->isNotEmpty()) {
                    $fee->update($updateData->toArray());
                    $successfulUpdates[] = $feeId;
                    $studentIds[] = $fee->student_id;
                } elseif (in_array($feeId, array_column($failedUpdates, 'id'))) {
                } else {
                    $failedUpdates[] = [
                        'id' => $feeId,
                        'reason' => "No valid fields were provided for update after filtering empty and restricted ('amount') fields.",
                    ];
                }
            });

            if (!empty($nonExistentIds) || !empty($failedUpdates)) {
                DB::rollBack();

                $errorMessages = [];
                if (!empty($nonExistentIds)) {
                    $errorMessages[] = "The following fee IDs were not found at your school branch: " . implode(', ', $nonExistentIds);
                }
                if (!empty($failedUpdates)) {
                    $failedList = collect($failedUpdates)->map(fn($f) => "ID {$f['id']}: {$f['reason']}")->implode('; ');
                    $errorMessages[] = "The following updates failed due to validation rules (e.g., attempting to change amount on a paid fee): {$failedList}";
                }

                throw new AppException(
                    "Bulk update failed due to validation errors or missing IDs.",
                    400,
                    "Bulk Update Failed 🛑",
                    "Some of the fee records could not be updated. The entire batch was rolled back. Details: " . implode(' | ', $errorMessages),
                    null
                );
            }

            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.additionalFee.update"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "additionalFeeManagement",
                    "action" => "additionalFee.updated",
                    "authAdmin" => $authAdmin,
                    "data" =>  $successfulUpdates,
                    "message" => "Additional Fee Updated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => $studentIds,
                'feature'      => 'studentAdditionalFeeUpdate',
                'message'      => 'Student Additional Fee Updated',
                'data'         =>   $successfulUpdates,
            ]);

            return $successfulUpdates;
        } catch (AppException $e) {
            DB::rollBack();
            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();
            throw new AppException(
                "A critical error occurred during the bulk update process: " . $e->getMessage(),
                500,
                "System Update Failed 🚨",
                "We were unable to complete the bulk update due to an unexpected system error. The operation has been rolled back. Please try again or contact support.",
                null
            );
        }
    }
    public function getStudentAdditionalFees($currentSchool, $student, $status)
    {
        $allowedStatus = collect([
            "unpaid",
            "paid",
            "due"
        ]);
        if (!$allowedStatus->contains($status)) {
            throw new AppException(
                "Invalid Additional Fee Status",
                404,
                "Invalid Additional Fee Status",
                "Invalid Additional Fee Status, Additional Fee Status Must Only Be unpaid paid or due"
            );
        }

        $data = AdditionalFees::where("school_branch_id", $currentSchool->id)
            ->where("student_id", $student->id)
            ->where("status", $status)
            ->with(['feeCategory'])
            ->get();
        return $data;
    }
    public function bulkBillStudents(array $studentList, $currentSchool, $authAdmin)
    {
        try {
            DB::beginTransaction();

            $studentsToInsert = [];
            $studentNotificationData = [];
            $additionalFeeIds = [];

            $studentIds = collect($studentList)->pluck('student_id')->unique()->filter();
            $students = Student::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $studentIds)
                ->get()
                ->keyBy('id');

            foreach ($studentList as $studentData) {
                $student = $students->get($studentData['student_id']);

                if (!$student) {
                    continue;
                }

                $newId = Str::uuid();

                $studentsToInsert[] = [
                    'id'                          => $newId,
                    'reason'                      => $studentData['reason'],
                    'amount'                      => $studentData['amount'],
                    'status'                      => 'unpaid',
                    'additionalfee_category_id'   => $studentData['additionalfee_category_id'],
                    'school_branch_id'            => $currentSchool->id,
                    'specialty_id'                => $student->specialty_id,
                    'level_id'                    => $student->level_id,
                    'student_id'                  => $student->id,
                    'due_date'                    => Carbon::now()->addDays(7),
                    'created_at'                  => now(),
                    'updated_at'                  => now(),
                ];

                $studentNotificationData[] = [
                    'student' => $student,
                    'amount'  => $studentData['amount'],
                    'reason'  => $studentData['reason'],
                ];

                $additionalFeeIds[] = $newId;
            }

            if (!empty($studentsToInsert)) {
                DB::table('additional_fees')->insert($studentsToInsert);
            }

            DB::commit();

            if (!empty($studentNotificationData)) {
                SendAdditionalFeeNotification::dispatch($studentNotificationData);
            }

            if (!empty($additionalFeeIds)) {
                SendAdminAdditionalFeeReminderNotificationJob::dispatch(
                    $additionalFeeIds,
                    $currentSchool->id
                );

                SendAdminAdditionalFeeReminderNotificationJob::dispatch(
                    $additionalFeeIds,
                    $currentSchool->id
                )->delay(Carbon::now()->addDays(7));
                AdminActionEvent::dispatch(
                    [
                        "permissions" =>  ["schoolAdmin.additionalFee.create"],
                        "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                        "schoolBranch" =>  $currentSchool->id,
                        "feature" => "additionalFeeManagement",
                        "action" => "additionalFee.charged",
                        "authAdmin" => $authAdmin,
                        "data" => $additionalFeeIds,
                        "message" => "Additional Fee Created",
                    ]
                );
                StudentActionEvent::dispatch([
                    'schoolBranch' => $currentSchool->id,
                    'studentIds'   => $studentIds->toArray(),
                    'feature'      => 'studentAdditionalFeeCreated',
                    'message'      => 'Student Additional Fee Created',
                    'data'         =>  $studentNotificationData,
                ]);
                event(new FinancialAnalyticsEvent(
                    eventType: FinancialEventConstant::ADDITIONAL_FEE_INCURRED,
                    version: 1,
                    payload: [
                        "school_branch_id" => $currentSchool->id,
                        "category_id" => $studentData['additionalfee_category_id'],
                        "amount" => $studentData['amount'],
                        "specialty_id" => $student->specialty_id,
                        "department_id" => $student->department_id,
                        "level_id" => $student->level_id,
                        "value" => 10000
                    ]
                ));
            }

            return $studentsToInsert;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function bulkDeleteStudentAdditionalFees($additionalFeeIds, $currentSchool, $authAdmin)
    {
        $result = [];
        $studentIds = [];
        try {
            DB::beginTransaction();
            foreach ($additionalFeeIds as $additionalFeeId) {
                $studentAdditionalFee = AdditionalFees::findOrFail($additionalFeeId['fee_id']);
                $studentAdditionalFee->delete();
                $result[] = [
                    $studentAdditionalFee
                ];
                $studentIds[] = $studentAdditionalFee->student_id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.additionalFee.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "additionalFeeManagement",
                    "action" => "additionalFee.Deleted",
                    "authAdmin" => $authAdmin,
                    "data" =>  $additionalFeeIds,
                    "message" => "Additional Fee Deleted",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => $studentIds,
                'feature'      => 'studentAdditionalFeeDelete',
                'message'      => 'Student Additional Fee Deleted',
                'data'         =>  $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
