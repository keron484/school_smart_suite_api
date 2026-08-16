<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;
class TeacherQualification extends Pivot
{
    use HasUuids;
    protected $fillable = [
        'qualification_id',
        'level_id',
        'school_branch_id',
        'field_of_study',
        'year',
        'institution'
    ];

    public $incrementing = false;
    public $table = 'teacher_qualifications';
    public $keyType = 'string';
}
