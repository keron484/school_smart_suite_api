<?php

namespace App\Http\Controllers\GradeScale;

use App\Http\Controllers\Controller;
use App\Jobs\GradeScale\GradeScaleImportJob;
use Illuminate\Http\Request;
use App\Http\Requests\Grade\AutoGenExamGradingRequest;
use App\Http\Requests\Grade\BulkConfigureByOtherGradesRequest;
use App\Http\Requests\Grade\BulkCreateGradeRequest;
use App\Http\Requests\Grade\BulkDeleteGradeConfigRequest;
use App\Services\Grade\GradeScaleService;
use App\Services\ApiResponseService;
use App\Services\Grade\AutoGenExamGradeScaleService;
use App\Http\Requests\Grade\CreateGradeRequest;
use App\Http\Requests\Grade\ImportGradeScaleRequest;
use App\Http\Requests\Grade\UpdateGradeRequest;
use App\Models\GradeScale\SchoolGradeScale;
use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobCategory;
use App\Models\Job\SystemJobDetail;


class SchoolGradeScaleController extends Controller
{
    protected GradeScaleService  $addGradesService;
    protected AutoGenExamGradeScaleService $autoGenExamGradingService;
    public function __construct(
        GradeScaleService $addGradesService,
        AutoGenExamGradeScaleService $autoGenExamGradingService
    ) {
        $this->addGradesService = $addGradesService;
        $this->autoGenExamGradingService = $autoGenExamGradingService;
    }

    public function updateExamGrades(UpdateGradeRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get("currentSchool");
        $this->addGradesService->updateGradeScale($request->grades, $currentSchool, $authAdmin);
        return ApiResponseService::success("Grades Updated Successfully");
    }

    public function bulkCreateExamGrades(BulkCreateGradeRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get("currentSchool");
        $this->addGradesService->bulkCreateGradeScale($request->validated(), $currentSchool, $authAdmin);
        return ApiResponseService::success("Grades Created Successfully", null, null, 200);
    }

    public function bulkDeleteGradesByGradeConfig(BulkDeleteGradeConfigRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get("currentSchool");
        $this->addGradesService->bulkDeleteGradesConfig($currentSchool, $request->validated(), $authAdmin);
        return ApiResponseService::success("Grades Deleted Successfully", null, null, 200);
    }

    public function bulkConfigureByOtherGradeConfig(BulkConfigureByOtherGradesRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get("currentSchool");
        $this->addGradesService->bulkConfigureByOtherScales($request->validated(), $currentSchool, $authAdmin);
        return ApiResponseService::success("Grades Configured Successfully", null, null, 200);
    }
    public function deleteGradeConfig(Request $request, string $configId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get("currentSchool");
        $this->addGradesService->deleteGradesConfig($currentSchool, $configId, $authAdmin);
        return ApiResponseService::success("School Grades Configuration Deleted Successfully", null, null, 200);
    }

    public function getGradeConfigDetails(Request $request, string $configId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $configDetails = $this->addGradesService->getGradeScaleCategoryId($currentSchool, $configId);
        return ApiResponseService::success("Grade Configuration Details Fetched Successfully", $configDetails, null, 200);
    }
    public function autoGenExamGrading(AutoGenExamGradingRequest $request)
    {
        $examGrading = $this->autoGenExamGradingService->autoGenerateExamGrading($request->validated());
        return ApiResponseService::success("Grading Generated Successfully", $examGrading, null, 200);
    }
    public function createExamGrades(CreateGradeRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get("currentSchool");
        $createGrades = $this->addGradesService->createGradeScale($request->grades, $currentSchool, $authAdmin);
        return ApiResponseService::success("Exam Grades Created Succefully", $createGrades, null, 201);
    }

    public function createGradesByOtherGrades(Request $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get("currentSchool");
        $configId = $request->route('configId');
        $targetConfigId = $request->route('targetConfigId');
        $createGrades = $this->addGradesService->configureByOtherScale($configId, $currentSchool, $targetConfigId, $authAdmin);
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
