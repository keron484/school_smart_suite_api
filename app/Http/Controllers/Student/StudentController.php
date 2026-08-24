<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfilePictureRequest;
use Illuminate\Http\Request;
use App\Services\Student\StudentService;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Requests\Student\BulkAddStudentDropoutRequest;
use App\Http\Requests\Student\BulkUpdateStudentRequest;
use App\Http\Requests\Student\ImportStudentRequest;
use App\Http\Requests\Student\StudentIdRequest;
use App\Http\Resources\Student\StudentResource;
use App\Jobs\Student\StudentImportJob;
use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobCategory;
use App\Models\Job\SystemJobDetail;
use App\Models\Student;
use App\Services\ApiResponseService;
use Throwable;

class StudentController extends Controller
{
    protected StudentService $studentService;
    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }
    public function getStudentProfileDetails(Request $request, string $studentId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $profileDetails = $this->studentService->getStudentProfileDetails($currentSchool, $studentId);
        return ApiResponseService::success("Student Profile Details Fetched Successfully", $profileDetails, null, 200);
    }
    public function getStudents(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $getStudents = $this->studentService->getStudents($currentSchool);
        return ApiResponseService::success("Student Fetched Successfully", StudentResource::collection($getStudents), null, 200);
    }
    public function deleteStudent(Request $request, string $studentId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $deleteStudent = $this->studentService->deleteStudent($studentId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Student Deleted Successfully", $deleteStudent, null, 200);
    }
    public function updateStudent(UpdateStudentRequest $request, string $studentId)
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

    public function importStudents(ImportStudentRequest $request)
    {
        $authUser = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $category = SystemJobCategory::where('name', 'Student')->firstOrFail();

        $filePath = $request->file('file')->store(
            "imports/student/{$currentSchool->id}",
            'r2'
        );

        $payload = $request->validated();
        $payload['file_path'] = $filePath;
        unset($payload['file']);

        $systemJob = SystemJob::create([
            'type'              => 'student_import',
            'context_type'      => Student::class,
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

        StudentImportJob::dispatch(
            $authUser->id,
            $currentSchool->id,
            $category->id,
            $systemJob->id,
            $payload
        );

        return ApiResponseService::success(
            'Student Importation Process Initiated Successfully',
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
