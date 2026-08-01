<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Jobs\Teacher\TeacherImportJob;
use App\Models\Job\SystemJobDetail;
use App\Models\Job\SystemJob;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\UpdateProfilePictureRequest;
use App\Http\Requests\Teacher\ImportTeacherRequest;
use App\Services\ApiResponseService;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use App\Http\Requests\Teacher\TeacherIdRequest;
use App\Models\Job\SystemJobCategory;
use App\Models\Teacher;
use App\Services\Teacher\TeacherService;

class TeacherController extends Controller
{
    protected TeacherService $teacherService;
    public function __construct(TeacherService $teacherService)
    {
        $this->teacherService = $teacherService;
    }

    public function getInstructors(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $getInstructorsBySchool = $this->teacherService->getAllTeachers($currentSchool);
        return ApiResponseService::success("Teacher Fetched Successfully", $getInstructorsBySchool, null, 200);
    }
    public function deleteInstructor(Request $request, string $teacherId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $deleteTeacher = $this->teacherService->deletetTeacher($teacherId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Teacher Deleted Sucessfully", $deleteTeacher, null, 200);
    }
    public function updateInstructor(UpdateTeacherRequest $request, string $teacherId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $updateTeacher = $this->teacherService->updateTeacher($request->all(), $teacherId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Teacher Updated Sucessfully", $updateTeacher, null, 200);
    }
    public function getTimettableByTeacher(Request $request, string $teacherId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $teacherId = $request->route('teacherId');
        $getTeacherSchedule = $this->teacherService->getTeacherSchedule($teacherId, $currentSchool);
        return ApiResponseService::success("Teacher Schedule Fetched And Generated Sucessfully", $getTeacherSchedule, null, 200);
    }
    public function getInstructorDetails(Request $request)
    {
        $teacherId = $request->route('teacherId');
        $teacherDetails = $this->teacherService->getTeacherDetails($teacherId);
        return ApiResponseService::success("Teacher Details Fetched Succesfully", $teacherDetails, null, 200);
    }
    public function deactivateTeacher(Request $request, string $teacherId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $deactivateTeacher = $this->teacherService->deactivateTeacher($teacherId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Teacher Account Deactivated Successfully", $deactivateTeacher, null, 200);
    }
    public function activateTeacher(Request $request, string $teacherId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $activateTeacher = $this->teacherService->activateTeacher($teacherId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Teacher Account Activated Successfully", $activateTeacher, null, 200);
    }

    public function bulkDeactivateTeacher(TeacherIdRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkDeactivateTeacher = $this->teacherService->bulkDeactivateTeacher($request->teacherIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Teacher Deactivated Successfully", $bulkDeactivateTeacher, null, 200);
    }
    public function bulkActivateTeacher(TeacherIdRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkActivateTeacher = $this->teacherService->bulkActivateTeacher($request->teacherIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Teacher Activated Successfully", $bulkActivateTeacher, null, 200);
    }
    public function bulkDeleteTeacher(TeacherIdRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkDeleteTeacher = $this->teacherService->bulkDeleteTeacher($request->teacherIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Teachers Deleted Successfully", $bulkDeleteTeacher, null, 200);
    }

    public function uploadProfilePicture(UpdateProfilePictureRequest $request)
    {
        $authTeacher = auth()->guard('teacher')->user();
        $updateProfilePicture = $this->teacherService->uploadProfilePicture($request, $authTeacher);
        return ApiResponseService::success("Profile Picture Uploaded Successfully", $updateProfilePicture, null, 200);
    }

    public function deleteProfilePicture(Request $request)
    {
        $authTeacher = auth()->guard('teacher')->user();
        $deleteProfilePicture = $this->teacherService->deleteProfilePicture($authTeacher);
        return ApiResponseService::success("Profile Picture Deleted Successfully", $deleteProfilePicture, null, 200);
    }

    public function getTeacherBySpecialtyPreference(Request $request, string $specialtyId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $getTeachersBySpecialty = $this->teacherService->getTeachersBySpecialtyPreference($specialtyId, $currentSchool);
        return ApiResponseService::success("Teachers Fetched Successfully", $getTeachersBySpecialty, null, 200);
    }

    public function importTeacher(ImportTeacherRequest $request)
    {
        $authUser = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $category = SystemJobCategory::where('name', 'teacher')->firstOrFail();

        $filePath = $request->file('file')->store(
            "imports/teachers/{$currentSchool->id}",
            'r2'
        );

        $payload = $request->validated();
        $payload['file_path'] = $filePath;
        unset($payload['file']);

        $systemJob = SystemJob::create([
            'type'              => 'teacher_import',
            'context_type'      => Teacher::class,
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
                'map'       => $payload['map'],
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

        TeacherImportJob::dispatch(
            $authUser->id,
            $currentSchool->id,
            $category->id,
            $systemJob->id,
            $payload
        );

        return ApiResponseService::success(
            'Teacher Importation Process Initiated Successfully',
            null,
            null,
            200
        );
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
