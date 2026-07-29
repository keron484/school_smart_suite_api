<?php

namespace App\Models\Teacher;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Models\Teacher;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSpecialty extends Pivot
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'school_branch_id',
        'teacher_id',
        'specialty_id'
    ];

    public $keyType = 'string';
    public $table = 'teacher_specialty_preferences';
    public $incrementing = false;

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }
}
