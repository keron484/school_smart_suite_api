<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\Specialty;
use App\Models\Teacher\TeacherSpecialty;
use App\Exceptions\AppException; // Assuming this is your custom Exception namespace
use Illuminate\Support\Facades\DB;
use Exception;

class TeacherSpecialtyService
{
    public function getTeacherSpecialtyTeacherId(object $currentSchool, string $teacherId)
    {
        try {
            $preferences = TeacherSpecialty::where("school_branch_id", $currentSchool->id)
                ->where("teacher_id", $teacherId)
                ->with([
                    'specialty:id,specialty_name,level_id',
                    'specialty.level:id,name,level' // Assuming relation 'level' exists on Specialty model
                ])
                ->get();

            if ($preferences->isEmpty()) {
                throw new AppException(
                    "No specialty preferences found for teacher ID '{$teacherId}' at school branch ID '{$currentSchool->id}'.",
                    404,
                    "No Preferences Assigned 🧑‍🏫",
                    "We couldn't find any defined specialty or subject preferences for this teacher. Please ensure their preferred teaching areas have been properly set up and saved.",
                    null
                );
            }

            $formatted = $preferences->map(function ($preference) {
                $specialty = $preference->specialty;
                $level = $specialty->level;

                return [
                    'id' => $preference->id,
                    'specialty_id' => $specialty->id,
                    'specialty_name' => $specialty->specialty_name,
                    'level_name' => $level?->name,
                    'level' => $level?->level,
                ];
            });

            return $formatted;
        } catch (AppException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected system error occurred while retrieving teacher specialties: " . $e->getMessage(),
                500,
                "Retrieval Failed 🛑",
                "Please check the query parameters and try again.",
                null
            );
        }
    }
    public function assignTeachers(object $currentSchool, array $payload)
    {
        $specialtyId = $payload['specialty_id'] ?? null;
        $teacherIds = $payload['teacher_ids'] ?? [];
        $schoolBranchId = $currentSchool->id ?? null;

        try {
            DB::beginTransaction();

            $specialty = Specialty::findOrFail($specialtyId);

            $syncData = [];
            foreach ($teacherIds as $teacherId) {
                if (!empty($teacherId)) {
                    $syncData[$teacherId] = [
                        'school_branch_id' => $schoolBranchId
                    ];
                }
            }

            $specialty->teachers()->syncWithoutDetaching($syncData);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new AppException(
                "Failed to assign teachers to the specialty: " . $e->getMessage(),
                500,
                "Assignment Error 🛑",
                "We encountered a database error while attempting to hook up these teachers to this specialty area. The changes have been discarded.",
                null
            );
        }
    }
    public function removeTeachers(object $currentSchool, array $payload)
    {
        $specialtyId = $payload['specialty_id'] ?? null;
        $teacherIds = $payload['teacher_ids'] ?? [];
        $schoolBranchId = $currentSchool->id ?? null;

        try {
            DB::beginTransaction();

            $specialty = Specialty::findOrFail($specialtyId);

            // Detach targets specifically linked with the current school branch constraint
            $specialty->teachers()
                ->wherePivot('school_branch_id', $schoolBranchId)
                ->detach($teacherIds);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new AppException(
                "Failed to detach teachers from the specialty area: " . $e->getMessage(),
                500,
                "Removal Error 🛑",
                "We were unable to detach the chosen instructors from this specific specialty selection.",
                null
            );
        }
    }
    public function getAssignableTeachers(object $currentSchool, string $specialtyId)
    {
        try {
            $specialty = Specialty::findOrFail($specialtyId);
            $requiredLevelId = $specialty->level_id;

            $teachers = Teacher::where('school_branch_id', $currentSchool->id)
                ->whereHas('levels', function ($query) use ($requiredLevelId) {
                    $query->where('levels.id', $requiredLevelId);
                })
                ->whereDoesntHave('specialties', function ($query) use ($specialtyId, $currentSchool) {
                    $query->where('specialty_id', $specialtyId)
                        ->where('teacher_specialty_preferences.school_branch_id', $currentSchool->id);
                })
                ->with(['specialties', 'teacherCoursePreference', 'qualifications', 'levels'])
                ->get();

            if ($teachers->isEmpty()) {
                throw new AppException(
                    "No eligible or unassigned teachers found for this specialty level at school branch ID '{$currentSchool->id}'.",
                    404,
                    "No Assignable Instructors Available 🧑‍🏫",
                    "We could not locate any alternative instructors who are authorized to teach this educational level and aren't already matched with this specialty choice.",
                    null
                );
            }

            return $teachers->map(function ($teacher) use ($currentSchool) {
                $numSpecialties = $teacher->specialties->count();
                $numCourses = $teacher->teacherCoursePreference->count();

                return [
                    'id' => $teacher->id,
                    'school_branch_id' => $currentSchool->id,
                    'name' => $teacher->name,
                    'first_name' => $teacher->first_name,
                    'last_name' => $teacher->last_name,
                    'profile_picture' => $teacher->profile_picture,
                    'username' => $teacher->username ?? null,
                    'address' => $teacher->address,
                    'qualifications' => $teacher->qualifications ?? null,
                    'levels' => $teacher->levels ?? null,
                    'num_assigned_courses' => $numCourses,
                    'course_assignment_status' => $numCourses > 0 ? 'assigned' : 'unassigned',
                    'num_assigned_specialties' => $numSpecialties,
                    'specialty_assignment_status' => $numSpecialties > 0 ? 'assigned' : 'unassigned',
                ];
            });
        } catch (AppException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AppException(
                "An error occurred while finding assignable teachers: " . $e->getMessage(),
                500,
                "Query Processing Error 🛑",
                "An underlying database error broke our filtering mechanism. Please verify system details and try again.",
                null
            );
        }
    }
}
