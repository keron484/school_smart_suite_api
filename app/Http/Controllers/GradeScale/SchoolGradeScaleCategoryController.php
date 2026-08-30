<?php

namespace App\Http\Controllers\GradeScale;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ApiResponseService;
use App\Http\Resources\SchoolGradeConfigResource;
use App\Services\Grade\SchoolGradeConfigService;

class SchoolGradeScaleCategoryController extends Controller
{
    protected SchoolGradeConfigService $schoolGradesConfigService;
    public function __construct(SchoolGradeConfigService  $schoolGradesConfigService)
    {
        $this->schoolGradesConfigService = $schoolGradesConfigService;
    }

    public function getSchoolGradeScaleCategories(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $getSchoolGradesConfig =  $this->schoolGradesConfigService->getSchoolGradeScaleCategories($currentSchool);
        return ApiResponseService::success("School Grades Config fetched Succesfully",  SchoolGradeConfigResource::collection($getSchoolGradesConfig), null, 200);
    }

    public function getSchoolGradeScaleSchoolGradeCategoryId(Request $request, string $schoolGradeCategoryId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $gradingBySchoolGradeConfig = $this->schoolGradesConfigService->getGradeScaleSchoolGradeCategoryId($currentSchool, $schoolGradeCategoryId);
        return ApiResponseService::success("Grade Scale By School Grade Category Fetched Successfully", $gradingBySchoolGradeConfig, null, 200);
    }
}
