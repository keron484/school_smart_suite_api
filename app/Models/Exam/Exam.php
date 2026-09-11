<?php

namespace App\Models\Exam;

use App\Models\AcademicYear\SchoolAcademicYear;
use App\Models\ExamTimetable\ExamInvigilator;
use App\Models\Examtype;
use App\Models\GradeScale\SchoolGradeScaleCategory;
use App\Models\Marks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
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

    public function examInvigilator(): HasMany
    {
        return $this->hasMany(ExamInvigilator::class, 'exam_id');
    }
    public function examScore(): HasMany
    {
        return $this->hasMany(Marks::class, 'exam_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolAcademicYear::class, 'school_year_id');
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(Examtype::class, 'exam_type_id');
    }

    public function examGradeScale(): BelongsTo
    {
        return $this->belongsTo(SchoolGradeScaleCategory::class, 'grades_category_id');
    }

    public function examCandidate(): HasMany
    {
        return $this->hasMany(ExamCandidate::class, 'exam_id');
    }
}
