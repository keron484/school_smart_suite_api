<?php

namespace App\Services\Grade;

use App\Models\Grades;
use App\Models\SchoolGradesConfig;
use App\Models\LetterGrade;
use Illuminate\Support\Facades\DB;
use Exception;
use Throwable;
use Illuminate\Support\Str;
use App\Exceptions\AppException;
use App\Events\Actions\AdminActionEvent;
use Illuminate\Support\Collection;

class GradeScaleService
{
    public function createGradeScale(array $grades, object $currentSchool, object $authAdmin)
    {
        try {
            DB::beginTransaction();

            $insertedGrades = [];

            foreach ($grades as $grade) {
                $newGrade = Grades::create([
                    'school_branch_id' => $currentSchool->id,
                    'letter_grade_id' => $grade['letter_grade_id'],
                    'grade_points' => $grade['grade_points'],
                    'minimum_score' => $grade['minimum_score'],
                    'maximum_score' => $grade['maximum_score'],
                    'grade_status' => $grade['grade_status'],
                    'resit_status' => $grade['resit_status'],
                    'determinant' => $grade['determinant'],
                    'grades_category_id' => $grade['grades_category_id'],
                ]);

                $insertedGrades[] = $newGrade;
            }
            $schoolGradesConfig = SchoolGradesConfig::where("school_branch_id", $currentSchool->id)
                ->where("grades_category_id", $grades[0]['grades_category_id'])
                ->first();

            if (!$schoolGradesConfig) {
                throw new AppException(
                    "School Grades Configuration Not Found",
                    404,
                    "Configuration Not Found",
                    "The specified school grades configuration could not be found. Please ensure the configuration exists before adding grades.",
                    null
                );
            }

            if ($schoolGradesConfig) {
                $schoolGradesConfig->isgrades_configured = true;
                $schoolGradesConfig->max_score = $grades[0]['max_score'];
                $schoolGradesConfig->save();
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.grades.create"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "gradeScaleManagement",
                    "action" => "gradeScale.created",
                    "authAdmin" => $authAdmin,
                    "data" => $insertedGrades,
                    "message" => "Grade Scale Created",
                ]
            );
            return $insertedGrades;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function bulkCreateGradeScale(array $data, object $currentSchool, object $authAdmin)
    {
        DB::beginTransaction();
        try {
            $gradesToInsert = [];
            $configsToUpdate = [];
            $grades = collect($data['grades'])->groupBy('grades_category_id');
            $configIds = collect($data['configIds'])->pluck('grade_config_id')->toArray();

            $existingConfigs = SchoolGradesConfig::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $configIds)
                ->get()
                ->keyBy('id');

            foreach ($configIds as $configId) {
                $schoolGradesConfig = $existingConfigs->get($configId);

                if ($schoolGradesConfig && !$schoolGradesConfig->isgrades_configured) {
                    $gradesCategoryId = $schoolGradesConfig->grades_category_id;
                    $gradesForCategory = $grades->get($gradesCategoryId, []);

                    if (!empty($gradesForCategory)) {
                        $maxScore = $gradesForCategory[0]['max_score'];

                        foreach ($gradesForCategory as $grade) {
                            $gradesToInsert[] = [
                                'id' => Str::uuid(),
                                'school_branch_id' => $currentSchool->id,
                                'letter_grade_id' => $grade['letter_grade_id'],
                                'grade_points' => $grade['grade_points'],
                                'minimum_score' => $grade['minimum_score'],
                                'maximum_score' => $grade['maximum_score'],
                                'grade_status' => $grade['grade_status'],
                                'resit_status' => $grade['resit_status'],
                                'determinant' => $grade['determinant'],
                                'grades_category_id' => $gradesCategoryId,
                            ];
                        }

                        $configsToUpdate[] = [
                            'id' => $schoolGradesConfig->id,
                            'isgrades_configured' => true,
                            'max_score' => $maxScore,
                        ];
                    }
                }
            }

            if (!empty($gradesToInsert)) {
                DB::table('grades')->insert($gradesToInsert);
            }

            if (!empty($configsToUpdate)) {
                SchoolGradesConfig::whereIn('id', array_column($configsToUpdate, 'id'))
                    ->upsert($configsToUpdate, ['id'], ['isgrades_configured', 'max_score']);
            }

            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.grades.create"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "gradeScaleManagement",
                    "authAdmin" => $authAdmin,
                    "action" => "gradeScale.created",
                    "data" => $gradesToInsert,
                    "message" => "Grade Scale Created",
                ]
            );
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function updateGradeScale(array $grades, object $currentSchool, object $authAdmin): bool
    {
        if (empty($grades)) {
            return true;
        }

        try {
            DB::beginTransaction();

            foreach ($grades as $grade) {
                Grades::where('school_branch_id', $currentSchool->id)
                    ->where('id', $grade['grade_id'])
                    ->update([
                        'grade_points' => $grade['grade_points'],
                        'letter_grade_id' => $grade['letter_grade_id'],
                        'minimum_score' => $grade['minimum_score'],
                        'maximum_score' => $grade['maximum_score'],
                        'grade_status' => $grade['grade_status'],
                        'resit_status' => $grade['resit_status'],
                        'determinant' => $grade['determinant'],
                    ]);
            }

            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.grades.update"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "gradeScaleManagement",
                    "action" => "gradeScale.update",
                    "authAdmin" => $authAdmin,
                    "data" => $grades,
                    "message" => "Grade Scale Updated",
                ]
            );
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function configureByOtherScale(string $configId, object $currentSchool, string $targetConfigId, object $authAdmin)
    {
        $insertedGrades = [];
        DB::beginTransaction();

        try {

            $schoolGradesConfig = SchoolGradesConfig::where("school_branch_id", $currentSchool->id)->find($configId);
            $targetGradesConfig = SchoolGradesConfig::where("school_branch_id", $currentSchool->id)->find($targetConfigId);


            if (!$schoolGradesConfig || !$targetGradesConfig) {
                throw new Exception("School Grades Configurations not found", 404);
            }


            $grades = Grades::where("school_branch_id", $currentSchool->id)
                ->where("grades_category_id", $schoolGradesConfig->grades_category_id)
                ->get();


            foreach ($grades as $grade) {
                $newGrade = Grades::create([
                    'school_branch_id' => $currentSchool->id,
                    'letter_grade_id' => $grade->letter_grade_id,
                    'grade_points' => $grade->grade_points,
                    'minimum_score' => $grade->minimum_score,
                    'maximum_score' => $grade->maximum_score,
                    'grade_status' => $grade->grade_status,
                    'determinant' => $grade->determinant,
                    'grades_category_id' => $targetGradesConfig->grades_category_id,
                ]);

                $insertedGrades[] = $newGrade;
            }


            $targetGradesConfig->isgrades_configured = true;
            $targetGradesConfig->max_score = $schoolGradesConfig->max_score;
            $targetGradesConfig->save();

            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.grades.create"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "gradeScaleManagement",
                    "action" => "gradeScale.created",
                    "authAdmin" => $authAdmin,
                    "data" => $insertedGrades,
                    "message" => "Grade Scale Created",
                ]
            );
            return $insertedGrades;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function bulkConfigureByOtherScales(array $data, object $currentSchool, object $authAdmin)
    {
        DB::beginTransaction();
        try {
            $configIds = $data['configIds'];
            $sourceConfigId = $data['target_config_id'];

            $sourceConfig = SchoolGradesConfig::where('school_branch_id', $currentSchool->id)
                ->find($sourceConfigId);

            if (!$sourceConfig || !$sourceConfig->isgrades_configured) {
                DB::rollBack();
                return;
            }

            $targetConfigs = SchoolGradesConfig::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $configIds)
                ->where('isgrades_configured', false)
                ->get();

            if ($targetConfigs->isEmpty()) {
                DB::rollBack();
                return;
            }

            $sourceGrades = Grades::where('school_branch_id', $currentSchool->id)
                ->where('grades_category_id', $sourceConfig->grades_category_id)
                ->get();

            if ($sourceGrades->isEmpty()) {
                DB::rollBack();
                return;
            }

            $gradesToInsert = [];
            foreach ($targetConfigs as $targetConfig) {
                foreach ($sourceGrades as $grade) {
                    $gradesToInsert[] = [
                        'id' => Str::uuid(),
                        'school_branch_id' => $currentSchool->id,
                        'letter_grade_id' => $grade->letter_grade_id,
                        'grade_points' => $grade->grade_points,
                        'minimum_score' => $grade->minimum_score,
                        'maximum_score' => $grade->maximum_score,
                        'grade_status' => $grade->grade_status,
                        'resit_status' => $grade->resit_status,
                        'determinant' => $grade->determinant,
                        'grades_category_id' => $targetConfig->grades_category_id,
                    ];
                }
            }

            if (!empty($gradesToInsert)) {
                DB::table('grades')->insert($gradesToInsert);
            }

            SchoolGradesConfig::whereIn('id', $targetConfigs->pluck('id'))
                ->update([
                    'isgrades_configured' => true,
                    'max_score' => $sourceConfig->max_score,
                ]);

            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.grades.create"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "gradeScaleManagement",
                    "action" => "gradeScale.created",
                    "authAdmin" => $authAdmin,
                    "data" => $gradesToInsert,
                    "message" => "Grade Scale Created",
                ]
            );
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function getGradeScaleCategoryId(object $currentSchool, string $categoryId)
    {
        try {
            $schoolGradeScaleCategory = SchoolGradesConfig::where("school_branch_id", $currentSchool->id)
                ->with(['gradesCategory'])
                ->find($categoryId);

            if (!$schoolGradeScaleCategory) {
                throw new Exception("Grade Scale Category not found", 404);
            }
            $configuredGrades = Grades::where("school_branch_id", $currentSchool->id)
                ->where("grades_category_id", $schoolGradeScaleCategory->grades_category_id)
                ->get()
                ->keyBy('letter_grade_id');

            $allLetterGrades = LetterGrade::all();

            return $this->formatGradeResponse($allLetterGrades, $configuredGrades, $schoolGradeScaleCategory);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function formatGradeResponse(
        Collection $allLetterGrades,
        Collection $configuredGrades,
        SchoolGradesConfig $schoolGradesConfig
    ): array {
        $category = [
            'id' => $schoolGradesConfig->grades_category_id,
            'name' => $schoolGradesConfig->gradesCategory->title ?? null,
            'maximum_score' => $schoolGradesConfig->max_score ?? null,
            'is_configured' => $configuredGrades->isNotEmpty()
        ];

        $formattedGrades = [];

        foreach ($allLetterGrades as $letterGrade) {
            $configuredGrade = $configuredGrades->get($letterGrade->id);
            $formattedGrades[] = [
                'letter_grade_id' => $letterGrade->id,
                'letter_grade' => $letterGrade->letter_grade,
                'configuration' => [
                    'id' => $configuredGrade->id ?? null,
                    'min_score' => $configuredGrade ? (float) $configuredGrade->minimum_score : null,
                    'max_score' => $configuredGrade ? (float) $configuredGrade->maximum_score : null,
                    'grade_point' => $configuredGrade ? (float) $configuredGrade->grade_points : null,
                    'performance' => $configuredGrade ? $configuredGrade->determinant : null,
                    'result' => $configuredGrade ? $configuredGrade->grade_status : null,
                    'resit_result' => $configuredGrade ? $configuredGrade->result_status : null,
                    'is_configured' => $configuredGrade &&
                        $configuredGrade->minimum_score !== null &&
                        $configuredGrade->maximum_score !== null
                ]
            ];
        }

        return [
            'category' => $category,
            'grades' => $formattedGrades
        ];
    }
    public function deleteGradesConfig(object $currentSchool, string $configId, object $authAdmin)
    {
        try {
            DB::beginTransaction();
            $schoolGradesConfig = SchoolGradesConfig::where("school_branch_id", $currentSchool->id)->find($configId);
            if (!$schoolGradesConfig) {
                throw new Exception("School Grades Configuration Not Found", 404);
            }
            $grades = Grades::where("school_branch_id", $currentSchool->id)
                ->where("grades_category_id", $schoolGradesConfig->grades_category_id)
                ->get();
            foreach ($grades as $grade) {
                $grade->delete();
            }
            $schoolGradesConfig->isgrades_configured = false;
            $schoolGradesConfig->max_score = null;
            $schoolGradesConfig->save();
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.grades.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "gradeScaleManagement",
                    "action" => "gradeScale.deleted",
                    "authAdmin" => $authAdmin,
                    "data" => $schoolGradesConfig,
                    "message" => "Grade Scale Deleted",
                ]
            );
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function bulkDeleteGradesConfig(object $currentSchool, array $data, object $authAdmin)
    {
        try {
            DB::beginTransaction();
            $configIds = collect($data['configIds'])->pluck('grade_config_id')->toArray();

            $schoolGradesConfigs = SchoolGradesConfig::where('school_branch_id', $currentSchool->id)
                ->whereIn('id', $configIds)
                ->where('isgrades_configured', true)
                ->get();
            if ($schoolGradesConfigs->isNotEmpty()) {
                $gradesCategoryIds = $schoolGradesConfigs->pluck('grades_category_id')->toArray();
                Grades::where('school_branch_id', $currentSchool->id)
                    ->whereIn('grades_category_id', $gradesCategoryIds)
                    ->delete();

                SchoolGradesConfig::where('school_branch_id', $currentSchool->id)
                    ->whereIn('id', $configIds)
                    ->update([
                        'isgrades_configured' => false,
                        'max_score' => null,
                    ]);
            }

            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.grades.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "gradeScaleManagement",
                    "action" => "gradeScale.deleted",
                    "authAdmin" => $authAdmin,
                    "data" => $schoolGradesConfigs,
                    "message" => "Grade Scale Deleted",
                ]
            );
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
