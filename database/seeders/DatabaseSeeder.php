<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(CountrySeeder::class);
        $this->call(FeatureSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(LevelTypeSeeder::class);
        $this->call(PermissionCategorySeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(StatTypeSeeder::class);
        $this->call(AssignPermissionToRoles::class);
        $this->call(InstallmentSeeder::class);
        $this->call(AnnouncementSeeder::class);
        $this->call(SemesterSeeder::class);
        $this->call(LetterGradeSeeder::class);
        $this->call(ExamTypeSeeder::class);
        $this->call(GradeCategorySeeder::class);
        $this->call(SettingCategorySeeder::class);
        $this->call(SettingDefinationSeeder::class);
        $this->call(StudentParentRelationshipSeeder::class);
        $this->call(LevelSeeder::class);
        $this->call(ActivationCodeTypeSeeder::class);
        $this->call(FeaturePlanSeeder::class);
        $this->call(StudentCredentials::class);
        $this->call(UserBadgeSeeder::class);
        $this->call(SystemAcademicYearSeeder::class);
        $this->call(PeriodDurationSeeder::class);
        $this->call(JobCategorySeeder::class);
    }
}
