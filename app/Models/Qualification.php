<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    use HasUuids;
    protected $fillable = [
        'name',
        'abbreviation',
        'level',
        'status'
    ];

    public $incrementing = false;
    public $table = 'qualifications';
    public $keyType = 'string';

    public function teachers()
    {
        return $this->belongsToMany(
            Teacher::class,
            'teacher_qualifications',
            'qualification_id',
            'teacher_id'
        )->using(TeacherQualification::class)
            ->withPivot(['id', 'school_branch_id', 'field_of_study', 'institution', 'year'])
            ->withTimestamps();
    }
}
