<?php

namespace App\Services\Course;

use App\Models\TeacherCoursePreference;
use App\Exceptions\AppException;
use App\Models\Courses;
use App\Models\Course\CourseSpecialty;
use Illuminate\Support\Facades\DB;
use App\Events\Actions\AdminActionEvent;
use Illuminate\Support\Str;
use App\Models\Teacher;
use App\Events\Analytics\OperationalAnalyticsEvent;
use App\Constant\Analytics\Operational\OperationalAnalyticsEvent as OperationalEvent;
use App\Models\Teacher\TeacherSpecialty;

class TeacherCoursePreferenceService
{
    public function assignTeacherCoursePreference(object $currentSchool, array $data,  $authAdmin): array
    {
        return DB::transaction(function () use ($currentSchool, $data, $authAdmin) {
            $schoolBranchId = $currentSchool->id;
            $teacherId      = $data['teacher_id'];
            $requestedCourses = collect($data['courseIds'] ?? []);

            if ($requestedCourses->isEmpty()) {
                throw new AppException(
                    "No Courses Provided",
                    400,
                    "No courses selected",
                    "Please select at least one course to assign."
                );
            }

            $requestedCourseIds = $requestedCourses->pluck('course_id')->unique()->values();

            $existing = TeacherCoursePreference::where('school_branch_id', $schoolBranchId)
                ->where('teacher_id', $teacherId)
                ->whereIn('course_id', $requestedCourseIds)
                ->exists();

            if ($existing) {
                throw new AppException(
                    "Duplicate Assignment",
                    409,
                    "Some courses already assigned",
                    "One or more selected courses are already assigned to this teacher."
                );
            }

            $teacher = Teacher::where('id', $teacherId)
                ->where('school_branch_id', $schoolBranchId)
                ->lockForUpdate()
                ->firstOrFail();

            $wasUnassigned = $teacher->course_assignment_status === 'unassigned';

            $allowedSpecialties = TeacherSpecialty::where('school_branch_id', $schoolBranchId)
                ->where('teacher_id', $teacherId)
                ->pluck('specialty_id')
                ->toArray();

            if (empty($allowedSpecialties)) {
                throw new AppException(
                    "No Teaching Preference",
                    403,
                    "Teacher has no specialty preference",
                    "This teacher has not set any preferred teaching specialties yet."
                );
            }

            $validCourseIds = Courses::where('school_branch_id', $schoolBranchId)
                ->with(['courseSpecialty', function ($query) use ($allowedSpecialties) {
                    $query->whereIn('specialty_id', $allowedSpecialties);
                }])
                ->whereIn('id', $requestedCourseIds)
                ->pluck('id')
                ->toArray();

            $invalidCourses = $requestedCourseIds->diff($validCourseIds);

            if ($invalidCourses->isNotEmpty()) {
                throw new AppException(
                    "Invalid Course Assignment",
                    403,
                    "Course-specialty mismatch",
                    "Some selected courses do not belong to the teacher's preferred teaching specialties."
                );
            }

            $insertData = $requestedCourseIds->map(function ($courseId) use ($teacherId, $schoolBranchId) {
                return [
                    'id'                => Str::uuid()->toString(),
                    'course_id'         => $courseId,
                    'teacher_id'        => $teacherId,
                    'school_branch_id'  => $schoolBranchId,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            })->toArray();

            TeacherCoursePreference::insert($insertData);



            // AdminActionEvent::dispatch([
            //     "permissions"  => ["schoolAdmin.teacherCoursePreference.assign"],
            //     "roles"        => ["schoolSuperAdmin", "schoolAdmin"],
            //     "schoolBranch" => $currentSchool->id,
            //     "feature"      => "teacherCoursePreferenceManagement",
            //     "action"       => "teacherCourse.assigned",
            //     "authAdmin"    => $authAdmin,
            //     "data"         => [
            //         'teacher_id'   => $teacher->id,
            //         'teacher_name' => $teacher->name ?? trim("{$teacher->first_name} {$teacher->last_name}"),
            //         'courses_assigned' => $assignedCount,
            //         'course_ids'   => $requestedCourseIds->toArray(),
            //     ],
            //     "message" => "Courses assigned to teacher successfully",
            // ]);
            foreach ($requestedCourseIds as $courseId) {
                event(new OperationalAnalyticsEvent(
                    eventType: OperationalEvent::TEACHER_COURSE_ASSIGNED,
                    version: 1,
                    payload: [
                        "school_branch_id" => $currentSchool->id,
                        "course_id" => $courseId,
                        "teacher_id" => $teacher->id,
                        "value" => 1
                    ]
                ));
            }


            return [
                'teacher_id'       => $teacherId,
                'school_branch_id' => $schoolBranchId,
                'status_updated'   => $wasUnassigned,
            ];
        });
    }
    public function getAssignableTeacherCourses(object $currentSchool, string $teacherId)
    {
        $schoolBranchId = $currentSchool->id;

        $preferredSpecialtyIds = TeacherSpecialty::where('school_branch_id', $schoolBranchId)
            ->where('teacher_id', $teacherId)
            ->pluck('specialty_id')
            ->toArray();

        if (empty($preferredSpecialtyIds)) {
            throw new AppException(
                "No Teaching Preference",
                403,
                "Teacher has no specialty preference",
                "This teacher has not set any preferred teaching specialties yet."
            );
        }

        $courses = Courses::where('courses.school_branch_id', $schoolBranchId)
            ->whereHas('courseSpecialty', function ($query) use ($preferredSpecialtyIds) {
                $query->whereIn('specialty_id', $preferredSpecialtyIds);
            })
            ->with([
                'courseSpecialty' => function ($query) use ($preferredSpecialtyIds) {
                    $query->whereIn('specialty_id', $preferredSpecialtyIds)
                        ->with(['specialty.level']);
                },
                'types'
            ])
            ->select('id', 'course_code', 'course_title', 'credit')
            ->whereNotExists(function ($query) use ($teacherId, $schoolBranchId) {
                $query->select(DB::raw(1))
                    ->from('teacher_course_preferences')
                    ->whereColumn('teacher_course_preferences.course_id', 'courses.id')
                    ->where('teacher_course_preferences.teacher_id', $teacherId)
                    ->where('teacher_course_preferences.school_branch_id', $schoolBranchId);
            });

        return $courses->get()->map(function ($course) {
            // Get the first matching course specialty (since we filtered by preferred specialties)
            $courseSpecialty = $course->courseSpecialty->first();
            $specialty = $courseSpecialty->specialty ?? null;
            $level = $specialty->level ?? null;

            return [
                'id' => $course->id,
                'course_code' => $course->course_code,
                'course_title' => $course->course_title,
                'credit' => $course->credit ?? 0,
                'specialty_name' => $specialty->specialty_name ?? 'N/A',
                'level_name' => $level->name ?? 'N/A',
                'type' => $course->types ?? null
            ];
        })->values();
    }
    public function removeTeacherAssignedCourses(object $currentSchool, array $data, $authAdmin): array
    {
        return DB::transaction(function () use ($currentSchool, $data, $authAdmin) {
            $schoolBranchId = $currentSchool->id;
            $teacherId      = $data['teacher_id'] ?? null;
            $requestedCourseIds = collect($data['courseIds'] ?? [])
                ->pluck('course_id')
                ->filter()
                ->unique()
                ->values();

            if (!$teacherId) {
                throw new AppException(
                    "Teacher ID is required",
                    400,
                    "Invalid Request",
                    "Teacher ID must be provided to remove course assignments."
                );
            }

            if ($requestedCourseIds->isEmpty()) {
                throw new AppException(
                    "No courses selected",
                    400,
                    "No Courses Selected",
                    "Please select at least one course to remove from the teacher."
                );
            }

            $teacher = Teacher::where('id', $teacherId)
                ->where('school_branch_id', $schoolBranchId)
                ->lockForUpdate()
                ->firstOrFail();

            $assignedCourseIds = DB::table('teacher_course_preferences')
                ->where('school_branch_id', $schoolBranchId)
                ->where('teacher_id', $teacherId)
                ->whereIn('course_id', $requestedCourseIds)
                ->pluck('course_id')
                ->toArray();

            if (empty($assignedCourseIds)) {
                throw new AppException(
                    "No assigned courses found",
                    404,
                    "Nothing to Remove",
                    "None of the selected courses are currently assigned to this teacher."
                );
            }

            $deletedCount = DB::table('teacher_course_preferences')
                ->where('school_branch_id', $schoolBranchId)
                ->where('teacher_id', $teacherId)
                ->whereIn('course_id', $assignedCourseIds)
                ->delete();

            $courseNames = Courses::whereIn('id', $assignedCourseIds)
                ->pluck('course_title', 'id')
                ->map(fn($name, $id) => "$name (ID: $id)")
                ->values()
                ->toArray();

            AdminActionEvent::dispatch([
                "permissions"  => ["schoolAdmin.teacherCoursePreference.remove"],
                "roles"        => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" => $currentSchool->id,
                "feature"      => "teacherCoursePreferenceManagement",
                "authAdmin"    => $authAdmin,
                "action" => "teacherCourse.removed",
                "data"         => [
                    'teacher_id'     => $teacher->id,
                    'teacher_name'   => $teacher->name ?? trim("{$teacher->first_name} {$teacher->last_name}"),
                    'removed_courses_count' => $deletedCount,
                    'removed_course_ids'    => $assignedCourseIds,
                    'removed_course_titles'  => $courseNames,
                ],
                "message" => "Courses removed from teacher successfully",
            ]);

            return [
                'teacher_id'              => $teacherId,
                'removed_courses_count'   => $deletedCount,
                'removed_course_ids'      => $assignedCourseIds,
                'school_branch_id'        => $schoolBranchId,
                'requested_count'         => $requestedCourseIds->count(),
                'status_updated_to_unassigned' => $teacher->wasChanged('course_assignment_status'),
            ];
        });
    }
    public function getAssignedTeacherCourses(object $currentSchool, string $teacherId)
    {
        $schoolBranchId = $currentSchool->id;

        $preferences = TeacherCoursePreference::where('school_branch_id', $schoolBranchId)
            ->where('teacher_id', $teacherId)
            ->with(['course.specialty', 'course.level'])
            ->get();

        if ($preferences->isEmpty()) {
            return [];
        }

        return $preferences->map(function ($preference) {
            $course = $preference->course;

            if (!$course) {
                return null;
            }

            return [
                'id'             => $course->id,
                'course_code'    => $course->course_code,
                'course_title'   => $course->course_title,
                'credit'         => $course->credit ?? 0,
                'specialty_name' => $course->specialty?->specialty_name ?? 'N/A',
                'level_name'     => $course->level?->name ?? 'N/A',
                'type'           => $course->types ?? null
            ];
        })->filter()->values();
    }
    public function changeTeacherForCourse(object $currentSchool, array $data): array
    {
        return DB::transaction(function () use ($currentSchool, $data) {
            $schoolBranchId = $currentSchool->id;
            $courseId = $data['course_id'];
            $newTeacherId = $data['new_teacher_id'];
            $oldTeacherId = $data['old_teacher_id'] ?? null;

            $course = Courses::where('id', $courseId)
                ->where('school_branch_id', $schoolBranchId)
                ->firstOrFail();

            $newTeacher = Teacher::where('id', $newTeacherId)
                ->where('school_branch_id', $schoolBranchId)
                ->lockForUpdate()
                ->firstOrFail();

            $courseSpecialties = CourseSpecialty::where('course_id', $courseId)
                ->pluck('specialty_id')
                ->toArray();

            $teacherSpecialties = TeacherSpecialty::where('school_branch_id', $schoolBranchId)
                ->where('teacher_id', $newTeacherId)
                ->pluck('specialty_id')
                ->toArray();

            $hasMatchingSpecialty = !empty(array_intersect($courseSpecialties, $teacherSpecialties));

            if (!$hasMatchingSpecialty) {
                throw new AppException(
                    "Specialty Mismatch",
                    403,
                    "Teacher cannot teach this course",
                    "The new teacher does not have the required specialty preferences for this course."
                );
            }

            $currentAssignments = TeacherCoursePreference::where('school_branch_id', $schoolBranchId)
                ->where('course_id', $courseId);

            if ($oldTeacherId) {
                $currentAssignments->where('teacher_id', $oldTeacherId);
            }

            $currentAssignmentsList = $currentAssignments->get();

            if ($currentAssignmentsList->isEmpty()) {
                throw new AppException(
                    "No Assignment Found",
                    404,
                    "Course has no assigned teacher",
                    "This course is not currently assigned to any teacher."
                );
            }

            if (!$oldTeacherId && $currentAssignmentsList->count() > 1) {
                throw new AppException(
                    "Multiple Teachers",
                    400,
                    "Course has multiple teachers",
                    "Please specify which teacher to replace using 'old_teacher_id'."
                );
            }

            $oldTeacherIdToUse = $oldTeacherId ?? $currentAssignmentsList->first()->teacher_id;


            $alreadyAssigned = TeacherCoursePreference::where('school_branch_id', $schoolBranchId)
                ->where('course_id', $courseId)
                ->where('teacher_id', $newTeacherId)
                ->exists();

            if ($alreadyAssigned) {
                throw new AppException(
                    "Already Assigned",
                    409,
                    "Teacher already assigned to this course",
                    "The new teacher is already assigned to this course."
                );
            }

            TeacherCoursePreference::where('school_branch_id', $schoolBranchId)
                ->where('course_id', $courseId)
                ->where('teacher_id', $oldTeacherIdToUse)
                ->delete();


            TeacherCoursePreference::create([
                'id' => Str::uuid()->toString(),
                'course_id' => $courseId,
                'teacher_id' => $newTeacherId,
                'school_branch_id' => $schoolBranchId,
            ]);
            return [
                'course_id' => $courseId,
                'old_teacher_id' => $oldTeacherIdToUse,
                'new_teacher_id' => $newTeacherId,
                'school_branch_id' => $schoolBranchId,
                'message' => 'Teacher changed successfully for the course'
            ];
        });
    }
}
