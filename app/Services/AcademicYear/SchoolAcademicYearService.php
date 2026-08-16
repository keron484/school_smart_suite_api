<?php

namespace App\Services\AcademicYear;

use App\Exceptions\AppException;
use App\Models\AcademicYear\SchoolAcademicYear;
use App\Models\AcademicYear\SystemAcademicYear;
use Carbon\Carbon;
class SchoolAcademicYearService
{
    public function createSchoolAcademicYear(array $data, object $currentSchool)
    {
        $existingAcademicYear = SchoolAcademicYear::where('school_branch_id', $currentSchool->id)
            ->where('system_academic_year_id', $data['system_academic_year_id'])
            ->where('specialty_id', $data['specialty_id'])
            ->with(['systemAcademicYear', 'specialty.level'])
            ->first();

        if ($existingAcademicYear) {
            throw new AppException(
                'An academic year with the same school year and specialty already exists for this school branch.',
                400,
                "Existing Academic Year",
                "Academic Year {$existingAcademicYear->systemAcademicYear->name} for {$existingAcademicYear->specialty->specialty_name}, {$existingAcademicYear->specialty->level->level} already exists."
            );
        }

        $academicYear = SchoolAcademicYear::create([
            'specialty_id' => $data['specialty_id'],
            'school_branch_id' => $currentSchool->id,
            'system_academic_year_id' => $data['system_academic_year_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        return $academicYear;
    }

    public function updateSchoolAcademicYear(
        object $currentSchool,
        string $schoolAcademicYearId,
        array $data
    ) {
        $academicYear = SchoolAcademicYear::where('school_branch_id', $currentSchool->id)
            ->with(['systemAcademicYear', 'specialty'])
            ->find($schoolAcademicYearId);

        if (!$academicYear) {
            throw new AppException(
                'School Academic Year not found.',
                404,
                'Not Found',
                'The school academic year does not exist or has been deleted.'
            );
        }

        $now = now();

        if ($academicYear->end_date->lt($now)) {
            throw new AppException(
                'Academic year closed',
                409,
                'Academic Year Locked',
                'This academic year has ended and can no longer be modified.'
            );
        }

        if ($academicYear->start_date->lte($now)) {
            foreach (['specialty_id', 'system_academic_year_id', 'system_academic_year_id', 'start_date'] as $field) {
                if (array_key_exists($field, $data) && $data[$field] != $academicYear->{$field}) {
                    throw new AppException(
                        'Invalid academic year modification',
                        409,
                        'Immutable Academic Year Field',
                        "The field '{$field}' cannot be modified once the academic year has started."
                    );
                }
            }
        }

        if (isset($data['system_academic_year_id'])) {
            $systemAcademicYear = SystemAcademicYear::find($data['system_academic_year_id']);

            if (!$systemAcademicYear) {
                throw new AppException(
                    'Invalid system academic year',
                    404,
                    'System Academic Year Not Found',
                    'The selected system academic year does not exist.'
                );
            }

            if ($systemAcademicYear->year_end < $now->year) {
                throw new AppException(
                    'System academic year expired',
                    409,
                    'Invalid System Academic Year',
                    'The selected system academic year has already passed.'
                );
            }
        }

        if (
            isset($data['specialty_id']) &&
            $data['specialty_id'] != $academicYear->specialty_id
        ) {
            $conflictingAcademicYear = SchoolAcademicYear::where('school_branch_id', $currentSchool->id)
                ->where('specialty_id', $data['specialty_id'])
                ->where(function ($query) use ($academicYear) {
                    $query
                        ->whereBetween('start_date', [$academicYear->start_date, $academicYear->end_date])
                        ->orWhereBetween('end_date', [$academicYear->start_date, $academicYear->end_date]);
                })
                ->first();

            if ($conflictingAcademicYear) {
                if (
                    $conflictingAcademicYear->start_date->lte($now) ||
                    $conflictingAcademicYear->end_date->lt($now)
                ) {
                    throw new AppException(
                        'Invalid specialty reassignment',
                        409,
                        'Specialty Academic Year Conflict',
                        'You cannot move this academic year to a specialty that already has an active or completed academic year for the same period.'
                    );
                }
            }
        }

        $academicYear->fill($data);
        $academicYear->save();

        return $academicYear->fresh(['systemAcademicYear', 'specialty']);
    }


    public function deleteSchoolAcademicYear(
        object $currentSchool,
        string $schoolAcademicYearId
    ) {
        $academicYear = SchoolAcademicYear::where('school_branch_id', $currentSchool->id)
            ->find($schoolAcademicYearId);

        if (!$academicYear) {
            throw new AppException(
                'School Academic Year not found.',
                404,
                'Not Found',
                'The school academic year does not exist or has been deleted.'
            );
        }

        $now = now();

        if ($academicYear->start_date->lte($now)) {
            throw new AppException(
                'Academic year deletion forbidden',
                409,
                'Academic Year Locked',
                'You cannot delete an academic year that has already started or ended.'
            );
        }

        $academicYear->delete();

        return true;
    }

    public function getSchoolAcademicYears(object $currentSchool)
    {
        $schoolAcademicYears = SchoolAcademicYear::where('school_branch_id', $currentSchool->id)
            ->with(['systemAcademicYear', 'specialty.level'])
            ->orderBy('start_date', 'desc')
            ->get();

        if ($schoolAcademicYears->isEmpty()) {
            throw new AppException(
                'No School Academic Years found for the given school branch.',
                404,
                "School Academic Years Not Found",
                "Not School Academic Year Found For this School Branch Please Create An Academic Year to Continue"
            );
        }

        $today = now()->startOfDay();

        $schoolAcademicYears = $schoolAcademicYears->map(function ($year) use ($today) {
            $start = Carbon::parse($year->start_date)->startOfDay();
            $end   = Carbon::parse($year->end_date)->endOfDay();

            $status = match (true) {
            $today < $start  => 'upcoming',
            $today <= $end   => 'ongoing',
            default          => 'expired'
        };

            return [
                "id" => $year->id,
                "start_date" => $year->start_date,
                "end_date" => $year->end_date,
                "specialty_name" => $year->specialty->specialty_name ?? null,
                "level_name" => $year->specialty->level->name ?? null,
                "level_number" => $year->specialty->level->level ?? null,
                "status" => $status,
                "school_year" => $year->systemAcademicYear->name ?? null,
                "year_start" => $year->systemAcademicYear->year_start ?? null,
                "year_end" => $year->systemAcademicYear->year_end ?? null,
                "created_at" => $year->created_at ?? null,
                "updated_at" => $year->updated_at ?? null,
            ];
        });

        return $schoolAcademicYears;
    }

    public function getSchoolAcademicYearById(object $currentSchool, string $schoolAcademicYearId)
    {
        $academicYear = SchoolAcademicYear::where('school_branch_id', $currentSchool->id)
            ->with(['systemAcademicYear', 'specialty.level'])
            ->find($schoolAcademicYearId);

        if (!$academicYear) {
            throw new AppException(
                'School Academic Year not found for the given school branch.',
                404,
                "Not Found",
                "School Academic Year Not Found For this School Branch, Ensure that it has not been deleted and try again"
            );
        }

        return $academicYear;
    }
}
