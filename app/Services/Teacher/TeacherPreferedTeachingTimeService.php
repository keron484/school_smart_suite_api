<?php

namespace App\Services\Teacher;

use App\Models\InstructorAvailability;
use App\Models\InstructorAvailabilitySlot;
use App\Models\SchoolSemester;
use App\Models\Teacher;
use App\Models\Teacher\TeacherSpecialty;
use App\Notifications\AvailabilitySubmitted;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

class TeacherPreferedTeachingTimeService
{
    public function createInstructorAvailability(array $instructorAvailabilities, object $currentSchool): array
    {
        DB::beginTransaction();
        $schoolSemester = null;
        $teacher = null;
        $instructorAvailability = null;
        try {
            $result = [];
            foreach ($instructorAvailabilities as $availability) {
                if ($schoolSemester == null) {
                    $schoolSemester = SchoolSemester::where('id', $availability['school_semester_id'])
                        ->where('school_branch_id', $currentSchool->id)
                        ->with(['specialty.level', 'semester'])
                        ->first();
                }
                if ($teacher == null) {
                    $teacher = Teacher::where("school_branch_id", $currentSchool->id)->find($availability['teacher_id']);
                }
                if ($instructorAvailability == null) {
                    $instructorAvailability =  InstructorAvailability::where("school_branch_id", $currentSchool)
                        ->findOrFail($availability['teacher_availability_id']);
                }
                if ($instructorAvailability == 'added') {
                    throw new Exception("Your Preferred Teaching Time for this semester is already Added", 400);
                }
                $availability = new InstructorAvailabilitySlot();
                $availability->school_branch_id = $currentSchool->id;
                $availability->teacher_id = $availability['teacher_id'];
                $availability->day_of_week = $availability['day_of_week'];
                $availability->start_time = $availability['start_time'];
                $availability->end_time = $availability['end_time'];
                $availability->teacher_availability_id = $availability['teacher_availability_id'];
                $availability->save();
                $result[] = $availability;
            }
            $instructorAvailability->save();
            DB::commit();
            $availabilityData = [
                'schoolYear' => $schoolSemester->year,
                'specialty' => $schoolSemester->specialty->specialty_name,
                'level' => $schoolSemester->specialty->level->name,
                'semester' => $schoolSemester->semester->name
            ];
            $teacher->notify(new AvailabilitySubmitted($availabilityData));
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function createAvialabilityByOtherSlots(string $targetAvailabilityId, string $availabilityId, object $currentSchool)
    {
        try {
            DB::beginTransaction();
            $availability = InstructorAvailability::where("school_branch_id", $currentSchool->id)
                ->find($availabilityId);
            if ($availability->status == 'added') {
                throw new Exception("Your Preferred Teaching Time for this semester is already Added", 400);
            }
            $availabilitySlots = InstructorAvailabilitySlot::where("school_branch_id", $currentSchool->id)
                ->where("teacher_availability_id", $targetAvailabilityId)
                ->get();

            foreach ($availabilitySlots as $availabilitySlot) {
                InstructorAvailabilitySlot::create([
                    'teacher_id' => $availabilitySlot['teacher_id'],
                    'day_of_week' => $availabilitySlot['day_of_week'],
                    'school_branch_id' => $currentSchool->id,
                    'start_time' => $availabilitySlot['start_time'],
                    'end_time' => $availabilitySlot['end_time'],
                ]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function deleteAvailabilitySlots(string $availabilityId, object $currentSchool, string $teacherId)
    {
        $result = [];
        $teacherAvailabilitySlots = InstructorAvailabilitySlot::where('teacher_availability_id', $availabilityId)
            ->where('school_branch_id', $currentSchool->id)
            ->where('teacher_id', $teacherId)
            ->get();
        foreach ($teacherAvailabilitySlots as $teacherAvailabilitySlot) {
            $teacherAvailabilitySlot->delete();
            $result[] = $teacherAvailabilitySlot;
        }
        return $result;
    }
    public function getSchoolSemestersByTeacherSpecialtyPreference(object $currentSchool, string $teacherId)
    {
        $specialtyIds = TeacherSpecialty::where('school_branch_id', $currentSchool->id)
            ->where('teacher_id', $teacherId)
            ->distinct()
            ->pluck('specialty_id');

        $schoolSemesters = SchoolSemester::with('specialty.level', 'semester')
            ->where('school_branch_id', $currentSchool->id)
            ->where('status', 'active')
            ->whereIn('specialty_id', $specialtyIds)
            ->get();

        if ($schoolSemesters->isEmpty()) {
            return collect();
        }

        return $schoolSemesters;
    }
    public function bulkUpdateInstructorAvailability(array $instructorAvailabilities, object $currentSchool): array
    {
        DB::beginTransaction();

        try {
            foreach ($instructorAvailabilities as $availability) {
                $existingAvailability = InstructorAvailabilitySlot::where("school_branch_id", $currentSchool->id)
                    ->find($availability['slot_id']);

                if (!$existingAvailability) {
                    throw new Exception('Instructor availability record with ID ' . $availability['slot_id'] . ' not found.');
                }
                $existingAvailability->day_of_week = $availability['day_of_week'];
                $existingAvailability->start_time = $availability['start_time'];
                $existingAvailability->end_time = $availability['end_time'];
                $existingAvailability->save();
            }
            DB::commit();
            return $instructorAvailabilities;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function getInstructorAvailabilities(object $currentSchool)
    {
        $instructorAvailabilities = InstructorAvailability::where("school_branch_id", $currentSchool->id)
            ->with([
                'teacher',
                'schoolSemester.semester',
                'schoolSemester.schoolYear.specialty.level',
                'schoolSemester.schoolYear.systemAcademicYear',
                'instructorAvailabilitySlot'
            ])
            ->get();
        return $instructorAvailabilities->map(fn($availability) => [
            "id" => $availability->id,
            "name" => $availability->teacher->name ?? null,
            "username" => $availability->teacher->username ?? null,
            "profile_picture" => $availability->teacher->profile_picture ?? null,
            "teacher_id" => $availability->teacher->id ?? null,
            "semester" => $availability->schoolSemester?->semester?->name ?? null,
            "semester_id" => $availability->schoolSemester?->semester?->id ?? null,
            "school_semester_id" => $availability->school_semester_id ?? null,
            "specialty_id" => $availability->schoolSemester?->schoolYear?->specialty?->id ?? null,
            "specialty_name" => $availability->schoolSemester?->schoolYear?->specialty?->specialty_name ?? null,
            "level_id" => $availability->schoolSemester?->schoolYear?->specialty?->level?->id ?? null,
            "level_name" => $availability->schoolSemester?->schoolYear?->specialty?->level?->name ?? null,
            "level_number" => $availability->schoolSemester?->schoolYear?->specialty?->level?->level ?? null,
            "academic_year" => $availability->schoolSemester?->schoolYear?->systemAcademicYear?->name ?? null,
            "semester_start_date" => $availability->schoolSemester?->start_date ?? null,
            "semester_end_date" => $availability->schoolSemester?->end_date ?? null,
            "status" => $availability->instructorAvailabilitySlot->count() > 0 ? 'added' : 'not_added'
        ]);
    }
    public function getInstructorAvailabilitesByTeacher(object $currentSchool, string $teacherId)
    {
        $instructorAvailabilities = InstructorAvailability::where("school_branch_id", $currentSchool->id)
            ->where('teacher_id', $teacherId)
            ->with(['teacher', 'level', 'schoolSemester', 'specialty'])
            ->get();
        return $instructorAvailabilities;
    }
    public function getInstructorAvailabilityDetails(object $currentSchool, string $availabilityId)
    {
        $instructorAvailabilty = InstructorAvailability::where("school_branch_id", $currentSchool->id)
            ->with([
                'teacher',
                'schoolSemester.semester',
                'schoolSemester.schoolYear.specialty.level',
                'schoolSemester.schoolYear.systemAcademicYear',
                'instructorAvailabilitySlot'
            ])
            ->find($availabilityId);
        return $instructorAvailabilty;
    }
    public function getAvailabilitySlots(object $currentSchool, string $availabilityId)
    {
        $instructorAvailability = InstructorAvailability::where("school_branch_id", $currentSchool->id)
            ->with([
                'teacher',
                'schoolSemester.semester',
                'schoolSemester.schoolYear.specialty.level',
                'schoolSemester.schoolYear.systemAcademicYear',
                'instructorAvailabilitySlot'
            ])
            ->find($availabilityId);

        if (!$instructorAvailability) {
            return null;
        }

        $slots = $instructorAvailability->instructorAvailabilitySlot;

        $prefTimes = $slots
            ->groupBy('day_of_week')
            ->map(fn($daySlots, $day) => [
                'day'   => $day,
                'short' => substr(strtolower($day), 0, 3),
                'slots' => $daySlots->map(function ($slot) {
                    $startTime = $slot->start_time instanceof Carbon
                        ? $slot->start_time->format('h:i')
                        : date('h:i', strtotime($slot->start_time));

                    $endTime = $slot->end_time instanceof Carbon
                        ? $slot->end_time->format('h:i')
                        : date('h:i', strtotime($slot->end_time));

                    return [
                        'id'         => $slot->id,
                        'start_time' => $slot->start_time,
                        'end_time'   => $slot->end_time,
                        'time_range' => "{$startTime} - {$endTime}",
                    ];
                })->values()->toArray(),
            ])
            ->values()
            ->all();

        $totalWeeklyMinutes = 0;
        $totalDailyHours = [];

        foreach ($slots->groupBy('day_of_week') as $day => $daySlots) {
            $dayMinutes = 0;

            foreach ($daySlots as $slot) {
                $start = Carbon::parse($slot->start_time);
                $end = Carbon::parse($slot->end_time);

                $duration = $start->diffInMinutes($end);
                $dayMinutes += $duration;
                $totalWeeklyMinutes += $duration;
            }

            $totalDailyHours[$day] = round($dayMinutes / 60, 2);
        }

        $latestSlotUpdate = $slots->max('updated_at');
        $parentUpdate = $instructorAvailability->updated_at;

        $lastUpdated = max(
            Carbon::parse($parentUpdate),
            Carbon::parse($latestSlotUpdate)
        );

        return [
            "teacher"         => $instructorAvailability->teacher,
            "school_semester" => $instructorAvailability->schoolSemester,
            "pref_times"      => $prefTimes,
            "summary"         => [
                "total_weekly_hours" => round($totalWeeklyMinutes / 60, 2),
                "total_daily_hours"  => $totalDailyHours,
                "last_updated_at"    => $lastUpdated ? $lastUpdated->toDateTimeString() : null,
                "last_updated_human" => $lastUpdated ? $lastUpdated->diffForHumans() : null,
            ]
        ];
    }
}
