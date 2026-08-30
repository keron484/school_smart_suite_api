<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CourseIdRequest;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Requests\Course\BulkUpdateCourseRequest;
use App\Http\Requests\Course\ImportCourseRequest;
use App\Http\Resources\CourseResource;
use App\Jobs\Course\CourseImportJob;
use App\Models\Courses;
use App\Services\Course\CourseService;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobCategory;
use App\Models\Job\SystemJobDetail;

class CourseController extends Controller
{
    protected CourseService $courseService;
    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }
    //
    public function createCourse(CreateCourseRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $course = $this->courseService->createCourse($request->validated(), $currentSchool, $authAdmin);
        return ApiResponseService::success('Course Created Succefully', $course, null, 201);
    }

    public function deleteCourse(Request $request, string $courseId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $deleteCourse = $this->courseService->deleteCourse($courseId, $currentSchool, $authAdmin);
        return  ApiResponseService::success('Course Deleted Succefully', $deleteCourse, null, 200);
    }

    public function updateCourse(UpdateCourseRequest $request, string $courseId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $updateCourse = $this->courseService->updateCourse($courseId, $request->validated(), $currentSchool, $authAdmin);
        return ApiResponseService::success('Course Update Succefully', $updateCourse, null, 200);
    }
    public function getCourses(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $courses = $this->courseService->getCourses($currentSchool);
        return ApiResponseService::success('Courses fetched succefully', $courses, null, 200);
    }
    public function getCourseDetails(Request $request)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $courseId = $request->route("courseId");
        $courseDetails = $this->courseService->courseDetails($courseId, $currentSchool);
        return ApiResponseService::success('Course Details fetched succefully', $courseDetails, null, 200);
    }
    public function getBySpecialtyLevelSemester(Request $request)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $specialtyId = $request->route("specialtyId");
        $semesterId = $request->route("semesterId");
        $coursesData = $this->courseService->getCoursesBySpecialtySemesterAndLevel($currentSchool, $specialtyId, $semesterId);
        return ApiResponseService::success(
            'Courses data fetched successfully',
            $coursesData,
            null,
            200
        );
    }
    public function activateCourse(Request $request, string $courseId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $activateCourse = $this->courseService->activateCourse($currentSchool, $courseId, $authAdmin);
        return ApiResponseService::success("Course Activated Succesfully", $activateCourse, null, 200);
    }
    public function deactivateCourse(Request $request, string $courseId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $deactivateCourse = $this->courseService->deactivateCourse($currentSchool, $courseId, $authAdmin);
        return ApiResponseService::success("Course Deactivated Succesfully", $deactivateCourse, null, 200);
    }
    public function getCoursesBySchoolSemester(Request $request, string $semesterId, string $specialtyId)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $courses = $this->courseService->getCoursesBySchoolSemester($currentSchool, $semesterId, $specialtyId);
        return ApiResponseService::success("Course By School Semester Fetched Succesfully", $courses, null, 200);
    }
    public function bulkUpdateCourse(BulkUpdateCourseRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $bulkUpdateCourse = $this->courseService->bulkUpdateCourse($request->courses, $currentSchool, $authAdmin);
        return ApiResponseService::success("Course Updated Succesfully", $bulkUpdateCourse, null, 200);
    }
    public function bulkDeleteCourse(CourseIdRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $bulkDelete = $this->courseService->bulkDeleteCourse($request->courseIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Course Deleted Successfully", $bulkDelete, null, 200);
    }
    public function bulkDeactivateCourse(CourseIdRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $bulkDeactivate = $this->courseService->bulkDeactivateCourse($request->courseIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Course Deactivated Successfully", $bulkDeactivate, null, 200);
    }
    public function bulkActivateCourse(CourseIdRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $bulkActivate = $this->courseService->bulkActivateCourse($request->courseIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Course Activated Successfully", $bulkActivate, null, 200);
    }
    public function getActiveCourses(Request $request)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $activeCourses =  $this->courseService->getActiveCourses($currentSchool);
        return ApiResponseService::success("Active Courses Fetched Successfully", $activeCourses, null, 200);
    }

    public function getAllCoursesByStudentId(Request $request, string $studentId)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $courses = $this->courseService->getAllCoursesByStudentId($currentSchool, $studentId);
        return ApiResponseService::success("Course Fetched Successfully", $courses, null, 200);
    }

    public function getCoursesByStudentIdSemesterId(Request $request)
    {
        $studentId = $request->route("studentId");
        $semesterId = $request->route("semesterId");
        $currentSchool = $request->attributes->get("currentSchool");
        $courses = $this->courseService->getCoursesByStudentIdSemesterId($currentSchool, $studentId, $semesterId);
        return ApiResponseService::success("Course Fetched Successfully", $courses, null, 200);
    }

    public function getCoursesBySpecialtySemester(Request $request)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $specialtyId = $request->route("specialtyId");
        $semesterId = $request->route("semesterId");
        $courses = Courses::where("school_branch_id", $currentSchool->id)
            ->where("specialty_id", $specialtyId)
            ->where("semester_id", $semesterId)
            ->get();
        return ApiResponseService::success("Courses By Specialty Semester Fetched Successfully", CourseResource::collection($courses), null, 200);
    }

    public function getCoursesGSemesterBspecialtyId(Request $request, string $specialtyId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $courses =  $this->courseService->getCoursesGSemesterBspecialtyId($currentSchool, $specialtyId);
        return ApiResponseService::success("Courses Grouped By Semester Fetched Successfully", $courses, null, 200);
    }

    public function importCourses(ImportCourseRequest $request)
    {
        $authUser = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $category = SystemJobCategory::where('name', 'Course')->firstOrFail();

        $filePath = $request->file('file')->store(
            "imports/course/{$currentSchool->id}",
            'r2'
        );

        $payload = $request->validated();
        $payload['file_path'] = $filePath;
        unset($payload['file']);

        $systemJob = SystemJob::create([
            'type'              => 'course_import',
            'context_type'      => Courses::class,
            'stage'             => 'Queued',
            'status'            => 'queued',
            'context_id'        => $currentSchool->id,
            'initiated_by_id'   => $authUser->id,
            'category_id'       => $category->id,
            'initiated_by_type' => $authUser::class,
            'queue'             => 'database',
            'started_at'        => now(),
        ]);

        SystemJobDetail::create([
            'job_id'           => $systemJob->id,
            'school_branch_id' => $currentSchool->id,
            'input'            => [
                'file_path' => $filePath,
                'mapping'       => $payload['mapping'],
                'original'  => $payload,
            ],
            'summary'          => null,
            'result'           => null,
            'metadata'         => [
                'last_broadcast_progress' => 0,
                'last_broadcast_status'   => null,
                'last_broadcast_at'       => null,
            ],
        ]);

        CourseImportJob::dispatch(
            $authUser->id,
            $currentSchool->id,
            $category->id,
            $systemJob->id,
            $payload
        );

        return ApiResponseService::success(
            'Course Importation Process Initiated Successfully',
            null,
            null,
            200
        );
    }

    protected function resolveUser(): ?object
    {
        foreach (['student', 'teacher', 'schooladmin'] as $guard) {
            $user = request()->user($guard);
            if ($user !== null) {
                return $user;
            }
        }
        return null;
    }
}
