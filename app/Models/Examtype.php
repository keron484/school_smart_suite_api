<?php

namespace App\Models;

use App\Models\Exam\Exam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\GeneratesUuid;

class Examtype extends Model
{
    use HasFactory, GeneratesUuid;

    protected $fillable = [
        'semester_id',
        'exam_name',
        'semester',
        'status',
        'type',
        'program_name',
        'description'
    ];


    public $incrementing = 'false';
    public $keyType = 'string';
    public $table = 'exam_types';

    public function semesters(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exams::class, 'exam_type_id');
    }

    public function exam(): HasMany
    {
        return $this->hasMany(Exam::class, 'exam_type_id');
    }

    public function resitExamRef(): HasMany
    {
        return $this->hasMany(ResitExamRef::class);
    }
}
