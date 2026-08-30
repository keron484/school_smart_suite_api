<?php

namespace App\Models\GradeScale;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolGradeScaleCategory extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'school_branch_id',
        'max_score',
        'grades_category_id'
    ];

    protected $casts = [
        'max_score' => 'float'
    ];
    public $keyType = 'string';
    public $incrementing = 'false';
    public $table = 'school_grade_scale_categories';

    public function systemGradeCategory(): BelongsTo
    {
        return $this->belongsTo(SystemGradeScaleCategory::class, 'grades_category_id');
    }

    public function schoolGradeScale(): HasMany
    {
        return $this->hasMany(SchoolGradeScale::class);
    }
}
