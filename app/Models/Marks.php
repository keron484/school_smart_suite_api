<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\GeneratesUuid;
use App\Models\Exam\Exam;
class Marks extends Model
{
    use HasFactory, GeneratesUuid;

    protected $fillable = [
        'student_id',
        'courses_id',
        'exam_id',
        'level_id',
        'score',
        'grade_points',
        'specialty_id',
        'school_branch_id',
        'grade',
        'resit_status',
        'grade_status',
        'gratification',
        'student_batch_id'
    ];

    protected $cast = [
         'score' => 'decimal:2',
         'grade_points' => 'decimal:2'
    ];
    public $keyType = 'string';
    public $incrementing = 'false';
    public $table = 'marks';

    public function course(): BelongsTo {
        return $this->belongsTo(Courses::class, 'courses_id');
    }

    public function student(): BelongsTo {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function exams(): BelongsTo {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function level(): BelongsTo {
        return $this->belongsTo(Educationlevels::class, 'level_id');
    }

    public function specialty(): BelongsTo {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

}
