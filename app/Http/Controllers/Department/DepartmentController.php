<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\DepartmentImportRequest;
use App\Services\Department\DepartmentService;
use App\Http\Resources\DepartmentResource;
use App\Http\Requests\Department\CreateDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Requests\Department\BulkUpdateDepartmentRequest;
use App\Services\ApiResponseService;
use App\Http\Requests\Department\ValidateDepartmentIdRequest;
use App\Jobs\Department\DepartmentImportJob;
use App\Models\Department;
use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobCategory;
use App\Models\Job\SystemJobDetail;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    protected DepartmentService $departmentService;
    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }
    public function createDepartment(CreateDepartmentRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $department = $this->departmentService->createDepartment($request->validated(), $currentSchool, $authAdmin);
        return ApiResponseService::success("Department Created Sucessfully", $department, null, 201);
    }
    public function deleteDepartment(Request $request, string $departmentId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $deleteDepartment = $this->departmentService->deleteDepartment($departmentId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Department Deleted successfully", $deleteDepartment, null, 200);
    }
    public function updateDepartment(UpdateDepartmentRequest $request, string $departmentId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $updateDepartment = $this->departmentService->updateDepartment($departmentId, $request->validated(), $currentSchool, $authAdmin);
        return ApiResponseService::success('Department updated sucessfully', $updateDepartment, null, 200);
    }
    public function getDepartments(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $getDepartments = $this->departmentService->getDepartments($currentSchool);
        return ApiResponseService::success('Departments fetched succefully', DepartmentResource::collection($getDepartments), null, 200);
    }
    public function getDepartmentDetails(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $departmentId = $request->route("departmentId");
        $departmentDetails = $this->departmentService->getDepartmentDetails($currentSchool, $departmentId);
        return ApiResponseService::success("Department Details Fetched Sucessfully", $departmentDetails, null, 200);
    }
    public function deactivateDepartment(Request $request, string $departmentId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $deactivateDepartment = $this->departmentService->deactivateDepartment($departmentId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Department Deactivated Sucessfully", $deactivateDepartment, null, 200);
    }
    public function activateDepartment(Request $request, string $departmentId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $activateDepartment = $this->departmentService->activateDepartment($departmentId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Department Activated Sucessfully", $activateDepartment, null, 200);
    }
    public function bulkDeactivateDepartment(ValidateDepartmentIdRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkDeactivateDepartment = $this->departmentService->bulkDeactivateDepartment($request->departmentIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Department Deactivated Succesfully", $bulkDeactivateDepartment, null, 200);
    }
    public function bulkActivateDepartment(ValidateDepartmentIdRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkActivateDepartment = $this->departmentService->bulkActivateDepartment($request->departmentIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Departments Activated Succesfully", $bulkActivateDepartment, null, 200);
    }
    public function bulkDeleteDepartment(ValidateDepartmentIdRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkDeleteDepartment = $this->departmentService->bulkDeleteDepartment($request->departmentIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Bulk Department Deleted Succesfully", $bulkDeleteDepartment, null, 200);
    }
    public function bulkUpdateDepartment(BulkUpdateDepartmentRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkUpdateDepartment = $this->departmentService->bulkUpdateDepartment($request->departments, $currentSchool, $authAdmin);
        return ApiResponseService::success("Departments Updated Succesfully", $bulkUpdateDepartment, null, 200);
    }

    public function importDepartment(DepartmentImportRequest $request)
    {
        $authUser = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $category = SystemJobCategory::where('name', 'Department')->firstOrFail();

        $filePath = $request->file('file')->store(
            "imports/department/{$currentSchool->id}",
            'r2'
        );

        $payload = $request->validated();
        $payload['file_path'] = $filePath;
        unset($payload['file']);

        $systemJob = SystemJob::create([
            'type'              => 'department_import',
            'context_type'      => Department::class,
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

        DepartmentImportJob::dispatch(
            $authUser->id,
            $currentSchool->id,
            $category->id,
            $systemJob->id,
            $payload
        );

        return ApiResponseService::success(
            'Department Importation Process Initiated Successfully',
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
