<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\GeneratesUuid;

class InstructorAvailabilitySlot extends Model
{
    use HasFactory, GeneratesUuid;

    protected $fillable = [
        'school_branch_id',
        'day_of_week',
        'start_time',
        'end_time',
        'availability_id'
    ];


    public $incrementing = 'false';
    public $table = 'teacher_availability_slots';
    public $keyType = 'string';
    public function teacherAvailability(): BelongsTo
    {
        return $this->belongsTo(InstructorAvailability::class, 'availability_id');
    }
}
