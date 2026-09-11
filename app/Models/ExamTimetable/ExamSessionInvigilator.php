<?php

namespace App\Models\ExamTimetable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSessionInvigilator extends Model
{
    use HasUuids;
    protected $fillable = [
        'exam_slot_id',
        'invigilator_id',
        'school_branch_id'
    ];
    public $keyType = 'string';
    public $incrementing = false;
    public $table = "exam_session_invigs";

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ExamTimetableSlot::class, 'exam_slot_id');
    }
    public function invigilator(): BelongsTo
    {
        return $this->belongsTo(ExamInvigilator::class, 'invigilator_id');
    }

}
