<?php

namespace App\Services\Course;

use App\Exceptions\AppException;
use App\Models\Courses;
use App\Models\SchoolSemester;
use App\Models\Specialty;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use App\Events\Actions\AdminActionEvent;
use App\Events\Actions\StudentActionEvent;
// use App\Events\Analytics\OperationalAnalyticsEvent;
// use App\Constant\Analytics\Operational\OperationalAnalyticsEvent as OperationalEvent;
use App\Models\Course\CourseSpecialty;

class CourseService
{
    public function createCourse(array $data, object $currentSchool, object $authAdmin): Courses
    {
        $courses = Courses::where("school_branch_id", $currentSchool->id)
            ->where("course_code", $data['course_code'])
            ->where("course_title", $data['course_title'])
            ->first();
        if ($courses) {
            throw new AppException(
                "It looks like you already have a course with this title and code",
                400,
                "Duplicate Course",
                "Please choose a different title or code for this course.",
                '/courses'
            );
        }
        $course = new Courses();
        $course->course_code = $data['course_code'];
        $course->course_title = $data['course_title'];
        $course->credit = $data['credit'];
        $course->school_branch_id = $currentSchool->id;
        $course->semester_id = $data['semester_id'];
        $course->description = $data['description'] ?? null;
        $course->save();

        if (!empty($data['typeIds'])) {
            $syncData = collect($data['typeIds'])
                ->pluck('type_id')
                ->mapWithKeys(fn($typeId) => [
                    $typeId => ['school_branch_id' => $currentSchool->id],
                ])
                ->toArray();

            $course->types()->sync($syncData);
        }


        if (!empty($data['specialty_id'])) {
            $course->specialties()->sync([
                $data['specialty_id'] => [
                    'school_branch_id' => $currentSchool->id
                ]
            ]);
        }

        // AdminActionEvent::dispatch(
        //     [
        //         "permissions" =>  ["schoolAdmin.course.create"],
        //         "roles" => ["schoolSuperAdmin", "schoolAdmin"],
        //         "schoolBranch" =>  $currentSchool->id,
        //         "feature" => "courseManagement",
        //         "action" => "course.created",
        //         "authAdmin" => $authAdmin,
        //         "data" => $course,
        //         "message" => "Course Created",
        //     ]
        // );
        // StudentActionEvent::dispatch([
        //     'schoolBranch'  => $currentSchool->id,
        //     'specialtyIds'  => [$specialty->id],
        //     'feature'       => 'courseCreate',
        //     'message'       => "New Course Created",
        //     'data'          => $course,
        // ]);
        // event(new OperationalAnalyticsEvent(
        //     eventType: OperationalEvent::COURSE_CREATED,
        //     version: 1,
        //     payload: [
        //         "school_branch_id" => $currentSchool,
        //         "specialty_id" => $specialty->id,
        //         "department_id" => $specialty->department_id,
        //         "level_id" => $specialty->level_id,
        //         "value" => 1
        //     ]
        // ));
        return $course;
    }
    public function deleteCourse(string $courseId, object $currentSchool, object $authAdmin)
    {
        $course = Courses::where("school_branch_id", $currentSchool->id)->find($courseId);
        if (!$course) {
            throw new AppException(
                "Course not found",
                404,
                "Course Not Found",
                "The course you are trying to delete does not exist or has already been deleted.",
                null
            );
        }
        $course->delete();
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.course.delete"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "courseManagement",
                "action" => "course.deleted",
                "authAdmin" => $authAdmin,
                "data" => $course,
                "message" => "Course Deleted",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch'  => $currentSchool->id,
            'specialtyIds'  => [$course->specialty_id],
            'feature'       => 'courseDelete',
            'message'       => "Course Deleted",
            'data'          => $course,
        ]);
        return $course;
    }
    public function bulkDeleteCourse(array $coursesIds, object $currentSchool, object $authAdmin)
    {
        $result = [];
        $specialtyIds = [];
        try {
            DB::beginTransaction();
            foreach ($coursesIds as $courseId) {
                $course = Courses::where("school_branch_id", $currentSchool->id)->findOrFail($courseId['course_id']);
                $course->delete();
                $result[] = $course;
                $specialtyIds[] = $course->specialty_id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.course.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "courseManagement",
                    "action" => "course.deleted",
                    "authAdmin" => $authAdmin,
                    "data" => $course,
                    "message" => "Course Deleted",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch'  => $currentSchool->id,
                'specialtyIds'  => $specialtyIds,
                'feature'       => 'courseDelete',
                'message'       => "Course Deleted",
                'data'          => $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new AppException(
                "One or more courses not found",
                404,
                "One or more courses not found",
                "One or more courses you are trying to delete do not exist or have already been deleted.",
                null
            );
        }
    }
    public function updateCourse(string $courseId, array $data, object $currentSchool, object $authAdmin)
    {
        $course = Courses::where("school_branch_id", $currentSchool->id)
            ->find($courseId);
        if (!$course) {
            throw new AppException(
                "Course not found please try again",
                404,
                "Course Not Found",
                "The course you are trying to update does not exist or has already been deleted.",
                null
            );
        }

        $filteredData = array_filter($data);
        if ($filteredData['course_code'] || $filteredData['course_title']) {
            $existingCourse = Courses::where("school_branch_id", $currentSchool->id)
                ->where(function ($query) use ($filteredData) {
                    if (isset($filteredData['course_code'])) {
                        $query->where('course_code', $filteredData['course_code']);
                    }
                    if (isset($filteredData['course_title'])) {
                        $query->where('course_title', $filteredData['course_title']);
                    }
                })
                ->where('id', '!=', $courseId)
                ->first();
            if ($existingCourse) {
                throw new AppException(
                    "It looks like you already have a course with this title and code",
                    400,
                    "Duplicate Course Details",
                    "Please choose a different title or code for this course.",
                    '/courses'
                );
            }
        }

        if (!empty($updateData['typeIds'])) {
            $syncData = collect($data['typeIds'])
                ->pluck('type_id')
                ->mapWithKeys(fn($typeId) => [
                    $typeId => ['school_branch_id' => $currentSchool->id],
                ])
                ->toArray();

            $course->types()->sync($syncData);
        }

        $course->update($filteredData);
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.course.update"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "courseManagement",
                "action" => "course.updated",
                "authAdmin" => $authAdmin,
                "data" => $course,
                "message" => "Course Updated",
            ]
        );

        StudentActionEvent::dispatch([
            'schoolBranch'  => $currentSchool->id,
            'specialtyIds'  => [$course->specialtyId],
            'feature'       => 'courseUpdate',
            'message'       => "Course Updated",
            'data'          => $course,
        ]);
        return $course;
    }
    public function bulkUpdateCourse(array $updateCourseList, object $currentSchool, object $authAdmin)
    {
        $result = [];
        $specialtyIds = [];
        try {
            DB::beginTransaction();
            foreach ($updateCourseList as $updateCourse) {
                $course = Courses::where("school_branch_id", $currentSchool->id)->findOrFail($updateCourse['course_id']);
                $filteredData = array_filter($updateCourse);
                $course->update($filteredData);
                $result[] = $course;
                $specialtyIds[] = $course->specialty_id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.course.update"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "courseManagement",
                    "action" => "course.updated",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "Course Updated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch'  => $currentSchool->id,
                'specialtyIds'  => [$specialtyIds],
                'feature'       => 'courseUpdate',
                'message'       => "Course Updated",
                'data'          => $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new AppException(
                "An unexpected error occurred. Please try again later.",
                500,
                "Server Error",
                "We were unable to complete the Update due to a server error.",
                "/courses"
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new AppException(
                "One or more courses not found",
                404,
                "One or more courses not found",
                "One or more courses you are trying to update do not exist or have already been deleted.",
                "/courses"
            );
        }
    }
    public function getCourses(object $currentSchool)
    {
        $courses = Courses::where("school_branch_id", $currentSchool->id)
            ->with(['specialties.level', 'semester', 'types'])
            ->withCount('specialties')
            ->having('specialties_count', '=', 1)
            ->get();


        if ($courses->isEmpty()) {
            throw new AppException(
                "No courses found",
                404,
                "No Courses Found",
                "There are no courses available for this school branch.",
                "/courses"
            );
        }

        return $courses->map(fn($course) => [
            "id" => $course->id ?? null,
            "course_code" => $course->course_code ?? null,
            "course_title" => $course->course_title ?? null,
            "course_credit" => $course->credit ?? null,
            'course_description' => $course->description ?? null,
            "semester_title" => $course->semester->name ?? null,
            "semester_count" => $course->semester->count ?? null,
            "specialty_name" => $course->specialties->first()->specialty_name ?? null,
            "level_name" => $course->specialties->first()->level->name ?? null,
            "level_number" => $course->specialties->first()->level->level ?? null,
            "status" => $course->status ?? null,
            "created_at" => $course->created_at ?? null,
            "updated_at" => $course->updated_at ?? null
        ]);
    }
    public function courseDetails(string $courseId, object $currentSchool)
    {
        try {
            $course = Courses::where('school_branch_id', $currentSchool->id)
                ->with(['courseSpecialty.specialty.level', 'courseSpecialty.specialty.department', 'semester', 'types'])
                ->findorFail($courseId);
            return $course;
        } catch (ModelNotFoundException $e) {
            throw new AppException(
                "Course not found",
                404,
                "Course Not Found",
                "The course you are trying to view does not exist or has already been deleted.",
                null
            );
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected error occurred. Please try again later.",
                500,
                "Server Error",
                "We encountered an unexpected issue while retrieving the course details.",
                null
            );
        }
    }
    public function getCoursesBySpecialtySemesterAndLevel(object $currentSchool, string $specialtyId,  string $semesterId)
    {
        try {

            $coursesData = CourseSpecialty::where("school_branch_id", $currentSchool->id)
                ->where("semester_id", $semesterId)
                ->where("specialty_id", $specialtyId)
                ->with(['types', 'course.semester'])
                ->whereHas('course', function ($query) {
                    $query->where('status', 'active');
                })
                ->pluck('course');

            if ($coursesData->isEmpty()) {
                throw new AppException(
                    "No courses found for the specified specialty, semester, and level.",
                    404,
                    "No Courses Found",
                    "There are no courses available for the selected specialty, semester, and level combination.",
                    "/courses"
                );
            }
            return $coursesData;
        } catch (AppException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected error occurred. Please try again later.",
                500,
                "Server Error",
                "We encountered an unexpected issue while retrieving the course details.",
                "/details"
            );
        } catch (ModelNotFoundException $e) {
            throw new AppException(
                "Specialty Not Found",
                404,
                "Specialty Not Found",
                "The Selected Specialty Was Not Found, It must have been deleted or removed",
                "/specialty"
            );
        }
    }
    public function deactivateCourse(object $currentSchool, string $courseId, object $authAdmin)
    {
        $course = Courses::where("school_branch_id", $currentSchool->id)->find($courseId);
        if ($course->status === "inactive") {
            throw new AppException("Course is already inactive", 400, "Course Already Inactive", "The course you are trying to deactivate is already inactive.", null);
        }
        if (!$course) {
            throw new AppException("Course not found", 404, "Course Not Found", "The course you are trying to deactivate does not exist or has already been deleted.", null);
        }
        $course->status = "inactive";
        $course->save();
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.course.deactivate"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "courseManagement",
                "action" => "course.deactivated",
                "authAdmin" => $authAdmin,
                "data" => $course,
                "message" => "Course Deactivated",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch'  => $currentSchool->id,
            'specialtyIds'  => [$course->specialty_id],
            'feature'       => 'courseDeactivate',
            'message'       => "Course Deactivated",
            'data'          => $course,
        ]);

        return $course;
    }
    public function bulkDeactivateCourse(array $coursesIds, object $currentSchool, object $authAdmin)
    {
        $result = [];
        $specialtyIds = [];
        try {
            DB::beginTransaction();
            foreach ($coursesIds as $courseId) {
                $course = Courses::where("school_branch_id", $currentSchool->id)->findOrFail($courseId['course_id']);
                $course->status = 'inactive';
                $course->save();
                $result[] = [
                    $course
                ];
                $specialtyIds[] = $course->specialty_id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.course.deactivate"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "courseManagement",
                    "action" => "course.deactivated",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "Course Deactivated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch'  => $currentSchool->id,
                'specialtyIds'  => $specialtyIds,
                'feature'       => 'courseDeactivate',
                'message'       => "Course Deactivated",
                'data'          => $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new AppException(
                "An unexpected error occurred. Please try again later.",
                500,
                "Server Error",
                "We encountered an unexpected issue while trying to deactivate courses.",
                "/courses"
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new AppException(
                "One or more courses not found",
                404,
                "One or more courses not found",
                "One or more courses you are trying to update do not exist or have already been deleted.",
                "/courses"
            );
        }
    }
    public function activateCourse(object $currentSchool, string $courseId, object $authAdmin)
    {
        $course = Courses::where("school_branch_id", $currentSchool->id)->find($courseId);
        if ($course->status === "active") {
            throw new AppException("Course is already active", 400, "Course Already Active", "The course you are trying to activate is already active.", null);
        }
        if (!$course) {
            throw new AppException("Course not found", 404, "Course Not Found", "The course you are trying to activate does not exist or has already been deleted.", null);
        }
        $course->status = "active";
        $course->save();
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.course.activate"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "courseManagement",
                "action" => "course.activated",
                "authAdmin" => $authAdmin,
                "data" => $course,
                "message" => "Course Activated",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch'  => $currentSchool->id,
            'specialtyIds'  => [$course->specialty_id],
            'feature'       => 'courseActivate',
            'message'       => "Course Activated",
            'data'          => $course,
        ]);
        return $course;
    }
    public function bulkActivateCourse(array $courseIds, object $currentSchool, object $authAdmin)
    {
        $result = [];
        $specialtyIds = [];
        try {
            DB::beginTransaction();
            foreach ($courseIds as $courseId) {
                $course = Courses::where("school_branch_id", $currentSchool->id)
                    ->findOrFail($courseId['course_id']);
                $course->status = 'active';
                $course->save();
                $result[] = [
                    $course
                ];
                $specialtyIds = $course->specialty_id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.course.activate"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "courseManagement",
                    "action" => "course.activated",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "Course Deactivated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch'  => $currentSchool->id,
                'specialtyIds'  => $specialtyIds,
                'feature'       => 'courseActivate',
                'message'       => "Course Activated",
                'data'          => $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new AppException(
                "An unexpected error occurred. Please try again later.",
                500,
                "Server Error",
                "We encountered an unexpected issue while trying to activate courses.",
                "/courses"
            );
        }
    }
    public function getActiveCourses(object $currentSchool)
    {
        $courses = Courses::where("school_branch_id", $currentSchool->id)
            ->where("status", "active")
            ->with(['courseSpecialty.specialty.level', 'courseSpecialty.specialty.department', 'semester', 'types'])
            ->get();
        if ($courses->isEmpty()) {
            throw new AppException(
                "No Active Courses Found for this school Branch",
                404,
                "Courses Found",
                "No Courses Found it looks like some courses have been deleted or deactivated",
                "/courses"
            );
        }

        return $courses->map(fn($course) => [
            'id' => $course->id,
            'course_code' => $course->course_code,
            'course_title' => $course->course_title,
            'credit' => $course->credit,
            'specialty_name' => $course->courseSpecialty[0]->specialty->specialty_name ?? null,
            'specialty_id' => $course->courseSpecialty[0]->specialty->id ?? null,
            'department_name' => $course->courseSpecialty[0]->specialty->department->department_name ?? null,
            'semester_title' => $course->semester->name ?? null,
            'semester_id' => $course->semester->id ?? null,
            'level_name' => $course->courseSpecialty[0]->specialty->level->name ?? null,
            'level_number' => $course->courseSpecialty[0]->specialty->level->level ?? null,
            'status' => $course->status,
            "joint_course_status" => count($course->courseSpecialty) > 1 ? true : false
        ]);
    }
    public function getCoursesBySchoolSemester(object $currentSchool, string $semesterId, string $specialtyId)
    {
        try {
            $schoolSemester = SchoolSemester::findOrFail($semesterId);
            $specialty = Specialty::findOrFail($specialtyId);
            $courses = Courses::where("school_branch_id", $currentSchool->id)
                ->where("semester_id", $schoolSemester->semester_id)
                ->whereHas("courseSpecialty", function ($query) use ($specialty) {
                    $query->where("specialty_id", $specialty->id);
                })
                ->where("status", "active")
                ->with(['types', 'semester'])
                ->get();
            if ($courses->isEmpty()) {
                throw new AppException(
                    "No Active Courses Found for this school Branch",
                    404,
                    "Courses Found",
                    "No Courses Found it looks like some courses have been deleted or deactivated",
                    "/courses"
                );
            }
            return $courses;
        } catch (AppException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            throw new AppException(
                "Semester or Specialty Not Found",
                404,
                "Semester or Specialty Not Found",
                "The Selected Semester or Specialty Was Not Found, It must have been deleted or removed",
                "/details"
            );
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected error occurred. Please try again later.",
                500,
                "Server Error",
                "We encountered an unexpected issue while retrieving the course details.",
                "/details"
            );
        }
    }
    public function getAllCoursesByStudentId(object $currentSchool, string $studentId): array
    {
        $student = Student::where('school_branch_id', $currentSchool->id)
            ->find($studentId);

        if (!$student) {
            throw new AppException(
                "Student Not Found",
                404,
                "Student Retrieval Error",
                "The student ID {$studentId} could not be found in this school branch. Please verify the ID.",
                "/students"
            );
        }

        if (empty($student->specialty_id) || empty($student->level_id)) {
            throw new AppException(
                "Student Profile Incomplete",
                400,
                "Course Matching Failed",
                "The student's specialty or level information is missing, preventing course assignment lookup.",
                "/students/profile"
            );
        }

        $courses = CourseSpecialty::where("school_branch_id", $currentSchool->id)
            ->where("specialty_id", $student->specialty_id)
            ->whereHas('course', function ($query) {
                $query->where('status', 'active');
            })
            ->with(['course.types', 'course.semester'])
            ->pluck('course');

        if ($courses->isEmpty()) {
            throw new AppException(
                "No Active Courses Found",
                404,
                "Courses Not Configured",
                "No courses are currently configured for the student's specialty and level in this school branch.",
                "/courses/configuration"
            );
        }

        $groupedCourses = $this->groupAndFormatCourses($courses);

        if (empty($groupedCourses)) {
            throw new AppException(
                "Course Grouping Failed",
                500,
                "Internal Processing Error",
                "Courses were found, but the system failed to organize them by semester. Check the 'semester' relationship.",
                "/system/logs"
            );
        }

        return $groupedCourses;
    }
    public function getCoursesByStudentIdSemesterId(object $currentSchool, string $studentId, string $semesterId)
    {
        $student = Student::where('school_branch_id', $currentSchool->id)
            ->find($studentId);

        if (!$student) {
            throw new AppException(
                "Student Not Found",
                404,
                "Student Retrieval Error",
                "The student ID {$studentId} could not be found in this school branch. Please verify the ID.",
                "/students"
            );
        }

        if (empty($student->specialty_id) || empty($student->level_id)) {
            throw new AppException(
                "Student Profile Incomplete",
                400,
                "Course Matching Failed",
                "The student's specialty or level information is missing, preventing course assignment lookup.",
                "/students/profile"
            );
        }

        $courses = CourseSpecialty::where('school_branch_id', $currentSchool->id)
            ->where('specialty_id', $student->specialty_id)
            ->whereHas('course', function ($query) use ($semesterId) {
                $query->where('semester_id', $semesterId);
            })
            ->where("status", "active")
            ->with(['course.types', 'semester'])
            ->pluck('course');

        if ($courses->isEmpty()) {
            throw new AppException(
                "No Active Courses Found",
                404,
                "Courses Not Configured",
                "No courses are currently configured for the student's specialty and level in this school branch.",
                "/courses/configuration"
            );
        }

        return $courses;
    }
    protected function groupAndFormatCourses(Collection $courses): array
    {
        $grouped = $courses->groupBy(function ($course) {
            return optional($course->semester)->name ?? 'Unassigned Semester';
        });

        $formatted = $grouped->map(function (Collection $coursesInSemester, string $semesterName) {
            $firstCourse = $coursesInSemester->first();
            $semesterId = optional($firstCourse)->semester_id;

            return [
                'semesterId' => $semesterId,
                'semester' => $semesterName,
                'courses' => $coursesInSemester->map(function ($course) {
                    return $course->only([
                        'id',
                        'course_code',
                        'course_title',
                        'credit',
                        'description',
                        'status'
                    ]);
                })->toArray(),
            ];
        })->values();

        return $formatted->toArray();
    }
    public function getCoursesGSemesterBspecialtyId(object $currentSchool, string $specialtyId)
    {
        $courses = Courses::where("school_branch_id", $currentSchool->id)
            ->whereHas('specialties', function ($query) use ($specialtyId) {
                $query->where('specialty_id', $specialtyId);
            })
            ->withCount('specialties') // Add count of specialties
            ->having('specialties_count', '=', 1) // Only courses with exactly one specialty
            ->with([
                'semester',
                'types',
                'teacherCoursePreference.teacher'
            ])
            ->get();

        $groupedBySemester = $courses->groupBy('semester_id')->map(function ($semesterCourses) {
            $semester = $semesterCourses->first()->semester;

            $formattedCourses = $semesterCourses->map(function ($course) {
                $teacherPreferences = $course->teacherCoursePreference;
                $isAssigned = $teacherPreferences->isNotEmpty();

                $formattedCourse = [
                    'course_id' => $course->id,
                    'course_code' => $course->course_code,
                    'course_title' => $course->course_title,
                    'course_credit' => $course->credit,
                    'description' => $course->description,
                    'types' => $course->types,
                    'assignment_status' => $isAssigned ? 'assigned' : 'unassigned'
                ];

                // Add all assigned teachers if any
                if ($isAssigned) {
                    $formattedCourse['teachers'] = $teacherPreferences->map(function ($preference) {
                        return [
                            'id' => $preference->teacher->id,
                            'name' => $preference->teacher->name,
                            'first_name' => $preference->teacher->first_name,
                            'profile_picture' => $preference->teacher->profile_picture,
                            'email' => $preference->teacher->email,
                            'username' => $preference->teacher->username ?? null
                        ];
                    })->values()->all();
                }

                return $formattedCourse;
            });

            return [
                'semester_id' => $semester->id,
                'semester_name' => $semester->name,
                'courses' => $formattedCourses->values()->all()
            ];
        })->values()->all();

        return $groupedBySemester;
    }
}
