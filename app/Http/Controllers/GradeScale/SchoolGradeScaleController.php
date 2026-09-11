<?php

namespace App\Http\Controllers\GradeScale;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeScale\BulkDeleteGradeScaleRequest;
use App\Jobs\GradeScale\GradeScaleImportJob;
use Illuminate\Http\Request;
use App\Services\Grade\GradeScaleService;
use App\Services\ApiResponseService;
use App\Http\Requests\GradeScale\ImportGradeScaleRequest;
use App\Http\Requests\GradeScale\BulkCopyGradeScaleRequest;
use App\Http\Requests\GradeScale\BulkUpdateGradeScaleCategoryRequest;
use App\Http\Requests\GradeScale\CreateGradeScaleRequest;
use App\Http\Requests\GradeScale\UpdateGradeScaleRequest;
use App\Models\GradeScale\SchoolGradeScale;
use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobCategory;
use App\Models\Job\SystemJobDetail;


class SchoolGradeScaleController extends Controller
{
    protected GradeScaleService  $gradeScaleService;
    public function __construct(
        GradeScaleService $gradeScaleService,
    ) {
        $this->gradeScaleService = $gradeScaleService;
    }


    public function bulkActivateGradeScale(BulkUpdateGradeScaleCategoryRequest $request)
    {

        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkActivateGradeScale = $this->gradeScaleService->bulkActivateGradeScaleCategories($currentSchool, $request->validated(), $authAdmin);
        return ApiResponseService::success("Grade Scales Activated Successfully", $bulkActivateGradeScale, null, 200);
    }
    public function bulkDeactivateGradeScale(BulkUpdateGradeScaleCategoryRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkDeactivateGradeScale = $this->gradeScaleService->bulkDeactivateGradeScaleCategories($currentSchool, $request->validated(), $authAdmin);
        return ApiResponseService::success("Grade Scales Deactivated Successfully", $bulkDeactivateGradeScale, null, 200);
    }
    public function getGradeScaleCategories(Request $request)
    {

        $currentSchool = $request->attributes->get('currentSchool');
        $gradeScaleCategories = $this->gradeScaleService->getGradeScaleCategories($currentSchool);
        return ApiResponseService::success("Grade Scale Categories Fetched Successfully", $gradeScaleCategories, null, 200);
    }
    public function getGradeScaleCategoryById(Request $request)
    {
        $categoryId = $request->route('categoryId');
        $configType = $request->configType ?? 'manual';
        $maxScore = $request->maxScore ?? 0;
        $currentSchool = $request->attributes->get('currentSchool');
        $gradeScaleCategory = $this->gradeScaleService->getGradeScaleCategoryById($currentSchool, $categoryId, $configType, (float) $maxScore);
        return ApiResponseService::success("Grade Scale Category Fetched Successfully", $gradeScaleCategory, null, 200);
    }
    public function bulkDeleteGradeScale(BulkDeleteGradeScaleRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $bulkDeleteGradeScale = $this->gradeScaleService->bulkDeleteGradeScalesByCategories($currentSchool, $request->validated(), $authAdmin);
        return ApiResponseService::success("Grade Scales Deleted Successfully", $bulkDeleteGradeScale, null, 200);
    }
    public function deleteGradeScale(Request $request, string $categoryId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $deleteGradeScale = $this->gradeScaleService->deleteGradeScale($currentSchool, $categoryId, $authAdmin);
        return ApiResponseService::success("Grade Scale Deleted Successfully", $deleteGradeScale, null, 200);
    }
    public function bulkCopyGradeScale(BulkCopyGradeScaleRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $copyGradeScale = $this->gradeScaleService->bulkCopyGradeScaleCategories($currentSchool, $request->validated(), $authAdmin);
        return ApiResponseService::success("Grade Scale Copied Successfully", $copyGradeScale, null, 200);
    }
    public function updateGradeScale(UpdateGradeScaleRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $updateGradeScale = $this->gradeScaleService->updateGradeScale($currentSchool, $request->validated(), $authAdmin);
        return ApiResponseService::success("Grade Scale Updated Successfully", $updateGradeScale, null, 200);
    }
    public function activateGradeScale(Request $request, string $categoryId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $activateGradeScale = $this->gradeScaleService->activateGradeScale($currentSchool, $categoryId);
        return ApiResponseService::success("Grade Scale Deactivated Successfully", $activateGradeScale, null, 200);
    }
    public function deactivateGradeScale(Request $request, string $categoryId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $deactivateGradeScale = $this->gradeScaleService->deactivateGradeScale($currentSchool, $categoryId);
        return ApiResponseService::success("Grade Scale Deactivated Successfully", $deactivateGradeScale, null, 200);
    }
    public function getActiveGradeScaleCategory(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $activeGradeScales = $this->gradeScaleService->getActiveGradeScaleCategories($currentSchool);
        return ApiResponseService::success("Active Grade Scale Categories Fetched Successfully", $activeGradeScales, null, 200);
    }
    public function getGradeScaleDetails(Request $request, string $categoryId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $gradeScaleDetails = $this->gradeScaleService->getGradeScaleDetails($currentSchool, $categoryId);
        return ApiResponseService::success("School Grade Scale Details Fetched Successfully", $gradeScaleDetails, null, 200);
    }
    public function createGradeScale(CreateGradeScaleRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $createGradeScales = $this->gradeScaleService->createGradeScale($request->validated(), $currentSchool, $authAdmin);
        return ApiResponseService::success("Exam Grade Scale Created Successfully", $createGradeScales, null, 201);
    }
    public function copyGradeScaleCategory(Request $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get("currentSchool");
        $sourceCategoryId = $request->route('sourceCategoryId');
        $targetCategoryId = $request->route('targetCategoryId');
        $createGrades = $this->gradeScaleService->copyGradeScaleCategory($currentSchool,  $sourceCategoryId,  $targetCategoryId, $authAdmin);
        return ApiResponseService::success("Exam Grades Added Successfully", $createGrades, null, 201);
    }
    public function importGradeScale(ImportGradeScaleRequest $request)
    {

        $authUser = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $category = SystemJobCategory::where('name', 'grade scale')->firstOrFail();

        $filePath = $request->file('file')->store(
            "imports/grade-scale/{$currentSchool->id}",
            'r2'
        );

        $payload = $request->validated();
        $payload['file_path'] = $filePath;
        unset($payload['file']);

        $systemJob = SystemJob::create([
            'type'              => 'grade_scale_import',
            'context_type'      => SchoolGradeScale::class,
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

        GradeScaleImportJob::dispatch(
            $authUser->id,
            $currentSchool->id,
            $category->id,
            $systemJob->id,
            $payload
        );

        return ApiResponseService::success(
            'Grade Scale Importation Process Initiated Successfully',
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
