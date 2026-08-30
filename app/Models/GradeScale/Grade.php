<?php

namespace App\Models\GradeScale;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'letter_grade',
        'status'
    ];

    public $incrementing = false;
    public $keyType = 'string';
    public $table = "letter_grades";

    public function schoolGradeScale(): HasMany
    {
        return $this->hasMany(SchoolGradeScale::class);
    }
}
