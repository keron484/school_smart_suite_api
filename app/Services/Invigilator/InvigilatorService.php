<?php

namespace App\Services\Invigilator;

use App\Exceptions\AppException;
use App\Models\ExamTimetable\ExamInvigilator;
use App\Models\Schooladmin;
use App\Models\Teacher;
use App\Models\Exam\Exam;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvigilatorService
{
    protected const TEACHER = "teacher";
    protected const SCHOOLADMIN = "school_admin";

    public function assignExamInvigilator(object $currentSchool, array $payload)
    {
        $examId = $payload['exam_id'] ?? null;
        $invigilators = $payload['invigilators'] ?? null;

        if (!$examId || !is_array($invigilators) || empty($invigilators)) {
            throw new AppException(
                "Missing required details",
                400,
                "Incomplete Request",
                "Some required information was not provided. Please fill in all fields and try again."
            );
        }

        $modelMap = [
            self::TEACHER => Teacher::class,
            self::SCHOOLADMIN => Schooladmin::class,
        ];

        $prepared = [];
        $seen = [];

        foreach ($invigilators as $index => $invigilator) {
            $actorId = $invigilator['actorable_id'] ?? null;
            $actorType = $invigilator['actorable_type'] ?? null;

            if (!$actorId || !$actorType) {
                throw new AppException(
                    "Missing required details",
                    400,
                    "Incomplete Request",
                    "Some required information was not provided. Please fill in all fields and try again."
                );
            }

            $normalizedType = strtolower($actorType);

            if (!in_array($normalizedType, [self::TEACHER, self::SCHOOLADMIN])) {
                throw new AppException(
                    "Invalid invigilator type",
                    400,
                    "Invalid Selection",
                    "One of the selected people cannot be assigned as an invigilator. Please choose a valid teacher or school admin."
                );
            }

            $actorModel = $modelMap[$normalizedType];

            $dedupeKey = $actorModel . '|' . $actorId;

            if (in_array($dedupeKey, $seen)) {
                throw new AppException(
                    "Duplicate invigilator in request",
                    400,
                    "Duplicate Selection",
                    "The same person was selected more than once. Please review your selections and try again."
                );
            }

            $seen[] = $dedupeKey;

            $prepared[] = [
                'actorable_id' => $actorId,
                'actorable_type' => $actorModel,
            ];
        }

        $actorIds = array_column($prepared, 'actorable_id');

        $teacherIds = [];
        $schoolAdminIds = [];

        foreach ($prepared as $item) {
            if ($item['actorable_type'] === Teacher::class) {
                $teacherIds[] = $item['actorable_id'];
            } else {
                $schoolAdminIds[] = $item['actorable_id'];
            }
        }

        $validTeacherIds = Teacher::whereIn('id', $teacherIds)
            ->where('school_branch_id', $currentSchool->id)
            ->pluck('id')
            ->all();

        $validSchoolAdminIds = Schooladmin::whereIn('id', $schoolAdminIds)
            ->where('school_branch_id', $currentSchool->id)
            ->pluck('id')
            ->all();

        $validIds = array_merge($validTeacherIds, $validSchoolAdminIds);

        foreach ($prepared as $item) {
            if (!in_array($item['actorable_id'], $validIds)) {
                throw new AppException(
                    "Invigilator not found",
                    404,
                    "Invigilator Unavailable",
                    "We couldn't find one of the selected people in your school. Please pick people from your school and try again."
                );
            }
        }

        $existing = ExamInvigilator::where('exam_id', $examId)
            ->where('school_branch_id', $currentSchool->id)
            ->get(['actorable_id', 'actorable_type'])
            ->map(fn($item) => $item->actorable_type . '|' . $item->actorable_id)
            ->all();

        foreach ($prepared as $item) {
            $key = $item['actorable_type'] . '|' . $item['actorable_id'];

            if (in_array($key, $existing)) {
                throw new AppException(
                    "Invigilator already assigned",
                    409,
                    "Already Assigned",
                    "One of the selected people is already an invigilator for this exam. Please choose someone else."
                );
            }
        }

        $now = now();

        $records = array_map(fn($item) => [
            'id' => Str::uuid(),
            'school_branch_id' => $currentSchool->id,
            'exam_id' => $examId,
            'actorable_id' => $item['actorable_id'],
            'actorable_type' => $item['actorable_type'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $prepared);

        DB::transaction(function () use ($records) {
            ExamInvigilator::insert($records);
        });

        return ExamInvigilator::where('exam_id', $examId)
            ->where('school_branch_id', $currentSchool->id)
            ->whereIn('actorable_id', $actorIds)
            ->get();
    }

    public function getAssignedInvigilatorsExamId(object $currentSchool, string $examId)
    {
        $examInvigilators = ExamInvigilator::with(['invigilatable'])
            ->where('exam_id', $examId)
            ->where('school_branch_id', $currentSchool->id)
            ->get();

        if ($examInvigilators->isEmpty()) {
            throw new AppException(
                "No invigilators found",
                404,
                "No Invigilators",
                "No invigilators have been assigned to this exam yet."
            );
        }

        $teachers = $examInvigilators
            ->filter(fn($item) => $item->actorable_type === Teacher::class)
            ->map(function ($invigilator) {
                return [
                    'id' => $invigilator->id,
                    'exam_id' => $invigilator->exam_id,
                    'actorable_id' => $invigilator->actorable_id,
                    'actorable_type' => self::TEACHER,
                    'name' => $invigilator->invigilatable?->name,
                    'username' => $invigilator->invigilatable?->username,
                    'profile_picture' => $invigilator->invigilatable?->profile_picture,
                    'email' => $invigilator->invigilatable?->email,
                    "phone" =>$invigilator->invigilatable?->phone
                ];
            });

        $schoolAdmins = $examInvigilators
            ->filter(fn($item) => $item->actorable_type === Schooladmin::class)
            ->map(function ($invigilator) {
                return [
                    'id' => $invigilator->id,
                    'exam_id' => $invigilator->exam_id,
                    'actorable_id' => $invigilator->actorable_id,
                    'actorable_type' => self::SCHOOLADMIN,
                    'name' => $invigilator->invigilatable?->name,
                    'username' => $invigilator->invigilatable?->username,
                    'profile_picture' => $invigilator->invigilatable?->profile_picture,
                    'email' => $invigilator->invigilatable?->email,
                    "phone" =>$invigilator->invigilatable?->phone
                ];
            });

        return collect([
            'teachers' => $teachers->values(),
            'school_admins' => $schoolAdmins->values(),
        ])->reject(fn($group) => $group->isEmpty());
    }
    public function getPotentialInvigilators(object $currentSchool, string $examId)
    {
        $exam = Exam::where('id', $examId)
            ->where('school_branch_id', $currentSchool->id)
            ->first();

        if (!$exam) {
            throw new AppException(
                "Exam not found",
                404,
                "Exam Unavailable",
                "We couldn't find the exam you're looking for. Please try again."
            );
        }

        $assignedTeacherIds = ExamInvigilator::where('exam_id', $examId)
            ->where('school_branch_id', $currentSchool->id)
            ->where('actorable_type', Teacher::class)
            ->pluck('actorable_id')
            ->all();

        $assignedSchoolAdminIds = ExamInvigilator::where('exam_id', $examId)
            ->where('school_branch_id', $currentSchool->id)
            ->where('actorable_type', Schooladmin::class)
            ->pluck('actorable_id')
            ->all();

        $teachers = Teacher::where('school_branch_id', $currentSchool->id)
            ->whereNotIn('id', $assignedTeacherIds)
            ->get()
            ->map(function ($teacher) {
                return [
                    'actorable_id' => $teacher->id,
                    'actorable_type' => self::TEACHER,
                    'name' => $teacher->name,
                    "username" => $teacher->username,
                    "profile_picture" => $teacher->profile_picture,
                    "email" => $teacher->email,
                ];
            });

        $schoolAdmins = Schooladmin::where('school_branch_id', $currentSchool->id)
            ->whereNotIn('id', $assignedSchoolAdminIds)
            ->get()
            ->map(function ($admin) {
                return [
                    'actorable_id' => $admin->id,
                    'actorable_type' => self::SCHOOLADMIN,
                    'name' => $admin->name,
                    "username" => $admin->username,
                    "profile_picture" => $admin->profile_picture,
                    "email" => $admin->email,
                ];
            });

        return collect([
            'teachers' => $teachers->values(),
            'school_admins' => $schoolAdmins->values(),
        ])->reject(fn($group) => $group->isEmpty());
    }
    public function removeExamInvigilators(object $currentSchool, array $payload)
    {
        $examId = $payload['exam_id'] ?? null;
        $examInvigilatorIds = $payload['invigilator_ids'] ?? null;

        if (!$examId || !is_array($examInvigilatorIds) || empty($examInvigilatorIds)) {
            throw new AppException(
                "Missing required details",
                400,
                "Incomplete Request",
                "Some required information was not provided. Please fill in all fields and try again."
            );
        }

        $examInvigilatorIds = array_unique($examInvigilatorIds);

        $existing = ExamInvigilator::where('exam_id', $examId)
            ->where('school_branch_id', $currentSchool->id)
            ->whereIn('id', $examInvigilatorIds)
            ->pluck('id')
            ->all();

        if (count($existing) !== count($examInvigilatorIds)) {
            throw new AppException(
                "Invigilator not assigned",
                404,
                "Not Assigned",
                "One or more of the selected invigilators are not currently assigned to this exam."
            );
        }

        DB::transaction(function () use ($existing) {
            ExamInvigilator::whereIn('id', $existing)->delete();
        });

        return [
            'removed' => count($existing),
        ];
    }
}
