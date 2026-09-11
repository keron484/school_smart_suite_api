<?php

namespace App\Models\AcademicYear;

use App\Models\Course\SemesterJointCourse;
use App\Models\Exam\Exam;
use App\Models\SchoolSemester;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolAcademicYear extends Model
{
    use HasUuids;
    protected $fillable = [
        'specialty_id',
        'school_branch_id',
        'system_academic_year_id',
        'start_date',
        'end_date',
    ];

    protected $table = 'school_academic_years';
    public $incrementing = false;
    public $keyType = 'string';

    public function schoolSemester(): HasMany
    {
        return $this->hasMany(SchoolSemester::class, 'school_year_id');
    }
    public function semesterJointCourse(): HasMany
    {
        return $this->hasMany(SemesterJointCourse::class);
    }
    public function systemAcademicYear(): BelongsTo
    {
        return $this->belongsTo(SystemAcademicYear::class, 'system_academic_year_id');
    }
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

    public function exam(): HasMany
    {
        return $this->hasMany(Exam::class, 'school_year_id');
    }
}
