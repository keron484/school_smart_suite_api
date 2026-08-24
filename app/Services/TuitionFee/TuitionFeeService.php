<?php

namespace App\Services\TuitionFee;

use App\Models\Student;
use App\Models\TuitionFees;
use App\Models\Feepayment;
use Exception;
use Throwable;
use App\Exceptions\AppException;
use App\Events\Actions\AdminActionEvent;
use App\Events\Actions\StudentActionEvent;
class TuitionFeeService
{
    public function getFeesPaid(object $currentSchool)
    {
        try {
            $paidFeesData = Feepayment::where('school_branch_id', $currentSchool->id)
                ->with(['student.specialty.level', 'student.specialty.department'])
                ->get();

            if ($paidFeesData->isEmpty()) {
                throw new AppException(
                    "No fee payment records were found for this school branch.",
                    404,
                    "No Fee Payments Found",
                    "There are no records of fees paid in the system for your school branch.",
                    null
                );
            }

            return $paidFeesData;
        } catch (AppException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected error occurred while retrieving fee payment records.",
                500,
                "Internal Server Error",
                "A server-side issue prevented the fee payment data from being retrieved successfully.",
                null
            );
        }
    }
    public function deleteFeePayment(string $feeId, object $currentSchool, $authAdmin)
    {
        try {
            $findFeePayment = Feepayment::where('school_branch_id', $currentSchool->id)
                ->find($feeId);

            if (!$findFeePayment) {
                throw new AppException(
                    "Fee payment record ID '{$feeId}' not found for school branch ID '{$currentSchool->id}'.",
                    404,
                    "Fee Payment Not Found 🔎",
                    "The specific fee payment record you are trying to delete could not be located.",
                    null
                );
            }

            $findFeePayment->delete();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.tuitionFee.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "tuitionFeeManagement",
                    "authAdmin" => $authAdmin,
                    "data" => $findFeePayment,
                    "message" => "Tuition Fee Payment Deleted",
                ]
            );
                        StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => [$findFeePayment->student_id],
                'feature'      => 'tuitionFeeDelete',
                'message'      => 'Tuition Fees Deleted',
                'data'         => $findFeePayment,
            ]);
            return $findFeePayment;
        } catch (AppException $e) {
            throw $e;
        } catch (Throwable $e) {
            $message = "Failed to delete fee payment record ID '{$feeId}'. Error: " . $e->getMessage();

            if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                throw new AppException(
                    $message,
                    409,
                    "Deletion Blocked 🔒",
                    "Cannot delete the fee payment as it is linked to other dependent financial records. Please ensure all associations are removed first.",
                    null
                );
            }

            throw new AppException(
                $message,
                500,
                "Deletion Failed 🛑",
                "A system error occurred during fee payment deletion. Please try again or contact support.",
                null
            );
        }
    }
    public function getFeeDebtors(object $currentSchool)
    {
        $feeDebtors = Student::where('school_branch_id', $currentSchool->id)
            ->where('total_fee_debt', '>', 0)
            ->with(['specialty.level'])
            ->get();

        return $feeDebtors;
    }
    public function getTuitionFees(object $currentSchool)
    {
        try {
            $tuitionFees = TuitionFees::where("school_branch_id", $currentSchool->id)
                ->with(['student.specialty.level'])
                ->get();

            if ($tuitionFees->isEmpty()) {
                throw new AppException(
                    "No tuition fee records were found for this school branch.",
                    404,
                    "No Tuition Fees Found",
                    "There are no student tuition fee records available in the system for your school branch.",
                    null
                );
            }

            return $tuitionFees;
        } catch (AppException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected error occurred while retrieving tuition fee records.",
                500,
                "Internal Server Error",
                "A server-side issue prevented the tuition fee data from being retrieved successfully.",
                null
            );
        }
    }
    public function getTuitionFeeDetails(object $currentSchool, string $feeId)
    {
        $tuitionFees = TuitionFees::where("school_branch_id", $currentSchool->id)
            ->with(['student.specialty.level'])
            ->find($feeId);
        return $tuitionFees;
    }
}
