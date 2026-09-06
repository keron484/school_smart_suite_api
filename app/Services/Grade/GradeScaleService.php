<?php

namespace App\Services\Grade;

use App\Models\GradeScale\SchoolGradeScale;
use App\Models\GradeScale\SchoolGradeScaleCategory;
use App\Models\GradeScale\Grade;
use Illuminate\Support\Facades\DB;
use Exception;
use Throwable;
use Illuminate\Support\Str;
use App\Exceptions\AppException;
use App\Events\Actions\AdminActionEvent;
use Illuminate\Support\Collection;
class GradeScaleService
{
    public function bulkActivateGradeScaleCategories(object $currentSchool, array $payload, object $authAdmin)
    {
        try {
            DB::beginTransaction();

            $categoryIds = collect($payload['grade_scale_category_ids'])->pluck('category_id')->toArray();

            $gradeScaleCategories = SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $categoryIds)
                ->get();

            if ($gradeScaleCategories->isEmpty()) {
                throw new AppException(
                    "Grade Scale Categories Not Found",
                    404,
                    "Configuration Not Found",
                    "The specified grade scale categories could not be found.",
                    null
                );
            }

            $alreadyActivated = $gradeScaleCategories->filter(function ($category) {
                return $category->status === 'activated';
            });

            if ($alreadyActivated->isNotEmpty()) {
                $ids = $alreadyActivated->pluck('id')->implode(', ');
                throw new AppException(
                    "Grade Scale Categories Already Activated",
                    422,
                    "Validation Error",
                    "The following grade scale categories are already activated: {$ids}. Please select only deactivated categories.",
                    null
                );
            }

            SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $categoryIds)
                ->update([
                    'status' => 'activated'
                ]);

            DB::commit();

            $updatedCategories = SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $categoryIds)
                ->get();

            // AdminActionEvent::dispatch(
            //     [
            //         "permissions" => ["schoolAdmin.grades.update"],
            //         "roles" => ["schoolSuperAdmin", "schoolAdmin"],
            //         "schoolBranch" => $currentSchool->id,
            //         "feature" => "gradeScaleManagement",
            //         "action" => "gradeScaleCategories.bulkActivated",
            //         "authAdmin" => $authAdmin,
            //         "data" => $updatedCategories,
            //         "message" => "Grade Scale Categories Bulk Activated Successfully",
            //     ]
            // );

            return $updatedCategories;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkDeactivateGradeScaleCategories(object $currentSchool, array $payload, object $authAdmin)
    {
        try {
            DB::beginTransaction();

            $categoryIds = collect($payload['grade_scale_category_ids'])->pluck('category_id')->toArray();

            $gradeScaleCategories = SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $categoryIds)
                ->get();

            if ($gradeScaleCategories->isEmpty()) {
                throw new AppException(
                    "Grade Scale Categories Not Found",
                    404,
                    "Configuration Not Found",
                    "The specified grade scale categories could not be found.",
                    null
                );
            }

            $alreadyDeactivated = $gradeScaleCategories->filter(function ($category) {
                return $category->status === 'deactivated';
            });

            if ($alreadyDeactivated->isNotEmpty()) {
                $ids = $alreadyDeactivated->pluck('id')->implode(', ');
                throw new AppException(
                    "Grade Scale Categories Already Deactivated",
                    422,
                    "Validation Error",
                    "The following grade scale categories are already deactivated: {$ids}. Please select only activated categories.",
                    null
                );
            }

            SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $categoryIds)
                ->update([
                    'status' => 'deactivated'
                ]);

            DB::commit();

            $updatedCategories = SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $categoryIds)
                ->get();

            // AdminActionEvent::dispatch(
            //     [
            //         "permissions" => ["schoolAdmin.grades.update"],
            //         "roles" => ["schoolSuperAdmin", "schoolAdmin"],
            //         "schoolBranch" => $currentSchool->id,
            //         "feature" => "gradeScaleManagement",
            //         "action" => "gradeScaleCategories.bulkDeactivated",
            //         "authAdmin" => $authAdmin,
            //         "data" => $updatedCategories,
            //         "message" => "Grade Scale Categories Bulk Deactivated Successfully",
            //     ]
            // );

            return $updatedCategories;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function getGradeScaleCategories(object $currentSchool)
    {
        $categories = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)
            ->with(['systemGradeCategory', 'schoolGradeScale.grade'])
            ->get();

        foreach ($categories as $category) {
            $category->is_configured = $category->schoolGradeScale->isNotEmpty();
        }

        return $categories->map(fn($cat) => [
            'id' => $cat->id,
            'grades_category_id' => $cat->grades_category_id,
            'grade_title' => $cat->systemGradeCategory->title ?? null,
            'exam_type' => $cat->systemGradeCategory->exam_type ?? null,
            'max_score' => $cat->max_score,
            'status' => $cat->status,
            'is_configured' => $cat->is_configured,
            'created_at' => $cat->created_at,
            'updated_at' => $cat->updated_at,
            'passing_grade_count' => collect($cat->schoolGradeScale)->where("result", "passed")->count() ?? null,
            'failing_grade_count' => collect($cat->schoolGradeScale)->where("result", "failed")->count() ?? null,
            'total_grade_scale' => collect($cat->schoolGradeScale)->count() ?? null,
            'grade_scale' =>  $cat->schoolGradeScale,
        ]);
    }
    public function getGradeScaleDetails(object $currentSchool, string $categoryId)
    {
        $gradeScale = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)
            ->with(['schoolGradeScale.grade', 'systemGradeCategory'])
            ->find($categoryId);

        if (!$gradeScale) {
            throw new AppException(
                "Grade Scale Category Not Found",
                404,
                "Configuration Not Found",
                "The specified school grades configuration could not be found.",
                null
            );
        }

        $gradeScale->is_configured = $gradeScale->schoolGradeScale->isNotEmpty();

        return $gradeScale;
    }
    public function getActiveGradeScaleCategories(object $currentSchool)
    {
        $categories = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)
            ->where("status", "active")
            ->with(['systemGradeCategory', 'schoolGradeScale.grade'])
            ->get();

        foreach ($categories as $category) {
            $category->is_configured = $category->schoolGradeScale->isNotEmpty();
        }

        return $categories;
    }
    public function activateGradeScale(object $currentSchool, string $categoryId)
    {
        $gradeScale = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)
            ->find($categoryId);

        if (!$gradeScale) {
            throw new AppException(
                "Grade Scale Category Not Found",
                404,
                "Configuration Not Found",
                "The specified school grades configuration could not be found.",
                null
            );
        }

        if ($gradeScale->status === "active") {
            throw new AppException(
                "Grade Scale Already Activated",
                422,
                "Validation Error",
                "This grade scale category is already activated. No action required.",
                null
            );
        }

        $gradeScale->status = "active";
        $gradeScale->save();
        return $gradeScale;
    }
    public function deactivateGradeScale(object $currentSchool, string $categoryId)
    {
        $gradeScale = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)
            ->find($categoryId);

        if (!$gradeScale) {
            throw new AppException(
                "Grade Scale Category Not Found",
                404,
                "Configuration Not Found",
                "The specified school grades configuration could not be found.",
                null
            );
        }

        if ($gradeScale->status === "inactive") {
            throw new AppException(
                "Grade Scale Already Inactive",
                422,
                "Validation Error",
                "This grade scale category is already inactive. No action required.",
                null
            );
        }

        $gradeScale->status = "inactive";
        $gradeScale->save();
        return $gradeScale;
    }
    public function createGradeScale(array $payload, object $currentSchool, object $authAdmin)
    {
        try {
            DB::beginTransaction();

            $gradeScaleCategory = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)
                ->where("id", $payload['grades_category_id'])
                ->first();

            if (!$gradeScaleCategory) {
                throw new AppException(
                    "Grade Scale Category Not Found",
                    404,
                    "Configuration Not Found",
                    "The specified school grades configuration could not be found. Please ensure the configuration exists before adding grades.",
                    null
                );
            }

            $this->validateGradeScaleOverlaps($payload['grade_scales']);

            $insertedGrades = [];

            foreach ($payload['grade_scales'] as $gradeScale) {
                $newGrade = SchoolGradeScale::create([
                    'school_branch_id' => $currentSchool->id,
                    'letter_grade_id' => $gradeScale['letter_grade_id'],
                    'performance' => $gradeScale['performance'],
                    'minimum_score' => $gradeScale['minimum_score'],
                    'maximum_score' => $gradeScale['maximum_score'],
                    'grade_points' => $gradeScale['grade_points'],
                    'result' => $gradeScale['result'],
                    'resit_result' => $gradeScale['resit_result'],
                    'grades_category_id' => $payload['grades_category_id'],
                ]);

                $insertedGrades[] = $newGrade;
            }

            $gradeScaleCategory->max_score = $payload['grade_max_score'];
            $gradeScaleCategory->save();

            DB::commit();
            return $gradeScaleCategory;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function updateGradeScale(object $currentSchool, array $payload, object $authAdmin)
    {
        try {
            DB::beginTransaction();

            $gradeScale = SchoolGradeScale::where('school_branch_id', $currentSchool->id)
                ->with(['grade', 'schoolGradeScaleCategory'])
                ->find($payload['grade_scale_id']);

            if (!$gradeScale) {
                throw new AppException(
                    "Grade Scale Not Found",
                    404,
                    "Configuration Not Found",
                    "The specified grade scale could not be found.",
                    null
                );
            }

            $categoryGradeScales = SchoolGradeScale::where('school_branch_id', $currentSchool->id)
                ->where('grades_category_id', $gradeScale->grades_category_id)
                ->where('id', '!=', $payload['grade_scale_id'])
                ->get();

            $this->validateGradeScaleOverlapWithExisting($payload, $categoryGradeScales);

            $gradeScale->letter_grade_id = $payload['letter_grade_id'] ?? $gradeScale->letter_grade_id;
            $gradeScale->grade_points = $payload['grade_points'] ?? $gradeScale->grade_points;
            $gradeScale->minimum_score = $payload['minimum_score'] ?? $gradeScale->minimum_score;
            $gradeScale->maximum_score = $payload['maximum_score'] ?? $gradeScale->maximum_score;
            $gradeScale->peformance = $payload['performance'] ?? $gradeScale->peformance;
            $gradeScale->result = $payload['result'] ?? $gradeScale->result;
            $gradeScale->resit_result = $payload['resit_result'] ?? $gradeScale->resit_result;
            $gradeScale->save();

            if (isset($payload['category_max_score'])) {
                $category = SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                    ->where('grades_category_id', $gradeScale->grades_category_id)
                    ->first();

                if ($category) {
                    $category->max_score = $payload['category_max_score'];
                    $category->save();
                }
            }

            DB::commit();

            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.grades.update"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "gradeScaleManagement",
                    "action" => "gradeScale.updated",
                    "authAdmin" => $authAdmin,
                    "data" => $gradeScale,
                    "message" => "Grade Scale Updated Successfully",
                ]
            );

            return $gradeScale->fresh(['grade', 'schoolGradeScaleCategory']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function copyGradeScaleCategory(object $currentSchool, string $sourceCategoryId, string $targetCategoryId, object $authAdmin)
    {
        $insertedGrades = [];
        DB::beginTransaction();

        try {
            $sourceCategory = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)
                ->where("id", $sourceCategoryId)
                ->first();

            $targetCategory = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)
                ->find($targetCategoryId);

            if (!$sourceCategory || !$targetCategory) {
                throw new AppException(
                    "Grade Scale Category Not Found",
                    404,
                    "Configuration Not Found",
                    "The specified grade scale categories could not be found.",
                    null
                );
            }

            $sourceGradeScales = SchoolGradeScale::where("school_branch_id", $currentSchool->id)
                ->where("grades_category_id", $sourceCategory->id)
                ->get();

            if ($sourceGradeScales->isEmpty()) {
                throw new AppException(
                    "No Grade Scales Found",
                    422,
                    "Validation Error",
                    "The source grade scale category has no configured grades to copy.",
                    null
                );
            }

            foreach ($sourceGradeScales as $gradeScale) {
                $newGrade = SchoolGradeScale::create([
                    'school_branch_id' => $currentSchool->id,
                    'letter_grade_id' => $gradeScale->letter_grade_id,
                    'grade_points' => $gradeScale->grade_points,
                    'minimum_score' => $gradeScale->minimum_score,
                    'maximum_score' => $gradeScale->maximum_score,
                    'performance' => $gradeScale->performance,
                    'result' => $gradeScale->result,
                    'resit_result' => $gradeScale->resit_result,
                    'grades_category_id' => $targetCategory->id,
                ]);

                $insertedGrades[] = $newGrade;
            }

            $targetCategory->max_score = $sourceCategory->max_score;
            $targetCategory->save();

            DB::commit();

            // AdminActionEvent::dispatch(
            //     [
            //         "permissions" => ["schoolAdmin.grades.create"],
            //         "roles" => ["schoolSuperAdmin", "schoolAdmin"],
            //         "schoolBranch" => $currentSchool->id,
            //         "feature" => "gradeScaleManagement",
            //         "action" => "gradeScale.copied",
            //         "authAdmin" => $authAdmin,
            //         "data" => $insertedGrades,
            //         "message" => "Grade Scale Copied Successfully",
            //     ]
            // );

            return $insertedGrades;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function bulkCopyGradeScaleCategories(object $currentSchool, array $payload, object $authAdmin)
    {
        DB::beginTransaction();
        try {
            $targetCategoryIds = $payload['target_category_ids'];
            $sourceCategoryId = $payload['source_category_id'];

            $sourceCategory = SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                ->where('grades_category_id', $sourceCategoryId)
                ->first();

            if (!$sourceCategory) {
                throw new AppException(
                    "Source Grade Scale Category Not Found",
                    404,
                    "Configuration Not Found",
                    "The source grade scale category could not be found.",
                    null
                );
            }

            $sourceGradeScales = SchoolGradeScale::where('school_branch_id', $currentSchool->id)
                ->where('grades_category_id', $sourceCategory->grades_category_id)
                ->get();

            if ($sourceGradeScales->isEmpty()) {
                throw new AppException(
                    "No Grade Scales Found",
                    422,
                    "Validation Error",
                    "The source grade scale category has no configured grades to copy.",
                    null
                );
            }

            $targetCategories = SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $targetCategoryIds)
                ->get();

            if ($targetCategories->isEmpty()) {
                throw new AppException(
                    "Target Grade Scale Categories Not Found",
                    404,
                    "Configuration Not Found",
                    "The specified target grade scale categories could not be found.",
                    null
                );
            }

            $gradesToInsert = [];
            foreach ($targetCategories as $targetCategory) {
                $existingGrades = SchoolGradeScale::where('school_branch_id', $currentSchool->id)
                    ->where('grades_category_id', $targetCategory->grades_category_id)
                    ->exists();

                if ($existingGrades) {
                    throw new AppException(
                        "Target Category Already Configured",
                        422,
                        "Validation Error",
                        "The target grade scale category {$targetCategory->id} already has configured grades.",
                        null
                    );
                }

                foreach ($sourceGradeScales as $gradeScale) {
                    $gradesToInsert[] = [
                        'id' => Str::uuid(),
                        'school_branch_id' => $currentSchool->id,
                        'letter_grade_id' => $gradeScale->letter_grade_id,
                        'grade_points' => $gradeScale->grade_points,
                        'minimum_score' => $gradeScale->minimum_score,
                        'maximum_score' => $gradeScale->maximum_score,
                        'peformance' => $gradeScale->peformance,
                        'result' => $gradeScale->result,
                        'resit_result' => $gradeScale->resit_result,
                        'grades_category_id' => $targetCategory->grades_category_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($gradesToInsert)) {
                DB::table('grade_scales')->insert($gradesToInsert);
            }

            SchoolGradeScaleCategory::whereIn('id', $targetCategories->pluck('id'))
                ->update([
                    'max_score' => $sourceCategory->max_score,
                ]);

            DB::commit();

            // AdminActionEvent::dispatch(
            //     [
            //         "permissions" => ["schoolAdmin.grades.create"],
            //         "roles" => ["schoolSuperAdmin", "schoolAdmin"],
            //         "schoolBranch" => $currentSchool->id,
            //         "feature" => "gradeScaleManagement",
            //         "action" => "gradeScale.bulkCopied",
            //         "authAdmin" => $authAdmin,
            //         "data" => $gradesToInsert,
            //         "message" => "Grade Scales Bulk Copied Successfully",
            //     ]
            // );

            return $gradesToInsert;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function getGradeScaleCategoryById(object $currentSchool, string $categoryId, string $configType, float $maxScore)
    {
        $schoolGradeScaleCategory = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)
            ->with(['systemGradeCategory', 'schoolGradeScale.grade'])
            ->find($categoryId);

        if (!$schoolGradeScaleCategory) {
            throw new AppException(
                "Grade Scale Category Not Found",
                404,
                "Configuration Not Found",
                "The specified grade scale category could not be found.",
                null
            );
        }

        $configuredGrades = SchoolGradeScale::where("school_branch_id", $currentSchool->id)
            ->where("grades_category_id", $schoolGradeScaleCategory->id)
            ->get()
            ->keyBy('letter_grade_id');

        $allLetterGrades = Grade::all();

        return $this->formatGradeScaleResponse(
            $allLetterGrades,
            $configuredGrades,
            $schoolGradeScaleCategory,
            $configType,
            $maxScore,
            $schoolGradeScaleCategory->systemGradeCategory->exam_type ?? null
        );
    }
    private function formatGradeScaleResponse(
        Collection $allLetterGrades,
        Collection $configuredGrades,
        SchoolGradeScaleCategory $schoolGradeScaleCategory,
        string $configType,
        float $maxScore,
        ?string $examType
    ): array {
        $category = [
            'id' => $schoolGradeScaleCategory->id,
            'system_category_id' => $schoolGradeScaleCategory->grades_category_id,
            'title' => $schoolGradeScaleCategory->systemGradeCategory->title ?? null,
            'exam_type' => $examType,
            'max_score' => $schoolGradeScaleCategory->max_score,
            'status' => $schoolGradeScaleCategory->status,
            'is_configured' => $configuredGrades->isNotEmpty()
        ];

        $formattedGrades = [];

        if ($configType === 'automatic' && $maxScore > 0) {
            $generateGradeScaleService = app(GenerateGradeScaleService::class);
            $autoGenScale = $generateGradeScaleService->generateGradeScale([
                'max_score' => $maxScore,
                'exam_type' => $examType
            ]);

            $autoGenScaleIndexed = collect($autoGenScale)->keyBy('letter_grade_id');

            foreach ($allLetterGrades as $letterGrade) {
                $generatedGrade = $autoGenScaleIndexed->get($letterGrade->id);

                $formattedGrades[] = [
                    'letter_grade_id' => $letterGrade->id,
                    'letter_grade' => $letterGrade->letter_grade,
                    'status' => $letterGrade->status,
                    'configuration' => [
                        'id' => null,
                        'minimum_score' => $generatedGrade['minimum_score'] ?? null,
                        'maximum_score' => $generatedGrade['maximum_score'] ?? null,
                        'grade_points' => $generatedGrade['grade_points'] ?? null,
                        'performance' => $generatedGrade['performance'] ?? null,
                        'result' => $generatedGrade['result'] ?? null,
                        'resit_result' => $generatedGrade['resit_result'] ?? null,
                        'is_configured' => !is_null($generatedGrade)
                    ]
                ];
            }
        } else {
            foreach ($allLetterGrades as $letterGrade) {
                $configuredGrade = $configuredGrades->get($letterGrade->id);

                $formattedGrades[] = [
                    'letter_grade_id' => $letterGrade->id,
                    'letter_grade' => $letterGrade->letter_grade,
                    'status' => $letterGrade->status,
                    'configuration' => [
                        'id' => $configuredGrade->id ?? null,
                        'minimum_score' => $configuredGrade ? $configuredGrade->minimum_score : null,
                        'maximum_score' => $configuredGrade ? $configuredGrade->maximum_score : null,
                        'grade_points' => $configuredGrade ? $configuredGrade->grade_points : null,
                        'performance' => $configuredGrade ? $configuredGrade->peformance : null,
                        'result' => $configuredGrade ? $configuredGrade->result : null,
                        'resit_result' => $configuredGrade ? $configuredGrade->resit_result : null,
                        'is_configured' => !is_null($configuredGrade)
                    ]
                ];
            }
        }

        return [
            'category' => $category,
            'grade_scales' => $formattedGrades
        ];
    }
    public function deleteGradeScale(object $currentSchool, string $gradeScaleCategoryId, object $authAdmin)
    {
        try {
            DB::beginTransaction();

            $gradeScaleCategory = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)->find($gradeScaleCategoryId);

            if (!$gradeScaleCategory) {
                throw new AppException(
                    "Grade Scale Category Not Found",
                    404,
                    "Configuration Not Found",
                    "The specified school grades configuration could not be found.",
                    null
                );
            }

            $gradeScales = SchoolGradeScale::where("school_branch_id", $currentSchool->id)
                ->where("grades_category_id", $gradeScaleCategory->id)
                ->get();

            foreach ($gradeScales as $grade) {
                $grade->delete();
            }

            $gradeScaleCategory->max_score = null;
            $gradeScaleCategory->save();

            DB::commit();

            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.grades.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "gradeScaleManagement",
                    "action" => "gradeScale.deleted",
                    "authAdmin" => $authAdmin,
                    "data" => $gradeScaleCategory,
                    "message" => "Grade Scale Deleted",
                ]
            );

            return $gradeScaleCategory;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkDeleteGradeScalesByCategories(object $currentSchool, array $payload, object $authAdmin)
    {
        try {
            DB::beginTransaction();

            $categoryIds = collect($payload['grade_scale_category_ids'])->pluck('category_id')->toArray();

            $gradeScaleCategories = SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $categoryIds)
                ->get();

            if ($gradeScaleCategories->isEmpty()) {
                throw new AppException(
                    "Grade Scale Categories Not Found",
                    404,
                    "Configuration Not Found",
                    "The specified grade scale categories could not be found.",
                    null
                );
            }

            $gradesCategoryIds = $gradeScaleCategories->pluck('grades_category_id')->toArray();

            SchoolGradeScale::where('school_branch_id', $currentSchool->id)
                ->whereIn('grades_category_id', $gradesCategoryIds)
                ->delete();

            SchoolGradeScaleCategory::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $categoryIds)
                ->update([
                    'max_score' => null,
                ]);

            DB::commit();

            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.grades.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "gradeScaleManagement",
                    "action" => "gradeScales.bulkDeletedByCategories",
                    "authAdmin" => $authAdmin,
                    "data" => $gradeScaleCategories,
                    "message" => "Grade Scales Bulk Deleted By Categories Successfully",
                ]
            );

            return $gradeScaleCategories;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    private function validateGradeScaleOverlaps(array $gradeScales)
    {
        $sortedScales = collect($gradeScales)->sortBy('minimum_score')->values()->toArray();

        for ($i = 0; $i < count($sortedScales) - 1; $i++) {
            $current = $sortedScales[$i];
            $next = $sortedScales[$i + 1];

            if ($current['maximum_score'] >= $next['minimum_score']) {
                throw new AppException(
                    "Grade Scale Overlap Detected",
                    422,
                    "Validation Error",
                    "Grade scales cannot overlap. The range {$current['minimum_score']} - {$current['maximum_score']} overlaps with {$next['minimum_score']} - {$next['maximum_score']}. Please adjust the score ranges.",
                    null
                );
            }
        }
    }

    private function validateGradeScaleOverlapWithExisting(array $newGradeScale, Collection $existingGradeScales)
    {
        $newMin = $newGradeScale['minimum_score'];
        $newMax = $newGradeScale['maximum_score'];

        foreach ($existingGradeScales as $existing) {
            $existingMin = $existing->minimum_score;
            $existingMax = $existing->maximum_score;

            $overlaps = (
                ($newMin >= $existingMin && $newMin <= $existingMax) ||
                ($newMax >= $existingMin && $newMax <= $existingMax) ||
                ($newMin <= $existingMin && $newMax >= $existingMax)
            );

            if ($overlaps) {
                throw new AppException(
                    "Grade Scale Overlap Detected",
                    422,
                    "Validation Error",
                    "The grade range {$newMin} - {$newMax} overlaps with existing range {$existingMin} - {$existingMax}. Please adjust the score ranges.",
                    null
                );
            }
        }
    }
}
