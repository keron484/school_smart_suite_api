<?php

namespace App\Jobs\SemesterTimetable;

use App\Events\SemesterTimetable\TimetableGenerationEvent;
use App\Interpreter\SemesterTimetable\Core\DiagnosticResponseBuilder;
use App\Interpreter\SemesterTimetable\DTOs\DiagnosticContext;
use App\Exceptions\AppException;
use App\Models\Course\CourseSpecialty;
use App\Models\Course\JointCourseSlot;
use App\Models\Course\SemesterJoinCourseReference;
use App\Models\Course\SemesterJointCourse;
use App\Models\Job\SystemJob;
use App\Models\SchoolSemester;
use App\Models\Hall;
use App\Models\SemesterTimetable\SemesterTimetableSlot;
use App\Models\SemesterTimetable\SemesterTimetableVersion;
use App\Models\SemesterTimetable\SemesterTimetableError;
use App\Models\SpecialtyHall;
use App\Models\TeacherCoursePreference;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\SemesterTimetable\SemesterTimetable;
use App\Models\Teacher\TeacherSpecialty;
use Illuminate\Support\Str;
use App\Schedular\SemesterTimetable\Engine\SchedularEngine;
use Throwable;

class GenerateFixedSemesterTimetable implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 30;

    protected const PARTIAL = "partial";
    protected const ERROR = "error";
    protected const OPTIMAL = "optimal";
    protected Collection $userErrors;
    public function __construct(
        protected readonly object $currentSchool,
        protected readonly array $payload,
        protected readonly string $jobId,
    ) {
        $this->userErrors = collect();
    }
    public function handle(): void
    {
        $systemJob = SystemJob::with('initiatedBy')->find($this->jobId);
        if (!$systemJob) {
            return;
        }

        $schoolSemester = SchoolSemester::where('school_branch_id', $this->currentSchool->id)
            ->with(['semester', 'schoolYear.specialty.level'])
            ->where('id', $this->payload['school_semester_id'])
            ->firstOrFail();

        try {
            $this->process($systemJob, $schoolSemester);
        } catch (AppException $e) {
            $this->failJob($systemJob, $e->getMessage(), $e->getCode());
            $this->fail($e);
            Log::error('AppException in process method: ' . $e->getMessage());
            Log::error('Exception details: ' . json_encode([
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]));
        } catch (Throwable $e) {
            $this->failJob($systemJob, $e->getMessage(), 500);
            $this->fail($e);
        }
    }
    private function process(SystemJob $systemJob, SchoolSemester $schoolSemester): void
    {
        $this->updateJobProgress($systemJob, 'PROCESSING', 'Gathering Data', 10);

        $branchId    = $this->currentSchool->id;
        $timetableVersionId = $this->payload['version_id'] ?? null;
        Log::info("timetable version: {$timetableVersionId}");
        $specialty = $schoolSemester->schoolYear->specialty;
        $requestPayload = $this->payload;

        TimetableGenerationEvent::dispatch(
            $systemJob->initiatedBy,
            $this->currentSchool,
            [
                'stage' => 'Gathering Data....',
                'percentage' => 10,
                'details' => 'Fetching teachers, courses, halls, and constraints to prepare for timetable generation.',
            ]
        );

        $teachers           = $this->getTeachers($branchId, $specialty);
        $teacherIds         = $teachers->pluck('teacher_id')->toArray();
        $systemJob->systemJobEvent()->create(['event_type' => 'info', 'message' => 'Teachers fetched']);

        $teacherCourses     = $this->getTeacherCourses($branchId, $teacherIds, $schoolSemester);
        $systemJob->systemJobEvent()->create(['event_type' => 'info', 'message' => 'Teacher courses fetched']);

        $halls              = $this->getHalls($branchId, $specialty);
        $systemJob->systemJobEvent()->create(['event_type' => 'info', 'message' => 'Halls fetched']);

        $hallBusyPeriods    = $this->getHallBusyPeriods($branchId, $halls);
        $systemJob->systemJobEvent()->create(['event_type' => 'info', 'message' => 'Hall busy periods fetched']);

        $teacherBusyPeriods = $this->getTeacherBusyPeriods($branchId, $teacherIds);
        $systemJob->systemJobEvent()->create(['event_type' => 'info', 'message' => 'Teacher busy periods fetched']);

        $jointCourses       = $this->getJointCourses($schoolSemester);

        if ($timetableVersionId == null) {
            $timetableVersionId = $this->createTimetableVersion(
                $this->payload['school_semester_id'],
                $this->currentSchool
            );
        }

        if ($this->hasUserErrors()) {
            $this->createUserError($timetableVersionId, $schoolSemester->id);
            $version = SemesterTimetableVersion::find($timetableVersionId);
            $errorCount = $this->userErrors->count();

            TimetableGenerationEvent::dispatch(
                $systemJob->initiatedBy,
                $this->currentSchool,
                [
                    'stage' => 'Validation Failed',
                    'percentage' => 90,
                    'status' => 'failed',
                    'details' => "Timetable generation failed due to {$errorCount} issue(s)",
                    'errors' => $this->userErrors->toArray(),
                    'error_count' => $errorCount,
                    'version' => $version ?? null,
                ]
            );

            if ($version) {
                $version->update(['scheduler_status' => 'error']);
            }

            throw new AppException(
                "Validation Process Failed: {$errorCount} error(s) found",
                422,
                "Validation Error",
                "Please fix the following issues"
            );
        }

        $body = $this->buildRequestBody(
            $teachers,
            $teacherBusyPeriods,
            $teacherCourses,
            $halls,
            $hallBusyPeriods,
            $requestPayload,
            $jointCourses,
        );

        $this->updateJobProgress($systemJob, 'PROCESSING', 'Generating Timetable', 50);

        TimetableGenerationEvent::dispatch(
            $systemJob->initiatedBy,
            $this->currentSchool,
            [
                'stage' => 'Generating Timetable....',
                'percentage' => 50,
                'details' => 'Generating the optimal timetable based on gathered data and constraints.',
            ]
        );

        $schedulingEngine = app(SchedularEngine::class);
        $response = $schedulingEngine->run($body);



        SemesterTimetable::create([
            'school_semester_id' => $schoolSemester->id,
            'timetable_version_id' => $timetableVersionId,
            'school_branch_id' => $this->currentSchool->id,
            'status' => $response->status,
            'timetable_slots' => $response->timetable,
            'request_payload' => $this->payload,
            'raw_diagnostics' => [
                "hard" => $response->diagnostics['hard']->toArray(),
                "soft" => $response->diagnostics['soft']->toArray()
            ],
            "raw_suggestions" => $response->suggestions
        ]);

        $version = SemesterTimetableVersion::find($timetableVersionId);
        $version->update(['scheduler_status' => $response->status]);

        $finalStatus = $this->isErrorResponse($response) ? 'FAILED' : 'COMPLETED';

        TimetableGenerationEvent::dispatch(
            $systemJob->initiatedBy,
            $this->currentSchool,
            [
                'stage' => 'Interpreting Results....',
                'percentage' => 90,
                'details' => 'Interpreting the results from the timetable generation process.',
                'version' => SemesterTimetableVersion::find($timetableVersionId) ?? null,
            ]
        );

        $this->handleDiagnostics($response, $timetableVersionId);
        $this->updateJobProgress($systemJob, $finalStatus, 'Done', 100);
    }
    private function getTeachers(string $branchId, object $specialty): Collection
    {
        $teacherPreferences = TeacherSpecialty::where('school_branch_id', $branchId)
            ->where('specialty_id', $specialty->id)
            ->with(['teacher' => fn($q) => $q->where('status', 'active')])
            ->get();

        if ($teacherPreferences->isEmpty()) {
            $this->addUserError(
                'No Teachers Assigned',
                "No teachers have been assigned to {$specialty->specialty_name} {$specialty->level->name}. Please assign teachers to this specialty before generating a timetable.",
                [
                    'specialty_name' => $specialty->specialty_name,
                    'level_name' => $specialty->level->name,
                    'specialty_id' => $specialty->id,
                    'reason' => 'no_assignments',
                    "path" => "/teacher-specialty"
                ]
            );
            return collect();
        }

        $activeTeachers = $teacherPreferences->filter(function ($preference) {
            return $preference->teacher && $preference->teacher->status === 'active';
        });

        $inactiveTeachers = $teacherPreferences->filter(function ($preference) {
            return !$preference->teacher || $preference->teacher->status !== 'active';
        });

        if ($activeTeachers->isEmpty()) {
            $inactiveCount = $inactiveTeachers->count();
            $inactiveTeacherNames = $inactiveTeachers->pluck('teacher.name')->filter()->values()->toArray();

            $this->addUserError(
                'No Active Teachers Available',
                "{$specialty->specialty_name} {$specialty->level->name} has {$inactiveCount} teacher(s) assigned, but none are active. Please activate at least one teacher before generating a timetable. Inactive teachers: {$inactiveTeacherNames}",
                [
                    'specialty_name' => $specialty->specialty_name,
                    'level_name' => $specialty->level->name,
                    'specialty_id' => $specialty->id,
                    'total_assigned_teachers' => $teacherPreferences->count(),
                    'inactive_teacher_count' => $inactiveCount,
                    'inactive_teacher_names' => $inactiveTeacherNames,
                    "path" => "/teacher-course"
                ]
            );
            return collect();
        }

        return $activeTeachers;
    }
    private function getTeacherCourses(string $branchId, array $teacherIds, SchoolSemester $schoolSemester): Collection
    {
        $specialty = $schoolSemester->schoolYear->specialty;
        $level = $schoolSemester->schoolYear->specialty->level;
        $specialtyId = $schoolSemester->schoolYear->specialty->id;
        $semesterId = $schoolSemester->semester_id;
        $semester = $schoolSemester->semester;
        $allCoursesForSpecialty = CourseSpecialty::where('school_branch_id', $branchId)
            ->where('specialty_id', $specialtyId)
            ->whereHas('course', fn($q) => $q->where('school_branch_id', $branchId)->where('semester_id', $semesterId))
            ->with(['course'])
            ->get();

        if ($allCoursesForSpecialty->isEmpty()) {
            $this->addUserError(
                'No Courses Created',
                "No courses have been created for {$specialty->specialty_name} {$level->name} in {$semester->name}. Please create and assign courses to this specialty before generating a timetable.",
                [
                    'specialty_name' => $specialty->specialty_name ?? null,
                    'level_name' => $level->name ?? null,
                    'semester_name' => $semester->name ?? null,
                    'specialty_id' => $specialtyId ?? null,
                    'semester_id' => $semesterId ?? null,
                    'branch_id' => $branchId,
                    'reason' => 'no_courses_created',
                    'path' => "/courses"
                ]
            );
            return collect();
        }

        // FIXED: Get active course IDs using a more reliable approach
        // First, get all course IDs for this specialty that have active status
        $activeCourseIds = CourseSpecialty::where('school_branch_id', $branchId)
            ->where('specialty_id', $specialtyId)
            ->whereHas(
                'course',
                fn($q) => $q
                    ->where('school_branch_id', $branchId)
                    ->where('semester_id', $semesterId)
                    ->where('status', 'active')
            )
            ->pluck('course_id')
            ->toArray();

        // Then, filter out courses that are also assigned to other specialties
        $courseIdsWithOtherSpecialties = CourseSpecialty::where('school_branch_id', $branchId)
            ->whereIn('course_id', $activeCourseIds)
            ->where('specialty_id', '!=', $specialtyId)
            ->pluck('course_id')
            ->unique()
            ->toArray();

        // Remove courses that belong to other specialties
        $activeCourseIds = array_values(array_diff($activeCourseIds, $courseIdsWithOtherSpecialties));

        $deactivatedCourses = $allCoursesForSpecialty->filter(function ($courseSpecialty) use ($activeCourseIds) {
            return !in_array($courseSpecialty->course_id, $activeCourseIds);
        });

        if (empty($activeCourseIds) && $deactivatedCourses->isNotEmpty()) {
            $deactivatedCourseNames = $deactivatedCourses->pluck('course.course_title')->filter()->values()->toArray();

            $this->addUserError(
                'All Courses Are Deactivated',
                "There are " . $deactivatedCourses->count() . " course(s) assigned to {$specialty->specialty_name} {$level->name}, but all are deactivated. Please activate at least one course before generating a timetable. Deactivated courses: " . implode(', ', array_slice($deactivatedCourseNames, 0, 3)) . ($deactivatedCourses->count() > 3 ? " and " . ($deactivatedCourses->count() - 3) . " more" : ""),
                [
                    'specialty_name' => $specialty->specialty_name ?? null,
                    'level_name' => $level->name ?? null,
                    'semester_name' => $semester->name ?? null,
                    'total_courses' => $allCoursesForSpecialty->count(),
                    'deactivated_course_count' => $deactivatedCourses->count(),
                    'deactivated_course_names' => $deactivatedCourseNames,
                    'specialty_id' => $specialtyId,
                    'semester_id' => $semesterId,
                    'branch_id' => $branchId,
                    'reason' => 'all_courses_deactivated',
                    'path' => '/courses?status=inactive'
                ]
            );
            return collect();
        }

        if (empty($activeCourseIds)) {
            $this->addUserError(
                'No Active Courses Available',
                "No active courses found for {$specialty->specialty_name} {$level->name} in {$semester->name}. Please ensure courses are assigned to this specialty and are active.",
                [
                    'specialty_name' => $specialty->specialty_name ?? null,
                    'level_name' => $level->name ?? null,
                    'semester_name' => $semester->name ?? null,
                    'specialty_id' => $specialtyId ?? null,
                    'semester_id' => $semesterId ?? null,
                    'branch_id' => $branchId,
                    'reason' => 'no_active_courses',
                    'path' => '/courses?status=active'
                ]
            );
            return collect();
        }

        $teacherCourses = TeacherCoursePreference::where('school_branch_id', $branchId)
            ->whereIn('teacher_id', $teacherIds)
            ->whereIn('course_id', $activeCourseIds)
            ->with(['course.courseSpecialty', 'teacher'])
            ->get();

        $assignedCourseIds = $teacherCourses->pluck('course_id')->unique()->toArray();
        $unassignedCourses = collect($activeCourseIds)->filter(function ($courseId) use ($assignedCourseIds) {
            return !in_array($courseId, $assignedCourseIds);
        });

        if ($teacherCourses->isEmpty() && $unassignedCourses->isNotEmpty()) {
            $activeCourseNames = $allCoursesForSpecialty->filter(function ($courseSpecialty) use ($activeCourseIds) {
                return in_array($courseSpecialty->course_id, $activeCourseIds);
            })->pluck('course.course_title')->filter()->values()->toArray();

            $teachers = Teacher::whereIn('id', $teacherIds)->get();
            $activeTeachers = $teachers->filter(fn($teacher) => $teacher->status === 'active');
            $inactiveTeachers = $teachers->filter(fn($teacher) => $teacher->status !== 'active');

            if ($activeTeachers->isEmpty() && $inactiveTeachers->isNotEmpty()) {
                $this->addUserError(
                    'All Teachers Are Inactive',
                    "There are " . count($teacherIds) . " teacher(s) assigned to {$specialty->specialty_name} {$level->name}, but all are inactive. Please activate at least one teacher before generating a timetable.",
                    [
                        'total_teachers' => count($teacherIds),
                        'inactive_teacher_count' => $inactiveTeachers->count(),
                        'inactive_teacher_names' => $inactiveTeachers->pluck('name')->values()->toArray(),
                        'active_course_count' => count($activeCourseIds),
                        'active_course_names' => $activeCourseNames,
                        'specialty_id' => $specialtyId,
                        'semester_id' => $semesterId,
                        'branch_id' => $branchId,
                        'reason' => 'all_teachers_inactive_for_courses',
                        'path' => "/teacher-course"
                    ]
                );
            } else {
                $this->addUserError(
                    'No Teachers Assigned to Courses',
                    "There are " . count($activeCourseIds) . " active course(s) that need to be taught, but no teachers have been assigned to any of them. Please assign teachers to courses before generating a timetable.",
                    [
                        'active_course_count' => count($activeCourseIds),
                        'active_course_names' => $activeCourseNames,
                        'total_teachers_available' => count($teacherIds),
                        'active_teachers_count' => $activeTeachers->count(),
                        'specialty_id' => $specialtyId,
                        'semester_id' => $semesterId,
                        'branch_id' => $branchId,
                        'reason' => 'no_teacher_course_assignments',
                        'path' => "/teacher-course"
                    ]
                );
            }
            return collect();
        }

        if ($unassignedCourses->isNotEmpty()) {
            $unassignedCourseNames = $allCoursesForSpecialty->filter(function ($courseSpecialty) use ($unassignedCourses) {
                return in_array($courseSpecialty->course_id, $unassignedCourses->toArray());
            })->pluck('course.course_title')->filter()->values()->toArray();

            // Check if specific courses have teachers but they are inactive
            $courseTeacherAssignments = TeacherCoursePreference::where('school_branch_id', $branchId)
                ->whereIn('course_id', $unassignedCourses->toArray())
                ->with(['teacher'])
                ->get();

            $coursesWithInactiveTeachers = [];
            foreach ($unassignedCourses as $courseId) {
                $assignments = $courseTeacherAssignments->where('course_id', $courseId);
                if ($assignments->isNotEmpty()) {
                    $hasActiveTeacher = $assignments->contains(fn($assignment) => $assignment->teacher && $assignment->teacher->status === 'active');
                    if (!$hasActiveTeacher) {
                        $course = $allCoursesForSpecialty->firstWhere('course_id', $courseId);
                        if ($course) {
                            $coursesWithInactiveTeachers[] = $course->course->course_title;
                        }
                    }
                }
            }

            if (!empty($coursesWithInactiveTeachers)) {
                $this->addUserError(
                    'Teachers for Some Courses Are Inactive',
                    count($coursesWithInactiveTeachers) . " course(s) have teachers assigned, but those teachers are inactive. Please activate the teachers or assign different teachers to these courses.",
                    [
                        'unassigned_course_count' => $unassignedCourses->count(),
                        'unassigned_course_names' => $unassignedCourseNames,
                        'courses_with_inactive_teachers' => $coursesWithInactiveTeachers,
                        'specialty_id' => $specialtyId,
                        'semester_id' => $semesterId,
                        'branch_id' => $branchId,
                        'reason' => 'courses_have_inactive_teachers',
                        'is_warning' => true,
                        'path' => "/courses"
                    ]
                );
            } else {
                $this->addUserError(
                    'Some Courses Have No Teachers',
                    count($unassignedCourses) . " active course(s) have no teachers assigned. These courses will be skipped during timetable generation. Please assign teachers to these courses.",
                    [
                        'total_active_courses' => count($activeCourseIds),
                        'assigned_course_count' => count($assignedCourseIds),
                        'unassigned_course_count' => $unassignedCourses->count(),
                        'unassigned_course_names' => $unassignedCourseNames,
                        'specialty_id' => $specialtyId,
                        'semester_id' => $semesterId,
                        'branch_id' => $branchId,
                        'reason' => 'some_courses_unassigned',
                        'is_warning' => true,
                        'path' => '/teacher-course'
                    ]
                );
            }
        }

        return $teacherCourses;
    }
    private function getHalls(string $branchId, object $specialty): Collection
    {
        $specialtyHalls = SpecialtyHall::where('school_branch_id', $branchId)
            ->where('specialty_id', $specialty->id)
            ->with([
                'hall' => function ($query) {
                    $query->where('status', 'available');
                },
                'hall.types'
            ])
            ->get();

        if ($specialtyHalls->isEmpty()) {
            $anyHalls = Hall::where('school_branch_id', $branchId)->exists();

            if (!$anyHalls) {
                $this->addUserError(
                    'No Halls Created',
                    "No halls have been created for this school branch. Please create halls before generating a timetable.",
                    [
                        'branch_id' => $branchId,
                        'specialty_name' => $specialty->specialty_name ?? null,
                        'level_name' => $specialty->level->name ?? null,
                        'reason' => 'no_halls_created',
                        'path' => "/hall"
                    ]
                );
            } else {
                $hallsNotAssigned = Hall::where('school_branch_id', $branchId)
                    ->whereDoesntHave('specialtyHall', fn($q) => $q->where('specialty_id', $specialty->id))
                    ->get();

                if ($hallsNotAssigned->isNotEmpty()) {
                    $hallNames = $hallsNotAssigned->pluck('name')->filter()->values()->toArray();

                    $this->addUserError(
                        'No Halls Assigned to Specialty',
                        "There are " . $hallsNotAssigned->count() . " hall(s) available, but none are assigned to {$specialty->specialty_name} {$specialty->level->name}. Please assign halls to this specialty before generating a timetable.",
                        [
                            'specialty_name' => $specialty->specialty_name ?? null,
                            'level_name' => $specialty->level->name ?? null,
                            'specialty_id' => $specialty->id,
                            'total_halls_available' => $hallsNotAssigned->count(),
                            'available_hall_names' => $hallNames,
                            'branch_id' => $branchId,
                            'reason' => 'no_halls_assigned_to_specialty',
                            'path' => "/specialty-hall"
                        ]
                    );
                }
            }

            return collect();
        }

        $halls = $specialtyHalls->pluck('hall')->filter();

        $availableHalls = $halls->filter(function ($hall) {
            return $hall->types->isNotEmpty();
        });

        if ($availableHalls->isEmpty()) {
            $this->addUserError(
                'No Halls Available',
                "All " . $halls->count() . " hall(s) assigned to {$specialty->specialty_name} {$specialty->level->name} have no available hall types. Please ensure at least one hall has a hall type with status 'available'.",
                [
                    'specialty_name' => $specialty->specialty_name ?? null,
                    'level_name' => $specialty->level->name ?? null,
                    'total_halls_assigned' => $halls->count(),
                    'specialty_id' => $specialty->id,
                    'branch_id' => $branchId,
                    'reason' => 'no_halls_available',
                    'path' => "/hall"
                ]
            );
            return collect();
        }

        return $availableHalls;
    }
    private function getHallBusyPeriods(string $branchId, Collection $halls): Collection
    {
        return SemesterTimetableSlot::where('school_branch_id', $branchId)
            ->whereIn('hall_id', $halls->pluck('hall_id')->toArray())
            ->whereHas('schoolSemester', fn($q) => $q->where('end_date', '>=', now()))
            ->with('hall')
            ->get();
    }
    private function getTeacherBusyPeriods(string $branchId, array $teacherIds): Collection
    {
        return SemesterTimetableSlot::where('school_branch_id', $branchId)
            ->whereIn('teacher_id', $teacherIds)
            ->whereHas('schoolSemester', fn($q) => $q->where('end_date', '>=', now()))
            ->with('teacher')
            ->get();
    }
    private function getJointCourses(SchoolSemester $schoolSemester): ?array
    {
        $branchId   = $this->currentSchool->id;
        $semesterId = $schoolSemester->id;

        $existingJointCourses = SemesterJointCourse::where('school_branch_id', $branchId)
            ->where('semester_id', $semesterId)
            ->exists();

        if (!$existingJointCourses) {
            return null;
        }

        $reference = SemesterJoinCourseReference::where('school_branch_id', $branchId)
            ->where('school_semester_id', $semesterId)
            ->first();

        if (!$reference) {
            return null;
        }

        $jointCourseSlots = JointCourseSlot::where('school_branch_id', $branchId)
            ->where('semester_joint_course_id', $reference->semester_joint_course_id)
            ->with(['course', 'teacher', 'hall'])
            ->get();

        if ($jointCourseSlots->isEmpty()) {
            throw new AppException(
                "No Joint Course Slots Found",
                404,
                "No Joint Course Slots Found",
                "Joint courses exist for this semester but no slots have been created. Please create slots for all joint courses before generating the timetable.",
            );
        }

        return $jointCourseSlots
            ->groupBy(fn($slot) => $slot->course->id . '|' . $slot->teacher->id)
            ->map(fn($slots) => [
                'course_id'  => $slots->first()->course->id,
                'teacher_id' => $slots->first()->teacher->id,
                'periods'    => $slots->map(fn($slot) => [
                    'day'        => $slot->day_of_week,
                    'start_time' => $slot->start_time,
                    'end_time'   => $slot->end_time,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
    private function buildRequestBody(
        Collection $teachers,
        Collection  $teacherBusyPeriods,
        Collection $teacherCourses,
        Collection $halls,
        Collection  $hallBusyPeriods,
        array $requestPayload,
        ?array $jointCourses,
    ): array {
        return [
            'teachers'             => $this->formatTeachers($teachers),
            'teacher_busy_period'  => $this->formatTeacherBusyPeriods($teacherBusyPeriods),
            'teacher_courses'      => $this->formatTeacherCourses($teacherCourses),
            'halls'                => $this->formatHalls($halls),
            'hall_busy_periods'    => $this->formatHallBusyPeriods($hallBusyPeriods),
            'soft_constraints'     => $this->buildSoftConstraints($requestPayload),
            'hard_constraints'     => $this->buildHardConstraints($requestPayload, $jointCourses),
        ];
    }
    private function formatTeachers(Collection $teachers): array
    {
        return $teachers->map(fn($t) => [
            'teacher_id' => $t->teacher->id,
            'name'       => $t->teacher->name,
        ])->all();
    }
    private function formatTeacherBusyPeriods(Collection $busyPeriods): array
    {
        return $busyPeriods->map(fn($s) => [
            'start_time'   => $s->start_time,
            'end_time'     => $s->end_time,
            'day'          => $s->day,
            'teacher_id'   => $s->teacher_id,
            'teacher_name' => $s->teacher->name,
        ])->all();
    }
    private function formatTeacherCourses(Collection $teacherCourses): array
    {
        return $teacherCourses->map(fn($c) => [
            'course_id'     => $c->course->id,
            'course_title'  => $c->course->course_title,
            'course_credit' => $c->course->credit,
            'course_type'   => 'theoretical',
            'teacher_id'    => $c->teacher->id,
            'teacher_name'  => $c->teacher->name,
        ])->all();
    }
    private function formatHalls(Collection $halls): array
    {
        return $halls->map(fn($hall) => [
            'hall_name'     => $hall->name,
            'hall_id'       => $hall->id,
            'hall_capacity' => $hall->capacity,
            'hall_type'     => $hall->types->pluck('name')->all(),
        ])->all();
    }
    private function formatHallBusyPeriods(Collection $busyPeriods): array
    {
        return $busyPeriods->map(fn($s) => [
            'hall_id'    => $s->hall->id,
            'hall_name'  => $s->hall->name,
            'start_time' => $s->start_time,
            'end_time'   => $s->end_time,
            'day'        => $s->day,
        ])->all();
    }
    private function buildSoftConstraints(array $requestPayload): array
    {
        return array_filter([
            'course_daily_frequency'      => $requestPayload['course_daily_frequency'] ?? null,
            'course_requested_time_slots' => $requestPayload['course_requested_time_slots'] ?? null,
            'hall_requested_time_windows' => $requestPayload['hall_requested_time_windows'] ?? null,
            'requested_assignments'            => $requestPayload['requested_assignments'] ?? null,
            'teacher_daily_hours'              => $requestPayload['teacher_daily_hours'] ?? null,
            'teacher_requested_time_windows'   => $requestPayload['teacher_requested_time_windows'] ?? null,
            'teacher_weekly_hours'             => $requestPayload['teacher_weekly_hours'] ?? null,
            'schedule_periods_per_day'         => $requestPayload['schedule_periods_per_day'] ?? null,
            'schedule_free_periods_per_day'    => $requestPayload['schedule_free_periods_per_day'] ?? null,
            'requested_free_periods'           => $requestPayload['requested_free_periods'] ?? null,
        ], fn($value) => !is_null($value));
    }
    private function buildHardConstraints(array $requestPayload, ?array $jointCourses): array
    {
        return array_filter([
            'required_joint_course_periods'    => $jointCourses,
            'break_period'                     => $requestPayload['break_period'] ?? null,
            'operational_period'               => $requestPayload['operational_period'] ?? null,
            'schedule_period_duration_minutes' => $requestPayload['schedule_period_duration_minutes'] ?? null,
        ], fn($value) => !is_null($value));
    }
    private function createTimetableVersion(
        string $schoolSemesterId,
        object $currentSchool
    ): string {
        $nextVersion = (SemesterTimetableVersion::where('school_branch_id', $currentSchool->id)
            ->where('school_semester_id', $schoolSemesterId)
            ->max('version_number') ?? 0) + 1;

        $version = SemesterTimetableVersion::create([
            'label'            => "Version {$nextVersion}",
            'scheduler_status' => null,
            'school_branch_id' => $currentSchool->id,
            'school_semester_id'      => $schoolSemesterId,
            'version_number'   => $nextVersion
        ]);

        return $version->id;
    }
    private function isErrorResponse(object $response): bool
    {
        return Str::lower($response->status ?? self::ERROR) === self::ERROR;
    }
    private function updateJobProgress(SystemJob $systemJob, string $status, string $stage, int $progress): void
    {
        $systemJob->update([
            'status'     => $status,
            'stage'      => $stage,
            'progress'   => $progress,
            'updated_at' => Carbon::now(),
        ]);
    }
    private function handleDiagnostics(
        object $schedulerResponse,
        string $timetableVersionId
    ): void {
        $status = $schedulerResponse->status ?? self::ERROR;

        if ($status === self::OPTIMAL) {
            return;
        }
        $isError = $status === self::ERROR;
        $isPartial = $status === self::PARTIAL;

        if ($isError || $isPartial) {
            $rawDiagnostics = $isError
                ? $schedulerResponse->diagnostics['hard']->toArray() ?? null
                : $schedulerResponse->diagnostics['soft']->toArray() ?? null;

            DiagnosticContext::setSchool($this->currentSchool);
            DiagnosticContext::setVersion($timetableVersionId);
            $diagnosticResponseBuilder = app(DiagnosticResponseBuilder::class);
            $diagnostics = $diagnosticResponseBuilder->build($rawDiagnostics);
            $semesterTimetable =  SemesterTimetable::where("school_branch_id", $this->currentSchool->id)
                ->where("timetable_version_id", $timetableVersionId)
                ->first();
            if ($semesterTimetable) {
                $semesterTimetable->update([
                    'parsed_diagnostics' => $diagnostics
                ]);
            }
        }
    }
    private function failJob(SystemJob $systemJob, string $message, int|string $code): void
    {
        $systemJob->update([
            'status'        => 'FAILED',
            'stage'         => 'Failed',
            'progress'      => 0,
            'error_code'    => (string) $code,
            'error_message' => $message,
            'updated_at'    => Carbon::now(),
        ]);

        $systemJob->systemJobEvent()->create([
            'event_type' => 'error',
            'message'    => $message,
        ]);
    }
    public function failed(Throwable $exception): void
    {
        $errorSource = $this->getErrorSource($exception);

        Log::error("GenerateFixedSemesterTimetable job [{$this->jobId}] permanently failed.", [
            'exception' => $exception->getMessage(),
            'error_source' => $errorSource,
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'error_trace' => $exception->getTraceAsString(),
            'school_id' => $this->currentSchool->id,
            'payload'   => $this->payload,
        ]);

        $systemJob = SystemJob::find($this->jobId);

        if ($systemJob) {
            $this->failJob($systemJob, $exception->getMessage(), 500);
        }
    }
    private function getErrorSource(Throwable $exception): string
    {
        $trace = $exception->getTrace();

        if (isset($trace[0])) {
            $class = $trace[0]['class'] ?? 'Unknown';
            $method = $trace[0]['function'] ?? 'Unknown';

            if ($class === self::class || str_contains($class, 'GenerateFixedSemesterTimetable')) {
                return "Job method: {$class}@{$method}";
            }
        }

        foreach ($trace as $index => $frame) {
            $class = $frame['class'] ?? '';
            $method = $frame['function'] ?? '';

            if (str_contains($class, 'Controller')) {
                return "Controller: {$class}@{$method} (trace index: {$index})";
            }

            if (str_contains($class, 'Service')) {
                return "Service: {$class}@{$method} (trace index: {$index})";
            }

            if (str_contains($class, 'Repository')) {
                return "Repository: {$class}@{$method} (trace index: {$index})";
            }

            if (str_contains($class, 'Job')) {
                return "Job: {$class}@{$method} (trace index: {$index})";
            }
        }
        $firstFrame = $trace[0] ?? null;
        if ($firstFrame) {
            return sprintf(
                "Frame 0: %s@%s (line: %s)",
                $firstFrame['class'] ?? 'Unknown',
                $firstFrame['function'] ?? 'Unknown',
                $firstFrame['line'] ?? 'Unknown'
            );
        }

        return "Unknown source (no trace available)";
    }
    private function addUserError(string $title, string $description, array $params = [])
    {
        $this->userErrors->push([
            'title' => $title,
            'description' => $description,
            'additional_params' => $params,
        ]);
    }
    private function hasUserErrors(): bool
    {
        return $this->userErrors->isNotEmpty();
    }
    private function createUserError(string $versionId, string $schoolSemesterId)
    {
        if ($this->userErrors->isEmpty()) {
            return;
        }

        SemesterTimetableError::create([
            'id' => Str::uuid()->toString(),
            'school_semester_id' => $schoolSemesterId,
            'timetable_version_id' => $versionId,
            'school_branch_id' => $this->currentSchool->id,
            'status' => "failed",
            'request_payload' => $this->payload,
            'errors' => $this->userErrors->toArray()
        ]);

        $version = SemesterTimetableVersion::find($versionId);
        $version->update([
            "scheduler_status" => "failed"
        ]);
    }
}
