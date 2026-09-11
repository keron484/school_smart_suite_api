<?php

namespace App\Models\Exam;

use App\Models\Exam\Exam;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ExamCandidate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_id',
        'exam_id',
        'school_branch_id'
    ];

    public $table = 'exam_candidates';
    public $keyType = 'string';
    public $incrementing = 'false';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

}
