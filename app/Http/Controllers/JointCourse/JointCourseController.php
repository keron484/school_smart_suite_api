<?php

namespace App\Http\Controllers\JointCourse;

use App\Http\Controllers\Controller;
use App\Http\Requests\JointCourse\CreateJointCourseRequest;
use App\Http\Requests\JointCourse\UpdateJointCourseRequest;
use App\Services\ApiResponseService;
use App\Services\JointCourse\JointCourseService;
use Illuminate\Http\Request;

class JointCourseController extends Controller
{
    protected JointCourseService $jointCourseService;
    public function __construct(JointCourseService $jointCourseService)
    {
        $this->jointCourseService = $jointCourseService;
    }
    public function createJointCourse(CreateJointCourseRequest $request)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $createCourse = $this->jointCourseService->createJointCourse($currentSchool, $request->validated(), $this->resolveUser());
        return ApiResponseService::success("Joint course created successfully", $createCourse, null, 201);
    }
    public function getJointCourses(Request $request)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $jointCourses = $this->jointCourseService->getJointCourses($currentSchool);
        return ApiResponseService::success("Joint courses retrieved successfully", $jointCourses);
    }

    public function updateJointCourse(UpdateJointCourseRequest $request, string $jointCourseId)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $updatedCourse = $this->jointCourseService->updateJointCourse(
            $request->validated(),
            $jointCourseId,
            $currentSchool
        );
        return ApiResponseService::success("Joint course updated successfully", $updatedCourse, null, 200);
    }

    public function deleteJointCourse(Request $request, string $jointCourseId)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $deletedCourse = $this->jointCourseService->deleteJointCourse($jointCourseId, $currentSchool, $this->resolveUser());
        return ApiResponseService::success("Joint course deleted successfully", $deletedCourse, null, 200);
    }

    public function getJointCourseDetails(Request $request, string $jointCourseId)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $courseDetails = $this->jointCourseService->getJointCourseDetails($jointCourseId, $currentSchool);
        return ApiResponseService::success("Joint course details retrieved successfully", $courseDetails, null, 200);
    }
    protected function resolveUser()
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
