<?php

namespace App\Models;

use App\Models\AcademicYear\SchoolAcademicYear;
use App\Models\ExamJointCourse\ExamJointCourseRef;
use App\Models\ExamTimetable\ExamTimetableSlot;
use App\Models\ExamTimetable\ExamTimetableVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exams extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'school_branch_id',
        'exam_type_id',
        'start_date',
        'end_date',
        'school_year_id',
        'grades_category_id',
        'max_score'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'max_score' => 'float',
    ];

    public $keyType = 'string';
    public $incrementing = false;
    public $table = 'exams';

    public function examJcRef(): HasMany
    {
        return $this->hasMany(ExamJointCourseRef::class);
    }
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolAcademicYear::class, 'school_year_id');
    }
    public function resitExamRef(): HasMany
    {
        return $this->hasMany(ResitExamRef::class);
    }
    public function resitmarks(): HasMany
    {
        return $this->hasMany(ResitMarks::class, 'exam_id');
    }

    public function courses(): BelongsTo
    {
        return $this->belongsTo(Exams::class);
    }
    public function resitResults(): HasMany
    {
        return $this->hasMany(ResitResults::class);
    }
    public function studentResults(): HasMany
    {
        return $this->hasMany(StudentResults::class);
    }
    public function accessedStudent(): HasMany
    {
        return $this->hasMany(AccessedStudent::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Marks::class, 'exam_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolbranches(): BelongsTo
    {
        return $this->belongsTo(Schoolbranches::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function grade(): HasMany
    {
        return $this->hasMany(Grades::class);
    }

    public function examtype(): BelongsTo
    {
        return $this->belongsTo(Examtype::class, 'exam_type_id');
    }

    public function examtimetable(): HasMany
    {
        return $this->hasMany(Examtimetable::class, 'exam_id');
    }

    public function examTimetableVersion(): HasMany
    {
        return $this->hasMany(ExamTimetableVersion::class, 'exam_id');
    }

    public function studentresit(): HasMany
    {
        return $this->hasMany(Studentresit::class);
    }
    public function level(): BelongsTo
    {
        return $this->belongsTo(Educationlevels::class);
    }

    public function examTimetableSlots(): HasMany
    {
        return $this->hasMany(ExamTimetableSlot::class);
    }
}
