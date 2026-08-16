<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfilePictureRequest;
use App\Http\Resources\StudentResource;
use Exception;
use Illuminate\Http\Request;
use App\Services\Student\StudentService;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Requests\Student\BulkAddStudentDropoutRequest;
use App\Http\Requests\Student\BulkUpdateStudentRequest;
use App\Http\Requests\Student\StudentIdRequest;
use App\Services\ApiResponseService;
use Throwable;

class StudentController extends Controller
{
    protected StudentService $studentService;
    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }
    public function getStudentProfileDetails(Request $request, $studentId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $profileDetails = $this->studentService->getStudentProfileDetails($currentSchool, $studentId);
        return ApiResponseService::success("Student Profile Details Fetched Successfully", $profileDetails, null, 200);
    }
    public function getStudents(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $getStudents = $this->studentService->getStudents($currentSchool);
        return ApiResponseService::success("Student Fetched Succefully", $getStudents, null, 200);
    }
    public function deleteStudent(Request $request, $studentId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $deleteStudent = $this->studentService->deleteStudent($studentId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Deleted Successfully", $deleteStudent, null, 200);
    }
    public function updateStudent(UpdateStudentRequest $request, $studentId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $updateStudent = $this->studentService->updateStudent($studentId, $currentSchool, $request->all(), $authAdmin);
        return ApiResponseService::success('Student Updated Successfully', $updateStudent, null, 200);
    }
    public function getStudentDetails(Request $request, string $studentId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $studentDetails = $this->studentService->studentDetails($studentId, $currentSchool);
        return ApiResponseService::success("Student Details Fetched Successfully", $studentDetails, null, 200);
    }
    public function deactivateAccount(Request $request, string $studentId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $deactivateAccount = $this->studentService->deactivateStudentAccount($studentId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Account Deactivated Succesfully", $deactivateAccount, null, 200);
    }
    public function activateAccount(Request $request, string $studentId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $activateAccount = $this->studentService->activateStudentAccount($studentId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Account Activated Sucessfully", $activateAccount, null, 200);
    }
    public function markStudentAsDropout(Request $request, string $studentId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $markStudentAsDropout = $this->studentService->markStudentAsDropout($studentId, $currentSchool, $request->reason, $authAdmin);
        return ApiResponseService::success("Student Marked As Dropout Successfully", $markStudentAsDropout, null, 200);
    }
    public function getStudentDropoutList(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $studentDropoutList = $this->studentService->getAllDropoutStudents($currentSchool);
        return ApiResponseService::success("Student Dropout List Fetched Successfully", StudentResource::collection($studentDropoutList), null, 200);
    }
    public function reinstateDropedOutStudent(Request $request, string $studentDropoutId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $reinstateDropedOutStudent = $this->studentService->reinstateDropoutStudent($studentDropoutId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Reinstated Successfully", $reinstateDropedOutStudent, null, 200);
    }
    public function bulkReinstateDropedOutStudent(Request $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $this->studentService->bulkReinstateDropOutStudent($request->studentIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Reinstated Succesfully", null, null, 200);
    }
    public function bulkMarkStudentAsDropout(BulkAddStudentDropoutRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkMarkStudentAsDropout = $this->studentService->bulkMarkStudentAsDropOut($request->dropout_list, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Marked As Dropout Succesfully", $bulkMarkStudentAsDropout, null, 200);
    }
    public function bulkDeleteStudent(StudentIdRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkDeleteStudent = $this->studentService->bulkDeleteStudent($request->studentIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Deleted Successfully", $bulkDeleteStudent, null, 200);
    }
    public function bulkUpdateStudent(BulkUpdateStudentRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkUpdateStudent = $this->studentService->bulkUpdateStudent($request->students, $currentSchool, $authAdmin);
        return ApiResponseService::success("Students Updated Succesfully", $bulkUpdateStudent, null, 200);
    }
    public function bulkActivateStudent(StudentIdRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $bulkActivateStudent = $this->studentService->bulkActivateStudent($request->studentIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Activated Succesfully", $bulkActivateStudent, null, 200);
    }
    public function bulkDeactivateStudent(StudentIdRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $bulkDeactivateStudent = $this->studentService->bulkDeactivateStudent($request->studentIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Deactivated Successfully", $bulkDeactivateStudent, null, 200);
    }
    public function bulkReinstateStudentDropout(StudentIdRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $bulkReinstateStudent = $this->studentService->bulkReinstateStudent($request->studentIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Reinstated Succesfully", $bulkReinstateStudent, null, 200);
    }
    public function uploadProfilePicture(UpdateProfilePictureRequest $request)
    {
        try {
            $authStudent = auth()->guard('student')->user();
            $updateProfilePicture = $this->studentService->uploadProfilePicture($request, $authStudent);
            return ApiResponseService::success("Profile Picture Uploaded Successfully", $updateProfilePicture, null, 200);
        } catch (Throwable $e) {
            return ApiResponseService::error($e->getMessage(), null, 500);
        }
    }
    public function deleteProfilePicture(Request $request)
    {
        $authStudent = auth()->guard('student')->user();
        $deleteProfilePicture = $this->studentService->deleteProfilePicture($authStudent);
        return ApiResponseService::success("Profile Picture Deleted Successfully", $deleteProfilePicture, null, 200);
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
