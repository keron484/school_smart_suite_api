<?php

namespace App\Http\Controllers\Specialty;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ApiResponseService;
use App\Services\Specialty\SpecialtyService;
use App\Http\Requests\Specialty\CreateSpecialtyRequest;
use App\Http\Requests\Specialty\UpdateSpecialtyRequest;
use App\Http\Requests\Specialty\SpecialtyIdRequest;
use App\Http\Requests\Specialty\BulkUpdateSpecialtyRequest;
use App\Http\Requests\Specialty\ImportSpecialtyRequest;
use App\Http\Resources\SpecialtyResource;
use App\Jobs\Specialty\SpecialtyImportJob;
use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobCategory;
use App\Models\Job\SystemJobDetail;
use App\Models\Specialty;

class SpecialtyController extends Controller
{
    protected SpecialtyService $specialtyService;
    public function __construct(SpecialtyService $specialtyService)
    {
        $this->specialtyService = $specialtyService;
    }
    public function createSpecialty(CreateSpecialtyRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $createSpecailty = $this->specialtyService->createSpecialty($request->validated(), $currentSchool, $authAdmin);
        return ApiResponseService::success("specialty created sucessfully", $createSpecailty, null, 200);
    }
    public function deleteSpecialty(Request $request, string $specialtyId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $deleteSpecailty = $this->specialtyService->deleteSpecialty($currentSchool, $specialtyId, $authAdmin);
        return ApiResponseService::success("Specialty Deleted Sucessfully", $deleteSpecailty, null, 200);
    }
    public function updateSpecialty(UpdateSpecialtyRequest $request, string $specialtyId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $authAdmin = $this->resolveUser();
        $updateSpecailty = $this->specialtyService->updateSpecialty($request->validated(), $currentSchool, $specialtyId, $authAdmin);
        return ApiResponseService::success("Specialty Updated Sucessfully", $updateSpecailty, null, 200);
    }
    public function getSpecialtiesBySchoolBranch(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $getSpecialties = $this->specialtyService->getSpecialties($currentSchool);
        return ApiResponseService::success("Specialty Data Fetched Successfully",  SpecialtyResource::collection($getSpecialties), null, 200);
    }
    public function getSpecialtyDetails(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $specialtyId = $request->route('specialtyId');
        $specailtyDetails = $this->specialtyService->getSpecailtyDetails($currentSchool, $specialtyId);
        return ApiResponseService::success("specialty details fetched succefully", $specailtyDetails, null, 200);
    }
    public function activateSpecialty(Request $request, string $specialtyId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $activateSpecialty = $this->specialtyService->activateSpecialty($specialtyId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Specialty Activated Successfully", $activateSpecialty, null, 200);
    }
    public function deactivateSpecialty(Request $request, string $specialtyId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $deactivateSpecialty = $this->specialtyService->deactivateSpecialty($specialtyId, $currentSchool, $authAdmin);
        return ApiResponseService::success("Specialty Deactivated Successfully", $deactivateSpecialty, null, 200);
    }
    public function bulkDeactivateSpecialty(SpecialtyIdRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkDeactivateSpecialty = $this->specialtyService->bulkDeactivateSpecialty($request->specialtyIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Specialty Deactived Succesfully", $bulkDeactivateSpecialty, null, 200);
    }
    public function bulkActivateSpecialty(SpecialtyIdRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkActivateSpecialty = $this->specialtyService->bulkActivateSpecialty($request->specialtyIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Specialty activated Succesfully", $bulkActivateSpecialty, null, 200);
    }
    public function bulkDeleteSpecialty(SpecialtyIdRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkDeleteSpecialty = $this->specialtyService->bulkDeleteSpecialty($request->specialtyIds, $currentSchool, $authAdmin);
        return ApiResponseService::success("Specialty Deleted Succesfully", $bulkDeleteSpecialty, null, 200);
    }
    public function bulkUdateSpecialty(BulkUpdateSpecialtyRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkUpdateSpecialty = $this->specialtyService->bulkUpdateSpecialty($request->specialties, $currentSchool, $authAdmin);
        return ApiResponseService::success("Specialty Updated Successfully", $bulkUpdateSpecialty, null, 200);
    }

    public function getSpecialtyLevels(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $specialties = $this->specialtyService->getSpecialtyLevel($currentSchool);
        return ApiResponseService::success("Specialties By Level Fetched Successfully", $specialties, null, 200);
    }

    public function importSpecialty(ImportSpecialtyRequest $request)
    {
        $authUser = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $category = SystemJobCategory::where('name', 'specialty')->firstOrFail();

        $filePath = $request->file('file')->store(
            "imports/specialty/{$currentSchool->id}",
            'r2'
        );

        $payload = $request->validated();
        $payload['file_path'] = $filePath;
        unset($payload['file']);

        $systemJob = SystemJob::create([
            'type'              => 'specialty_import',
            'context_type'      => Specialty::class,
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

        SpecialtyImportJob::dispatch(
            $authUser->id,
            $currentSchool->id,
            $category->id,
            $systemJob->id,
            $payload
        );

        return ApiResponseService::success(
            'Specialty Importation Process Initiated Successfully',
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
