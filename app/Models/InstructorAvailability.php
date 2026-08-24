<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\GeneratesUuid;

class InstructorAvailability extends Model
{
    use GeneratesUuid;
    protected $fillable = [
        'school_branch_id',
        'teacher_id',
        'school_semester_id',
        'status'
    ];

    public $incrementing = 'false';
    public $keyType = 'string';
    public $table = 'teacher_availabilities';

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function schoolSemester(): BelongsTo
    {
        return $this->belongsTo(SchoolSemester::class, 'school_semester_id');
    }

    public function instructorAvailabilitySlot(): HasMany
    {
        return $this->hasMany(InstructorAvailabilitySlot::class, 'availability_id');
    }
}
