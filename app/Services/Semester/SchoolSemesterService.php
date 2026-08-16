<?php

namespace App\Services\Semester;

use App\Constant\Enums\SystemState;
//use App\Jobs\DataCreationJob\CreateExamJob;
use App\Jobs\DataCreationJob\CreateInstructorAvailabilityJob;
use App\Jobs\DataCreationJob\CreateTeacherAvailabilityJob;
use App\Jobs\NotificationJobs\SendNewSemesterAvialableNotificationJob;
use App\Models\FeeSchedule;
use App\Models\SchoolSemester;
use App\Models\Semester;
use App\Models\SchoolBranchSetting;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\AppException;
use Carbon\Carbon;
use App\Services\ApiResponseService;
use App\Events\Actions\AdminActionEvent;
use App\Events\Actions\StudentActionEvent;
use App\Jobs\JointCourse\CreateJointCourseSemesterJob;
use App\Models\AcademicYear\SchoolAcademicYear;

class SchoolSemesterService
{
    public function createSchoolSemester(array $semesterData, object $currentSchool, $authAdmin)
    {
        try {
            DB::beginTransaction();

            $schoolSemester = new SchoolSemester();

            $schoolYear = SchoolAcademicYear::where("school_branch_id", $currentSchool->id)
                ->with(['systemAcademicYear', 'specialty.level'])
                ->findOrFail($semesterData['school_year_id']);

            if ($semesterData['end_date'] > $schoolYear->end_date || $semesterData['start_date'] < $schoolYear->start_date) {
                throw new AppException(
                    "The semester dates must fall within the start and end dates of the associated school academic year.",
                    400,
                    "Invalid Semester Dates",
                    "Please ensure that the semester's start and end dates are within the range of the school academic year's start and end dates.",
                    null
                );
            }

            $specialty = $schoolYear->specialty;

            $existingSemester = SchoolSemester::where("school_branch_id", $currentSchool->id)
                ->where("semester_id", $semesterData['semester_id'])
                ->where("school_year_id", $semesterData['school_year_id'])
                ->first();

            if ($existingSemester) {
                throw new AppException(
                    "A semester with the same specialty, semester, and school year already exists.",
                    409,
                    "Duplicate Semester",
                    "You are trying to create a semester that already exists. Please check the details and try again.",
                    null
                );
            }

            $schoolSemester->start_date = $semesterData["start_date"];
            $schoolSemester->end_date = $semesterData["end_date"];
            $schoolSemester->school_year_id = $semesterData["school_year_id"];
            $schoolSemester->semester_id = $semesterData["semester_id"];
            $schoolSemester->school_branch_id = $currentSchool->id;
            $schoolSemester->timetable_published = false;

            $schoolSemester->save();

            FeeSchedule::create([
                'specialty_id' => $specialty->id,
                'level_id' => $specialty->level_id,
                'school_branch_id' => $currentSchool->id,
                'school_semester_id' => $schoolSemester->id
            ]);

            CreateTeacherAvailabilityJob::dispatch([
                'specialty_id' => $specialty->id,
                'school_branch_id' => $currentSchool->id,
                'school_semester_id' => $schoolSemester->id,
                'level_id' => $specialty->level_id
            ]);

            DB::commit();

            $data = [
                'startDate' => $semesterData['start_date'],
                'endDate' => $semesterData['end_date'],
                'semester' => Semester::find($semesterData['semester_id'])->name,
                'level' => $specialty->level->name,
                'school_year_id' => $semesterData['school_year_id']
            ];

            $autoCreateExamSetting = $this->getSettingByKey($currentSchool->id, "exam.auto_create");
            if ($autoCreateExamSetting->value === true) {
                // CreateExamJob::dispatch([
                //     'specialty_id' => $semesterData['specialty_id'],
                //     'student_batch_id' => $semesterData['student_batch_id'],
                //     'semester_id' => $semesterData['semester_id'],
                //     'level_id' => $specialty->level_id,
                //     'school_year_id' => $semesterData['school_year_id']
                // ], $currentSchool->id);
            }

            SendNewSemesterAvialableNotificationJob::dispatch(
                $schoolYear->specialty_id,
                $currentSchool->id,
                $data
            );

            CreateInstructorAvailabilityJob::dispatch(
                $currentSchool->id,
                $schoolSemester->id
            );

            CreateJointCourseSemesterJob::dispatch(
                $schoolSemester->id,
                $currentSchool
            );

            // AdminActionEvent::dispatch(
            //     [
            //         "permissions" =>  ["schoolAdmin.schoolSemester.create"],
            //         "roles" => ["schoolSuperAdmin", "schoolAdmin"],
            //         "schoolBranch" =>  $currentSchool->id,
            //         "feature" => "schoolSemesterManagement",
            //         "authAdmin" => $authAdmin,
            //         "data" => $schoolSemester,
            //         "message" => "School Semester Created ",
            //     ]
            // );
            // StudentActionEvent::dispatch([
            //     'schoolBranch' => $currentSchool->id,
            //     'specialtyIds'   => [$semesterData['specialty_id']],
            //     'feature'      => 'semesterCreated',
            //     'message'      => 'Semester Created',
            //     'data'         => $schoolSemester,
            // ]);
            return $schoolSemester;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new AppException(
                "The specified specialty was not found. Please verify the specialty ID.",
                404,
                "Specialty Not Found",
                "We could not find the specialty associated with the provided ID. Please check and try again.",
                null
            );
        } catch (Exception $e) {
            DB::rollBack();

            // Get detailed error information
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'code' => $e->getCode()
            ];


            throw new AppException(
                "An error occurred while creating the semester. Please try again.",
                500,
                "Creation Error",
                "We encountered an issue while trying to create the semester. Error: " . $e->getMessage() . " in " . basename($e->getFile()) . " on line " . $e->getLine(),
                null
            );
        }
    }
    public function updateSchoolSemester(array $semesterData, object $currentSchool, string $schoolSemesterId, $authAdmin)
    {
        $schoolSemester = SchoolSemester::where("school_branch_id", $currentSchool->id)->find($schoolSemesterId);
        if (!$schoolSemester) {
            return ApiResponseService::error("School Semester Not Found", null, 404);
        }

        $filteredData = array_filter($semesterData);
        $schoolSemester->update($filteredData);
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.schoolSemester.update"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "schoolSemesterManagement",
                "authAdmin" => $authAdmin,
                "data" => $schoolSemester,
                "message" => "School Semester Updated",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch' => $currentSchool->id,
            'specialtyIds'   => [$schoolSemester->specialty_id],
            'feature'      => 'semesterUpdate',
            'message'      => 'Semester  Updated',
            'data'         => $schoolSemester,
        ]);
        return $schoolSemester;
    }
    public function bulkUpdateSchoolSemester(array $updateSemesterList, object $currentSchool, $authAdmin)
    {
        $result = [];
        $specialtyIds = [];
        try {
            DB::beginTransaction();
            foreach ($updateSemesterList as $updateSemester) {
                $schoolSemester = SchoolSemester::where("school_branch_id", $currentSchool->id)->findOrFail($updateSemester['school_semester_id']);
                $filteredData = array_filter($updateSemester);
                $schoolSemester->update($filteredData);
                $result[] = [
                    $schoolSemester
                ];
                $specialtyIds[] = $schoolSemester->specialty_id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.schoolSemester.update"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "schoolSemesterManagement",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "School Semester Updated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'specialtyIds'   => $specialtyIds,
                'feature'      => 'semesterUpdate',
                'message'      => 'Semester Semester Updated',
                'data'         => $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function deleteSchoolSemester(string $schoolSemesterId, object $currentSchool, $authAdmin)
    {
        $schoolSemester = SchoolSemester::where("school_branch_id", $currentSchool->id)
            ->find($schoolSemesterId);
        if (!$schoolSemester) {
            throw new AppException(
                "The school semester you are trying to delete was not found.",
                404,
                "Semester Not Found",
                "The specified school semester does not exist or may have already been deleted.",
                null
            );
        }
        $schoolSemester->delete();
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.schoolSemester.delete"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "schoolSemesterManagement",
                "authAdmin" => $authAdmin,
                "data" => $schoolSemester,
                "message" => "School Semester Deleted",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch' => $currentSchool->id,
            'specialtyIds'   => [$schoolSemester->specialty_id],
            'feature'      => 'semesterDelete',
            'message'      => 'Semester Deleted',
            'data'         => $schoolSemester,
        ]);
        return $schoolSemester;
    }
    public function bulkDeleteSchoolSemester(array $schoolSemesterIds, object $currentSchool, $authAdmin): array
    {
        $deletedSemesters = [];
        $specialtyIds = [];
        try {
            DB::beginTransaction();

            foreach ($schoolSemesterIds as $schoolSemesterId) {
                $id = $schoolSemesterId['school_semester_id'] ?? null;
                if (!$id) {
                    throw new AppException(
                        "An invalid semester ID was provided in the list.",
                        400,
                        "Invalid Input",
                        "One or more semester IDs were malformed or missing from the request.",
                        null
                    );
                }

                $schoolSemester = SchoolSemester::findOrFail($id);
                $schoolSemester->delete();
                $deletedSemesters[] = $schoolSemester;
                $specialtyIds[] = $schoolSemester->id;
            }

            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.schoolSemester.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "schoolSemesterManagement",
                    "authAdmin" => $authAdmin,
                    "data" => $deletedSemesters,
                    "message" => "School Semester Deleted",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'specialtyIds'   => $specialtyIds,
                'feature'      => 'semesterDelete',
                'message'      => 'Semester Deleted',
                'data'         => $schoolSemester,
            ]);
            return $deletedSemesters;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new AppException(
                "A semester you tried to delete was not found. Please verify the IDs and try again.",
                404,
                "Semester Not Found",
                "We could not find one or more semesters associated with the provided IDs. Please check the list and try again.",
                null
            );
        } catch (Exception $e) {
            DB::rollBack();
            throw new AppException(
                "An unexpected error occurred while deleting the semesters. Please try again.",
                500,
                "Deletion Error",
                "We encountered an issue while trying to delete the semesters. This may be due to a server problem.",
                null
            );
        }
    }
    public function getSchoolSemesters(object $currentSchool)
    {
        $schoolSemesters = SchoolSemester::query()
            ->where('school_branch_id', $currentSchool->id)
            ->select([
                'id',
                'start_date',
                'end_date',
                'semester_id',
                'timetable_published',
                'school_year_id',
            ])
            ->with([
                'schoolYear.specialty:id,specialty_name,level_id',
                'schoolYear.specialty.level:id,level,name',
                'semester:id,name,count',
                'schoolYear.systemAcademicYear:id,name,year_start,year_end',
            ])
            ->get();

        if ($schoolSemesters->isEmpty()) {
            throw new AppException(
                "No school semesters found for this school branch.",
                404,
                "No Semesters Found",
                "There are no school semesters available. Please create a semester to get started.",
                "/semesters"
            );
        }

        $today = now()->startOfDay();

        return $schoolSemesters->map(function ($semester) use ($today) {
            $start = $semester->start_date ? Carbon::parse($semester->start_date)->startOfDay() : null;
            $end = $semester->end_date ? Carbon::parse($semester->end_date)->endOfDay() : null;

            $status = null;
            if ($start && $today->lt($start)) {
                $status = SystemState::UPCOMING;
            } elseif ($start && $end && $today->betweenIncluded($start, $end)) {
                $status = SystemState::ONGOING;
            } elseif ($end && $today->gt($end)) {
                $status = SystemState::FINISHED;
            }

            return [
                "id" => $semester->id,
                "start_date" => $start ? $start->format('Y-m-d') : null,
                "end_date" => $end ? $end->format('Y-m-d') : null,
                "school_year" => $semester->schoolYear?->systemAcademicYear?->name,
                "status" => $status,
                "specialty_name" => $semester->schoolYear->specialty?->specialty_name,
                "level_name" => $semester->schoolYear->specialty?->level?->name,
                "level_number" => $semester->schoolYear->specialty?->level?->level,
                "timetable_published" => $semester->timetable_published ? "created" : "not created",
                "semester_name" => $semester->semester?->name ?? $semester->semester?->semester_name,
                "created_at" => $semester->created_at,
                "updated_at" => $semester->updated_at
            ];
        });
    }
    public function getActiveSchoolSemesters(object $currentSchool)
    {
        $schoolSemesters = SchoolSemester::where("school_branch_id", $currentSchool->id)
            ->with(['specialty', 'specialty.level', 'semester', 'studentBatch'])
            ->where("start_date", "<=", Carbon::now())
            ->where("end_date", ">=", Carbon::now())
            ->get();

        if ($schoolSemesters->isEmpty()) {
            throw new AppException(
                "No active school semesters found for this school branch.",
                404,
                "No Active Semesters Found",
                "There are no active school semesters available. Please create and activate a semester to get started.",
                "/semesters"
            );
        }
        return $schoolSemesters;
    }
    public function getSchoolSemesterDetail(object $currentSchool, string $semesterId)
    {
        $schoolSemesterDetails = SchoolSemester::with(['specialty', 'specialty.level', 'semester', 'studentBatch', 'schoolYear.systemAcademicYear'])
            ->where("school_branch_id", $currentSchool->id)
            ->find($semesterId);
        if ($schoolSemesterDetails === null) {
            throw new AppException(
                "The school semester you are trying to access was not found.",
                404,
                "Semester Not Found",
                "The specified school semester does not exist or may have been deleted.",
                null
            );
        }
        return $schoolSemesterDetails;
    }
    private function getSettingByKey(string $schoolBranchId, string $key)
    {
        return SchoolBranchSetting::where("school_branch_id", $schoolBranchId)
            ->whereHas('settingDefination', fn($query) => $query->where("key", $key))
            ->first();
    }
}
