<?php

namespace App\Http\Controllers\GradeScale;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradesCategory\CreateGradesCategoryRequest;
use App\Http\Requests\GradesCategory\UpdateGradesCategoryRequest;
use App\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use App\Services\Grade\GradeCategoryService;

class GradeScaleCategoryController extends Controller
{
    protected GradeCategoryService $gradesCategoryService;
    public function __construct(GradeCategoryService $gradesCategoryService)
    {
        $this->gradesCategoryService = $gradesCategoryService;
    }
    public function createCategory(CreateGradesCategoryRequest $request): JsonResponse
    {
        $createCategory = $this->gradesCategoryService->createGradeCategory($request->validated());
        return ApiResponseService::success("Grades Category Created Succesfully", $createCategory, null, 201);
    }

    public function updateCategory(UpdateGradesCategoryRequest $request, string $categoryId): JsonResponse
    {
        $updateCategory = $this->gradesCategoryService->UpdateGradeCategory($request->validated(), $categoryId);
        return ApiResponseService::success("Grades Category Updated Succesfully", $updateCategory, null, 200);
    }

    public function deleteCategory(string $categoryId): JsonResponse
    {
        $deleteCategory = $this->gradesCategoryService->deleteGradeCategory($categoryId);
        return ApiResponseService::success("Grades Category Deleted Succesfully", $deleteCategory, null, 200);
    }

    public function getGradesCategory()
    {
        $getCategory = $this->gradesCategoryService->getGradesCategory();
        return ApiResponseService::success("Grades Categories Fetched Succesfully", $getCategory, null, 200);
    }
}
