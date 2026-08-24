<?php

namespace App\Models;

use App\Models\Course\JointCourseSlot;
use App\Models\ExamJointCourse\ExamJCSessionHall;
use App\Models\ExamTimetable\ExamSessionHall;
use App\Models\SemesterTimetable\SemesterTimetableSlot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hall extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'capacity',
        'status',
        'location',
        'school_branch_id',
        'num_assigned_specialties',
        'assignment_status'
    ];

    protected $casts = [
        'capacity' => 'integer'
    ];
    public $keyType = 'string';
    public $table = 'halls';
    public $incrementing = false;

    public function examJcSessionHall(): HasMany
    {
        return $this->hasMany(ExamJCSessionHall::class);
    }

    public function examSessionHall(): HasMany
    {
        return $this->hasMany(ExamSessionHall::class);
    }
    public function jointCourseSlot(): HasMany
    {
        return $this->hasMany(JointCourseSlot::class);
    }
    public function specialtyHall(): HasMany
    {
        return $this->hasMany(SpecialtyHall::class);
    }

    public function semesterTimetableSlot(): HasMany
    {
        return $this->hasMany(SemesterTimetableSlot::class);
    }
    public function types()
    {
        return $this->belongsToMany(HallType::class, 'school_hall_types')
            ->using(SchoolHallType::class)
            ->withPivot(['id', 'school_branch_id'])
            ->withTimestamps();
    }
    public function syncTypes(array $typeIds)
    {
        return $this->types()->sync(
            collect($typeIds)->mapWithKeys(fn($id) => [
                $id => ['school_branch_id' => $this->school_branch_id],
            ])->toArray()
        );
    }
}
