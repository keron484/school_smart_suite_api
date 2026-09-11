<?php

namespace App\Models\ExamTimetable;

use App\Models\Exam\Exam;
use App\Models\ExamJointCourse\ExamJCSessionInvig;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExamInvigilator extends Model
{
    use HasUuids;
    protected $fillable = [
        'school_branch_id',
        'exam_id',
        'actorable_id',
        'actorable_type'
    ];

    public $table = "exam_invigilators";
    public $incrementing = false;
    public $keyType = 'string';

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }
    public function invigilatable(): MorphTo
    {
        return $this->morphTo('invigilatable', 'actorable_type', 'actorable_id');
    }
    public function examJcSessionInvig(): HasMany
    {
        return $this->hasMany(ExamJCSessionInvig::class);
    }
    public function examSessionInvig(): HasMany
    {
        return $this->hasMany(ExamSessionInvigilator::class);
    }
}
