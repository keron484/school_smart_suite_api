<?php

namespace App\Services\Exam;

use App\Jobs\Exam\CreateExamCandidateJob;
use App\Models\Exam\Exam;
use App\Models\GradeScale\Grade;
use Illuminate\Support\Str;
use App\Models\SchoolGradesConfig;
use App\Models\GradeScale\SchoolGradeScaleCategory;
use App\Models\AccessedStudent;
use App\Models\Examtype;
use App\Models\Semester;
use App\Models\Student;
use App\Models\AcademicYear\SchoolAcademicYear;
use Carbon\Carbon;
use Exception;
use App\Exceptions\AppException;
use App\Models\ResitExam;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Grades;
use App\Events\Actions\AdminActionEvent;
use App\Events\Actions\StudentActionEvent;

class ExamService
{
    public function createExam(array $data, object $currentSchool, object $authAdmin): Exam
    {
        try {
            $examType = Examtype::find($data['exam_type_id']);
            if (!$examType) {
                throw new AppException(
                    "Exam Type Not Found",
                    404,
                    "Exam Type Not Found",
                    "Exam Type Not Found. Please ensure that this exam type exists and is not accidentally deleted."
                );
            }

            $existingExam = Exam::where("school_branch_id", $currentSchool->id)
                ->where("exam_type_id", $data['exam_type_id'])
                ->where("school_year_id", $data['school_year_id'])
                ->first();

            if ($existingExam) {
                throw new AppException(
                    "Duplicate Exam Details",
                    409,
                    "Duplicate Exam Credentials",
                    "You are trying to create an exam that already exists. Please check exam details and try again."
                );
            }

            $calculatedMaxScore = $data["max_score"];

            if (strtolower($examType->type) !== 'ca') {
                $caExam = Exam::where("school_branch_id", $currentSchool->id)
                    ->where("school_year_id", $data['school_year_id'])
                    ->whereHas('examType', function ($query) use ($examType) {
                        $query->where('semester', $examType->semester)
                            ->where('type', 'ca');
                    })
                    ->first();

                if (!$caExam) {
                    throw new AppException(
                        "Missing CA Exam Prerequisite",
                        422,
                        "Continuous Assessment Required",
                        "You must create a Continuous Assessment (CA) exam for semester '{$examType->semester}' before creating this exam."
                    );
                }

                $calculatedMaxScore += $caExam->max_score;
            }

            $exam = new Exam();
            $exam->school_branch_id = $currentSchool->id;
            $exam->start_date = $data["start_date"];
            $exam->end_date = $data["end_date"];
            $exam->max_score = $calculatedMaxScore;
            $exam->exam_type_id = $examType->id;
            $exam->school_year_id = $data["school_year_id"];
            $exam->save();

            CreateExamCandidateJob::dispatch(
                $exam->id,
                $currentSchool->id
            );

            return $exam;
        } catch (AppException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new AppException(
                $e->getMessage(),
                500,
                "Server Error",
                "An unexpected error occurred while creating the exam. Please try again later."
            );
        }
    }
    public function deleteExam(string $examId, Object $currentSchool, object $authAdmin)
    {
        try {

            $exam = Exam::where("school_branch_id", $currentSchool->id)
                ->findorFail($examId);
            $this->deleteExamCandidate($examId, $currentSchool);

            $exam->delete();
            return $exam;
        } catch (ModelNotFoundException $e) {
            throw new AppException(
                "The exam you are trying to delete was not found. Please verify the exam ID.",
                404,
                "Exam Not Found",
                "We could not find the exam associated with the provided ID. It may have already been deleted.",
                null
            );
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected error occurred while deleting the exam. Please try again later.",
                500,
                "Deletion Error",
                "We encountered a server-side issue while attempting to delete the exam.",
                $e->getMessage()
            );
        }
    }
    private function deleteExamCandidate(string $examId, object $currentSchool)
    {
        AccessedStudent::where("school_branch_id", $currentSchool)
            ->where("exam_id", $examId)
            ->delete();
    }
    public function bulkDeleteExam(array $examIds, object $currentSchool, object $authAdmin): array
    {
        $deletedExams = [];

        try {
            DB::beginTransaction();
            $specialtyIds = [];
            foreach ($examIds as $examIdItem) {
                $examId = $examIdItem['exam_id'] ?? null;
                $exam = Exam::where("school_branch_id", $currentSchool->id)
                    ->findOrFail($examId);
                $specialtyIds[] = $exam->specialty_id;
                $this->deleteExamCandidate($examId, $currentSchool);
                $exam->delete();
                $deletedExams[] = $exam;
            }

            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.exam.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "examManagement",
                    "authAdmin" => $authAdmin,
                    "data" => $deletedExams,
                    "message" => "Exam Deleted",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'specialtyIds'   => $specialtyIds,
                'feature'      => 'examDelete',
                'message'      => 'Exam  Deleted',
                'data'         => $deletedExams,
            ]);
            return $deletedExams;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new AppException(
                "One or more exams you tried to delete were not found. Please verify the IDs and try again.",
                404,
                "Exams Not Found",
                "We could not find one or more exams associated with the provided IDs. They may have already been deleted.",
                null
            );
        } catch (Exception $e) {
            DB::rollBack();
            throw new AppException(
                "An unexpected error occurred while deleting the exams. Please try again.",
                500,
                "Deletion Error",
                "We encountered an issue while trying to delete the exams due to a server-side problem.",
                null
            );
        }
    }
    public function updateExam(string $examId, object $currentSchool, array $data, array  $authAdmin)
    {
        try {
            $exam = Exam::where("school_branch_id", $currentSchool->id)
                ->find($examId);

            if (!$exam) {
                throw new AppException(
                    "The exam you are trying to update was not found.",
                    404,
                    "Exam Not Found",
                    "We could not find the exam with the provided ID for this school. Please verify the ID and try again.",
                    null
                );
            }

            $filteredData = array_filter($data);

            $exam->update($filteredData);
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.exam.update"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "examManagement",
                    "authAdmin" => $authAdmin,
                    "data" => $exam,
                    "message" => "Exam Updated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'specialtyIds'   => [$exam->specialty_id],
                'feature'      => 'examUpdate',
                'message'      => 'Exam Updated',
                'data'         => $exam,
            ]);
            return $exam;
        } catch (AppException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected error occurred while updating the exam. Please try again later.",
                500,
                "Update Error",
                "We encountered a server-side issue while attempting to update the exam. " . $e->getMessage(),
                null
            );
        }
    }
    public function bulkUpdateExam(array $examUpdateList, object $currentSchool, object $authAdmin)
    {
        $result = [];
        $specialtyIds = [];
        try {
            DB::beginTransaction();
            foreach ($examUpdateList as $examUpdate) {
                $exam = Exam::where("school_branch_id", $currentSchool->id)
                    ->findOrFail($examUpdate['exam_id']);
                $filterData = array_filter($examUpdate);
                $exam->update($filterData);
                $specialtyIds[] = $exam->specialty_id;
                $result[] = [
                    $exam
                ];
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.exam.update"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "examManagement",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "Exam Updated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'specialtyIds'   => $specialtyIds,
                'feature'      => 'examUpdate',
                'message'      => 'Exam Updated',
                'data'         => $exam,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function getExams(object $currentSchool)
    {
        $exams = Exam::where('school_branch_id', $currentSchool->id)
            ->with([
                'examType.semesters',
                'schoolYear.specialty.level',
                'examGradeScale',
                'schoolYear.systemAcademicYear'
            ])
            ->get();

        if ($exams->isEmpty()) {
            throw new AppException(
                "There are no exams available for this school branch yet.",
                404,
                "No Exams Found",
                "We could not find any exams associated with your school branch. Please try creating one first.",
                null
            );
        }

        return $exams;
    }
    public function examDetails(object $currentSchool, string $examId): Exam
    {
        try {
            $exam = Exam::where("school_branch_id", $currentSchool->id)
                ->with(['examType.semesters', 'schoolYear.specialty.level', 'schoolYear.systemAcademicYear', 'examGradeScale'])
                ->find($examId);

            if (!$exam) {
                throw new AppException(
                    "The exam you are looking for was not found.",
                    404,
                    "Exam Not Found",
                    "We could not find an exam with the provided ID for this school. Please verify the ID and try again.",
                    null
                );
            }

            $now = Carbon::now();
            $startDate = Carbon::parse($exam->start_date)->startOfDay();
            $endDate = Carbon::parse($exam->end_date)->endOfDay();

            if ($now->lt($startDate)) {
                $status = 'upcoming';
            } elseif ($now->between($startDate, $endDate)) {
                $status = 'active';
            } else {
                $status = 'finished';
            }

            $exam->status = $status;

            return $exam;
        } catch (AppException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new AppException(
                $e->getMessage(),
                500,
                "Server Error",
                "An unexpected error occurred while fetching the exam details. Please try again later.",
                null
            );
        }
    }
    public function getAssociateWeightedMarkLetterGrades(string $examId, object $currentSchool)
    {
        $results = [];

        $exam = Exam::where("school_branch_id", $currentSchool->id)
            ->with(["examtype"])
            ->find($examId);

        if (!$exam) {
            throw new AppException(
                "The exam you are looking for was not found.",
                404,
                "Exam Not Found",
                "We could not find an exam with the provided ID for this school. Please verify the ID and try again.",
                null
            );
        }

        $letterGrades = Grade::all();

        if ($letterGrades->isEmpty()) {
            throw new AppException(
                "No letter grades have been configured for the system.",
                500,
                "Grades Configuration Missing",
                "The system requires letter grades to be configured before you can associate them with exam marks. Please contact support.",
                null
            );
        }

        foreach ($letterGrades as $letterGrade) {
            $results[] = [
                "letter_grade" => $letterGrade,
                "exam" => $exam,
            ];
        }

        return $results;
    }
    public function addExamGradeScale(string $examId, object $currentSchool, string $gradeScaleCategoryId, object $authAdmin): Exam
    {
        try {
            $gradeScaleCategory = SchoolGradeScaleCategory::where("school_branch_id", $currentSchool->id)
                ->with(['schoolGradeScale', 'systemGradeCategory'])
                ->find($gradeScaleCategoryId);

            if (!$gradeScaleCategory) {
                throw new AppException(
                    "The selected grading configuration was not found.",
                    404,
                    "Grading Configuration Not Found",
                    "We could not find the specified grading configuration for this school. Please verify the ID and try again.",
                    null
                );
            }

            if ($gradeScaleCategory->schoolGradeScale->isEmpty()) {
                throw new AppException(
                    "The selected grading configuration has not been set up completely.",
                    400,
                    "Incomplete Configuration",
                    "You cannot apply an incomplete grading configuration to an exam. Please complete the setup and try again.",
                    null
                );
            }

            $exam = Exam::where("school_branch_id", $currentSchool->id)
                ->with(['examType'])
                ->find($examId);

            if (!$exam) {
                throw new AppException(
                    "The exam you are trying to grade was not found.",
                    404,
                    "Exam Not Found",
                    "We could not find the exam with the provided ID for this school. Please verify the ID and try again.",
                    null
                );
            }

            if (strtolower($exam->examType->type) !== strtolower($gradeScaleCategory->systemGradeCategory->exam_type)) {
                throw new AppException(
                    "Mismatched Exam Type and Grading Category",
                    422,
                    "Incompatible Grading Category",
                    "The selected grading configuration type does not match the type of this exam ({$exam->examType->type}).",
                    null
                );
            }

            if ((float) $exam->max_score !== (float) $gradeScaleCategory->max_score) {
                throw new AppException(
                    "Mismatched Maximum Score",
                    422,
                    "Score Limit Mismatch",
                    "The maximum score of the exam ({$exam->max_score}) does not match the maximum score configured for this grading scale ({$gradeScaleCategory->max_score}).",
                    null
                );
            }

            $exam->grades_category_id = $gradeScaleCategory->id;
            $exam->save();

            return $exam;
        } catch (AppException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new AppException(
                $e->getMessage(),
                500,
                "Server Error",
                "An unexpected error occurred while attaching the grading configuration to the exam. Please try again later.",
                null
            );
        }
    }
    public function bulkAddExamGrading(array $examGradingList, object $currentSchool, object $authAdmin)
    {
        $result = [];
        try {
            DB::beginTransaction();
            $specialtyIds = [];
            foreach ($examGradingList as $examGrading) {
                $gradesConfigId = $examGrading['grades_config_Id'] ?? null;
                $examId = $examGrading['exam_id'] ?? null;

                $gradesConfig = SchoolGradesConfig::where("school_branch_id", $currentSchool->id)
                    ->find($gradesConfigId);

                if (!$gradesConfig) {
                    throw new AppException(
                        "A grading configuration was not found for one of the exams.",
                        404,
                        "Grading Configuration Not Found",
                        "We could not find the specified grading configuration for exam ID: {$examId}.",
                        null
                    );
                }

                if ($gradesConfig->isgrades_configured === false) {
                    throw new AppException(
                        "The selected grading configuration is incomplete.",
                        400,
                        "Incomplete Configuration",
                        "The grading configuration with ID: {$gradesConfigId} has not been fully set up.",
                        null
                    );
                }

                $exam = Exam::where("school_branch_id", $currentSchool->id)->find($examId);

                if (!$exam) {
                    throw new AppException(
                        "An exam was not found.",
                        404,
                        "Exam Not Found",
                        "We could not find the exam with ID: {$examId}.",
                        null
                    );
                }

                $exam->grades_category_id = $gradesConfig->grades_category_id;
                $exam->grading_added = true;
                $exam->save();
                $specialtyIds[] = $exam->specialty_id;
                $result[] = [
                    'grades_config' => $gradesConfig,
                    'exam' => $exam,
                ];
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.exam.add.grade.config"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "examManagement",
                    "action" => "examGradeScale.added",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "Exam Grade Scale Added",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'specialtyIds'   => $specialtyIds,
                'feature'      => 'examGradeScale',
                'message'      => 'Exam Grade Scale Added',
                'data'         => $exam,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new AppException(
                "An unexpected error occurred during the bulk grading assignment. Please try again.",
                500,
                "Bulk Grading Error",
                "A server-side issue prevented the grading from being applied to all exams. Error: " . $e->getMessage(),
                null
            );
        }
    }
    public function getExamsByStudentIdSemesterId(object $currentSchool, string $studentId, string $semesterId)
    {
        $student = Student::where('school_branch_id', $currentSchool->id)
            ->findOrFail($studentId);
        $examQueryConstraints = [
            ['school_branch_id', $currentSchool->id],
            ['specialty_id', $student->specialty_id],
            ['level_id', $student->level_id],
            ['semester_id', $semesterId],
        ];

        $relationshipsToLoad = ['semester', 'examtype', 'specialty', 'level'];

        $exams = Exam::where($examQueryConstraints)
            ->with($relationshipsToLoad)
            ->get();

        $resitExams = ResitExam::where($examQueryConstraints)
            ->with($relationshipsToLoad)
            ->get();

        $allExams = $exams->merge($resitExams);

        $examItems = $allExams->map(function ($exam) {
            $item = [
                "exam_name"         => $exam->examtype->exam_name ?? null,
                "exam_id"           => $exam->id,
                "level_id"          => $exam->level_id,
                "level_name"        => $exam->level?->name ?? null,
                "specialty_id"      => $exam->specialty_id,
                "specialty_name"    => $exam->specialty?->specialty_name ?? null,
                "description"       => $exam->examtype->description ?? null,
                "exam_type_id"      => $exam->exam_type_id,
                "student_batch_id"  => $exam->student_batch_id,
                "timetable_created" => (bool) $exam->timetable_published,
                "result_released"   => (bool) $exam->result_released,
                "start_date"        => $exam->start_date,
                "end_date"          => $exam->end_date,
                "result_message"    => null,
            ];

            if ($exam->result_released == true) {
                $item["result_message"] = [
                    "id"    => (string) Str::uuid(),
                    "title" => ($exam->examtype->exam_name ?? 'Exam') . " Results Are Out!",
                    "body"  => "Well done on completing the exams! Your results are ready.",
                ];
            }

            return $item;
        });

        $semester = Semester::find($semesterId);

        $result = [
            "semester"   => $semester?->name ?? "Semester",
            "semesterId" => $semesterId,
            "exams"      => $examItems->values()->all()
        ];

        return $result;
    }
    public function getExamGradeScale(string $examId,  object $currentSchool)
    {
        $exam = Exam::where('school_branch_id', $currentSchool->id)
            ->with([
                'examtype',
                'semester',
                'level',
                'specialty'
            ])
            ->findOrFail($examId);

        $grades = Grades::where('school_branch_id', $currentSchool->id)
            ->where('grades_category_id', $exam->grades_category_id)
            ->with('lettergrade')
            ->orderBy('minimum_score', 'desc')
            ->get();

        $gradeScale = $grades->map(function ($grade) {
            return [
                "id"             => $grade->id,
                "minimum_score"  => (float) $grade->minimum_score,
                "maximum_score"  => (float) $grade->maximum_score,
                "grade_status"   => $grade->grade_status ?? "N/A",
                "grade"          => $grade->lettergrade?->letter_grade ?? "N/A",
                "determinant"    => $grade->determinant ?? "N/A",
                "grade_points"  => (float) $grade->grade_points,
            ];
        })->values();

        $result = [
            "exam" => [
                "exam_id"         => $exam->id,
                "exam_name"       => $exam->examtype?->exam_name ?? "Unnamed Exam",
                "exam_type"       => $exam->examtype?->type ?? "Unnamed Type",
                "semester"        => $exam->semester?->name ?? "Unknown Semester",
                "semester_id"     => $exam->semester_id,
                "level_name"      => $exam->level?->name ?? "N/A",
                "specialty_name"  => $exam->specialty?->specialty_name ?? "N/A",
            ],
            "grade_scale" => $gradeScale
        ];

        return $result;
    }
    public function getUpcomingExams(object $currentSchool, $student)
    {
        $student = Student::where('school_branch_id', $currentSchool->id)
            ->find($student->id);

        if (!$student) {
            throw new AppException(
                "Student Not Found",
                404,
                "Student Not Found",
                "Student Not Found. Please check to ensure that the student has not been accidentally deleted or dropped."
            );
        }

        $now = Carbon::now();


        $regularExams = Exam::where('school_branch_id', $currentSchool->id)
            ->where('specialty_id', $student->specialty_id)
            ->where('level_id', $student->level_id)
            ->where('end_date', '>=', $now)
            ->with(['examtype', 'semester'])
            ->select('id', 'exam_type_id', 'start_date', 'end_date', 'timetable_published', 'semester_id')
            ->get();

        $resitExams = ResitExam::where('school_branch_id', $currentSchool->id)
            ->where('specialty_id', $student->specialty_id)
            ->where('level_id', $student->level_id)
            ->where('end_date', '>=', $now)
            ->with(['examtype', 'semester'])
            ->select('id', 'exam_type_id', 'start_date', 'end_date', 'timetable_published', 'semester_id')
            ->get();

        $allUpcoming = collect();

        $regularExams->each(function ($exam) use ($allUpcoming) {
            $allUpcoming->push([
                "exam_id"            => $exam->id,
                "exam_name"          => $exam->examtype?->exam_name ?? "Upcoming Exam",
                'description'        => $exam->examtype?->description ?? null,
                "semester"           => $exam->semester?->name ?? "Unknown Semester",
                "start_date"         => $exam->start_date,
                "end_date"           => $exam->end_date,
                "timetable_published" => (bool) $exam->timetable_published,
            ]);
        });

        $resitExams->each(function ($exam) use ($allUpcoming) {
            $allUpcoming->push([
                "exam_id"            => $exam->id,
                "exam_name"          => $exam->examtype?->exam_name ?? "Resit Exam",
                'description'        => $exam->examtype?->description ?? null,
                "semester"           => $exam->semester?->name ?? "Unknown Semester",
                "start_date"         => $exam->start_date,
                "end_date"           => $exam->end_date,
                "timetable_published" => (bool) $exam->timetable_published,
            ]);
        });

        $sorted = $allUpcoming->sortBy('start_date')->values();
        return $sorted;
    }
    public function getAllExamsByStudentId(object $currentSchool, string $studentId)
    {
        $student = Student::where('school_branch_id', $currentSchool->id)
            ->findOrFail($studentId);

        $examQueryConstraints = [
            ['school_branch_id', $currentSchool->id],
            ['specialty_id', $student->specialty_id],
            ['level_id', $student->level_id],
        ];

        $relationshipsToLoad = ['semester', 'examtype', 'specialty', 'level'];

        $exams = Exam::where($examQueryConstraints)
            ->with($relationshipsToLoad)
            ->get();

        $resitExams = ResitExam::where($examQueryConstraints)
            ->with($relationshipsToLoad)
            ->get();

        $allExams = $exams->merge($resitExams);

        $grouped = $allExams->groupBy('semester_id');

        $result = $grouped->map(function ($examsInSemester) {
            $firstExam = $examsInSemester->first();
            $semester = $firstExam->semester;

            $semesterExams = $examsInSemester->map(function ($exam) {
                $item = [
                    "exam_name"         => $exam->examtype->exam_name ?? null,
                    "exam_id"           => $exam->id,
                    "level_id"          => $exam->level_id,
                    "level_name"        => $exam->level?->name ?? null,
                    "specialty_id"      => $exam->specialty_id,
                    "specialty_name"    => $exam->specialty?->specialty_name ?? null,
                    "description"       => $exam->examtype->description ?? null,
                    "exam_type_id"      => $exam->exam_type_id,
                    "student_batch_id"  => $exam->student_batch_id,
                    "timetable_created" => (bool) $exam->timetable_published,
                    "result_released"   => (bool) $exam->result_released,
                    "start_date"        => $exam->start_date,
                    "end_date"          => $exam->end_date,
                    "result_message"    => null,
                ];

                if ($exam->result_released == true) {
                    $item["result_message"] = [
                        "id"    => (string) Str::uuid(),
                        "title" => ($exam->examtype->exam_name ?? 'Exam') . " Results Are Out",
                        "body"  => "Well done on completing the exams! Your results are ready.",
                    ];
                }

                return $item;
            });


            return [
                "semester"   => $semester?->name ?? 'Unknown Semester',
                "semesterId" => $semester?->id ?? $firstExam->semester_id,
                "exams"      => $semesterExams->values()->all()
            ];
        })->values()->all();

        return $result;
    }
    public function getRelatedCaExam(object $currentSchool, string $examTypeId, string $schoolYearId): ?Exam
    {
        $schoolYear = SchoolAcademicYear::where('school_branch_id', $currentSchool->id)
            ->with('specialty')
            ->find($schoolYearId);

        if (!$schoolYear) {
            throw new AppException(
                'Academic Year Not Found',
                404,
                'Academic Year Not Found',
                'The requested school academic year could not be found for this branch. Please verify the selected year.'
            );
        }

        $examType = Examtype::find($examTypeId);

        if (!$examType) {
            throw new AppException(
                'Exam Type Not Found',
                404,
                'Exam Type Not Found',
                'The specified exam type does not exist. Please check your selection and try again.'
            );
        }

        $caExam = Exam::where('school_branch_id', $currentSchool->id)
            ->where('school_year_id', $schoolYear->id)
            ->whereHas('examType', function ($query) use ($examType) {
                $query->where('semester', $examType->semester)
                    ->where('type', 'ca');
            })
            ->with('examType')
            ->first();

        if (!$caExam) {
            throw new AppException(
                'CA Exam Not Found',
                404,
                'CA Exam Not Found',
                'No Continuous Assessment (CA) exam was found for the specified semester and academic year.'
            );
        }

        return $caExam;
    }
}
