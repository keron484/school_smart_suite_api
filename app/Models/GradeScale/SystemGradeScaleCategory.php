<?php

namespace App\Models\GradeScale;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemGradeScaleCategory extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'title',
        'status',
        'exam_type'
    ];

    public $keyType = 'string';
    public $incrementing = 'false';
    public $table = 'grade_scale_categories';

    public function schoolGradeScale(): HasMany
    {
        return $this->hasMany(SchoolGradeScaleCategory::class, 'grades_category_id');
    }
}
