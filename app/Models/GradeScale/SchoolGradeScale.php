<?php

namespace App\Models\GradeScale;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolGradeScale extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'school_branch_id',
        'letter_grade_id',
        'grade_points',
        'minimum_score',
        'result',
        'resit_result',
        'maximum_score',
        'performance',
        'grades_category_id'
    ];

    protected $casts = [
        'grade_points' => 'float',
        'minimum_score' => 'float',
        'maximum_score' => 'float',
    ];
    public $keyType = 'string';
    public $incrementing = 'false';
    public $table = 'grade_scales';

    public function schoolGradeScaleCategory(): BelongsTo
    {
        return $this->belongsTo(SchoolGradeScaleCategory::class, 'grades_category_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'letter_grade_id');
    }
}
