<?php

namespace App\Services\ActivationCode;

use App\Constant\Enums\SystemState;
use App\Exceptions\AppException;
use App\Models\ActivationCode;
use App\Models\ActivationCodeType;
use App\Models\ActivationCodeUsage;
use App\Models\PaymentMethod;
use App\Models\SchoolTransaction;
use App\Models\Student;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Teacher;
use App\Notifications\ActivationCode\Admin\AdminPurchaseSuccessfullNotification;
use App\Notifications\ActivationCode\Teacher\TeacherSubscribedNotification;
use App\Notifications\ActivationCode\Student\StudentSubscribedNotification;

class ActivationCodeService
{
    public function purchaseActivationCode($data, $currentSchool, $authAdmin)
    {
        $paymentMethod = PaymentMethod::find($data['payment_method_id']);
        if (!$paymentMethod) {
            throw new AppException(
                "Payment Method Not Found",
                404,
                "Invalid Payment Method",
                "The selected payment method was not found. Please choose a valid one."
            );
        }

        if ($paymentMethod->status === SystemState::INACTIVE) {
            throw new AppException(
                "Payment Method Unavailable",
                400,
                "Payment Method Unavailable",
                "The selected payment method ({$paymentMethod->name}) is currently unavailable. Please choose another."
            );
        }

        $teacherCount = (int) ($data['teacher_code_count'] ?? 0);
        $studentCount = (int) ($data['student_code_count'] ?? 0);

        if ($teacherCount <= 0 && $studentCount <= 0) {
            throw new AppException(
                "Invalid Quantity",
                400,
                "No Codes Requested",
                "You must request at least one teacher or student activation code."
            );
        }

        $pricing = ActivationCodeType::where('country_id', $currentSchool->school->country_id)
            ->whereIn('type', ['teacher', 'student'])
            ->pluck('price', 'type');

        $teacherPrice = $pricing['teacher'] ?? 0;
        $studentPrice = $pricing['student'] ?? 0;

        if ($teacherCount > 0 && !$teacherPrice) {
            throw new AppException(
                "Pricing Missing",
                400,
                "Teacher Pricing Not Configured",
                "Teacher activation code pricing is not set for your country."
            );
        }

        if ($studentCount > 0 && !$studentPrice) {
            throw new AppException(
                "Pricing Missing",
                400,
                "Student Pricing Not Configured",
                "Student activation code pricing is not set for your country."
            );
        }

        $totalAmount = ($teacherCount * $teacherPrice) + ($studentCount * $studentPrice);

        if ($totalAmount <= 0) {
            throw new AppException(
                "Invalid Amount",
                400,
                "Zero Amount",
                "The total transaction amount cannot be zero."
            );
        }

        $codes = [];
        $expiresAt = Carbon::now()->addYear();

        for ($i = 0; $i < $teacherCount; $i++) {
            $codes[] = [
                'id' => Str::uuid(),
                'code' => 'TEA-' . strtoupper(Str::random(8)),
                'code_type' => 'teacher',
                'status' => 'active',
                'used' => false,
                'price' => $teacherPrice,
                'duration' => 365,
                'expires_at' => $expiresAt,
                'school_branch_id' => $currentSchool->id,
                'country_id' => $currentSchool->school->country_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        for ($i = 0; $i < $studentCount; $i++) {
            $codes[] = [
                'id' => Str::uuid(),
                'code' => 'STU-' . strtoupper(Str::random(8)),
                'code_type' => 'student',
                'status' => 'active',
                'used' => false,
                'price' => $studentPrice,
                'duration' => 365,
                'expires_at' => $expiresAt,
                'school_branch_id' => $currentSchool->id,
                'country_id' => $currentSchool->school->country_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ActivationCode::insert($codes);

        $transaction = SchoolTransaction::create([
            'type' => 'activation_code_purchase',
            'amount' => $totalAmount,
            'payment_ref' => 'PAY-' . strtoupper(Str::random(10)),
            'transaction_id' => 'TRX-' . strtoupper(Str::random(12)),
            'status' => 'completed',
            'payment_method_id' => $paymentMethod->id,
            'country_id' => $currentSchool->school->country_id,
            'school_branch_id' => $currentSchool->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $authAdmin->notify(new AdminPurchaseSuccessfullNotification(
            [
                [
                    'type' => 'teacher',
                    'quantity' => $teacherCount,
                    'duration' => 365,
                ],
                [
                    'type' => 'student',
                    'quantity' => $studentCount,
                    'duration' => 365,
                ],
            ],
            $totalAmount
        ));
        return [
            'transaction' => $transaction,
            'codes_generated' => $codes,
            'total_amount' => $totalAmount,
            'teacher_codes' => $teacherCount,
            'student_codes' => $studentCount,
        ];
    }
    public function getSchoolBranchActivationCodes($currentSchool)
    {
        $activationCodes = ActivationCode::Where("school_branch_id", $currentSchool->id)
            ->with(['country', 'activationCodeType'])
            ->get();
        return $activationCodes->map(fn($a) => [
            "id" => $a->id,
            "created_at" => $a->created_at,
            "updated_at" => $a->updated_at,
            'code' => $a->code,
            'code_type' => $a->code_type,
            'status' => $a->status,
            'used' => $a->used,
            'price' => $a->price,
            'duration' => $a->duration,
            'expires_at' => $a->expires_at,
        ]);
    }
    public function activateStudentAccount($data, $currentSchool)
    {
        $student = Student::where('school_branch_id', $currentSchool->id)
            ->find($data['student_id']);

        if (!$student) {
            throw new AppException(
                "Student Not Found",
                404,
                "Student Not Found",
                "The student account could not be found. Please ensure it has not been deleted."
            );
        }

        $activeUsage = ActivationCodeUsage::where('actorable_id', $student->id)
            ->where('actorable_type', Student::class)
            ->where('expires_at', '>', now())
            ->first();

        if ($activeUsage) {
            throw new AppException(
                "Active Subscription Exists",
                409,
                "Already Subscribed",
                "This student already has an active subscription. Only one active subscription is allowed per student."
            );
        }

        $code = ActivationCode::where('school_branch_id', $currentSchool->id)
            ->where('code', $data['activation_code'])
            ->where('status', 'active')
            ->where('used', false)
            ->first();

        if (!$code) {
            throw new AppException(
                "Invalid or Unavailable Code",
                404,
                "Code Not Found",
                "The activation code '{$data['activation_code']}' is invalid, already used, inactive, or not found. Please check and try again."
            );
        }

        if ($code->expires_at <= now()) {
            throw new AppException(
                "Code Expired",
                409,
                "Code Expired",
                "The activation code '{$data['activation_code']}' has expired. Please use a valid, non-expired code."
            );
        }

        $subscriptionExpiresAt = now()->addDays($code->duration);

        $codeUsage = ActivationCodeUsage::create([
            'school_branch_id' => $currentSchool->id,
            'activation_code_id' => $code->id,
            'country_id' => $currentSchool->school->country_id,
            'activated_at' => now(),
            'expires_at' => $subscriptionExpiresAt,
            'actorable_id' => $student->id,
            'actorable_type' => Student::class,
        ]);

        $code->update([
            'used' => true,
            'used_at' => now(),
            'used_by' => $student->id,
        ]);

        $student->update([
            'sub_status' => 'subscribed',
            'subscribed_at' => now(),
            'subscription_expires_at' => $subscriptionExpiresAt,
        ]);

        $student->notify(new StudentSubscribedNotification(
            $subscriptionExpiresAt->toDateString()
        ));

        return [
            'message' => 'Student account activated successfully.',
            'activation' => $codeUsage,
            'student' => $student->fresh(),
            'expires_at' => $subscriptionExpiresAt,
        ];
    }
    public function activateTeacherAccount($data, $currentSchool)
    {
        $teacher = Teacher::where('school_branch_id', $currentSchool->id)
            ->find($data['teacher_id']);

        if (!$teacher) {
            throw new AppException(
                "Teacher Not Found",
                404,
                "Teacher Not Found",
                "The teacher account could not be found. Please ensure it has not been deleted."
            );
        }

        $activeUsage = ActivationCodeUsage::where('actorable_id', $teacher->id)
            ->where('actorable_type', Teacher::class)
            ->where('expires_at', '>', now())
            ->first();

        if ($activeUsage) {
            throw new AppException(
                "Active Subscription Exists",
                409,
                "Already Subscribed",
                "This teacher already has an active subscription. Only one active subscription is allowed per teacher."
            );
        }

        $code = ActivationCode::where('school_branch_id', $currentSchool->id)
            ->where('code', $data['activation_code'])
            ->where('status', 'active')
            ->where('used', false)
            ->first();

        if (!$code) {
            throw new AppException(
                "Invalid or Unavailable Code",
                404,
                "Code Not Found",
                "The activation code '{$data['activation_code']}' is invalid, already used, inactive, or not found. Please check and try again."
            );
        }

        if ($code->expires_at <= now()) {
            throw new AppException(
                "Code Expired",
                409,
                "Code Expired",
                "The activation code '{$data['activation_code']}' has expired. Please use a valid, non-expired code."
            );
        }

        $subscriptionExpiresAt = now()->addDays($code->duration);

        $codeUsage = ActivationCodeUsage::create([
            'school_branch_id' => $currentSchool->id,
            'activation_code_id' => $code->id,
            'country_id' => $currentSchool->school->country_id,
            'activated_at' => now(),
            'expires_at' => $subscriptionExpiresAt,
            'actorable_id' => $teacher->id,
            'actorable_type' => Teacher::class,
        ]);

        $code->update([
            'used' => true,
            'used_at' => now(),
            'used_by' => $teacher->id,
        ]);

        $teacher->update([
            'sub_status' => 'subscribed',
            'subscribed_at' => now(),
            'subscription_expires_at' => $subscriptionExpiresAt,
        ]);

        $teacher->notify(new TeacherSubscribedNotification($subscriptionExpiresAt));
        return [
            'message' => 'Teacher account activated successfully.',
            'activation' => $codeUsage,
            'teacher' => $teacher->fresh(),
            'expires_at' => $subscriptionExpiresAt,
        ];
    }
    public function getActivationCodeUsage($currentSchool)
    {
        return ActivationCodeUsage::where('school_branch_id', $currentSchool->id)
            ->with(['actorable', 'activationCode'])
            ->get()
            ->map(fn(ActivationCodeUsage $usage) => [
                'id'            => $usage->id,
                'account_type'  => str($usage->actorable_type)
                    ->afterLast('\\')
                    ->lower()
                    ->value() ?: 'unknown',
                'user_name'     => $usage->actorable?->name ?? '—',
                "code" => $usage->activationCode->code ?? '',
                'activated_date' => $usage->activated_at?->toDateTimeString(),
                'expires_at'    => $usage->expires_at?->toDateTimeString(),
                'status'        => $usage->expires_at
                    ? ($usage->expires_at->isFuture() ? 'active' : 'expired')
                    : 'no expiry',
            ])
            ->values()
            ->all();
    }
    public function getStudentActivationStatuses($currentSchool)
    {
        $activationStatus = Student::where("school_branch_id", $currentSchool->id)
            ->with(['activationCode.activationCode', 'specialty.level'])
            ->get();
        return $activationStatus->map(fn($student) => [
            "id" => $student->id,
            "student_name" => $student->name,
            "specialty_name" => $student->specialty->specialty_name,
            "level_name" => $student->specialty->level->name,
            "level" =>   $student->specialty->level->level,
            "sub_status" => $student->sub_status,
            "activation_code" => $student->activationCode->first()->activationCode->code ?? null
        ]);
    }
    public function getStudentSubscriptionDetail($currentSchool, $studentId)
    {
        $student = Student::where("school_branch_id", $currentSchool->id)
            ->with(['activationCode.activationCode', 'specialty.level'])
            ->find($studentId);
        if (!$student) {
            throw new AppException(
                "Student Not Found",
                404,
                "Student Not Found",
                "The student account could not be found. Please ensure it has not been deleted."
            );
        }
        return [
            "id" => $student->id,
            "student_name" => $student->name,
            "avatar" => $student->profile_picture ?? null,
            "specialty_name" => $student->specialty->specialty_name,
            "level_name" => $student->specialty->level->name,
            "level" =>   $student->specialty->level->level,
            "sub_status" => $student->sub_status,
            "activation_code" => $student->activationCode->first()->activationCode->code ?? null
        ];
    }
    public function getTeacherActivationStatuses($currentSchool)
    {
        $activationStatus = Teacher::where("school_branch_id", $currentSchool->id)
            ->with(['activationCode.activationCode'])
            ->get();
        return $activationStatus->map(fn($teacher) => [
            "id" => $teacher->id,
            "teacher_name" => $teacher->name,
            "first_name" => $teacher->first_name,
            "last_name" => $teacher->last_name,
            "avatar" => $teacher->profile_picture ?? null,
            "sub_status" => $teacher->sub_status,
            "activation_code" => $teacher->activationCode->first()->activationCode->code ?? null
        ]);
    }
    public function getTeacherSubscriptionDetail($currentSchool, $teacherId)
    {
        $teacher = Teacher::where("school_branch_id", $currentSchool->id)
            ->with(['activationCode.activationCode'])
            ->find($teacherId);
        if (!$teacher) {
            throw new AppException(
                "Teacher Not Found",
                404,
                "Teacher Not Found",
                "The teacher account could not be found. Please ensure it has not been deleted."
            );
        }
        return [
            "id" => $teacher->id,
            "teacher_name" => $teacher->name,
            "first_name" => $teacher->first_name,
            "last_name" => $teacher->last_name,
            "avatar" => $teacher->profile_picture ?? null,
            "sub_status" => $teacher->sub_status,
            "activation_code" => $teacher->activationCode->first()->activationCode->code ?? null
        ];
    }
}
