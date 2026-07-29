<?php

namespace App\Models;

use App\Models\AcademicYear\SchoolAcademicYear;
use App\Models\ExamJointCourse\ExamJCSessionHall;
use App\Models\ExamTimetable\ExamSessionHall;
use App\Models\Course\CourseSpecialty;
use App\Traits\GeneratesUuid;
use App\Models\Courses;
use App\Models\Teacher\TeacherSpecialty;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialty extends Model
{
    use HasFactory, GeneratesUuid, HasUuids;

    protected $fillable = [
        'department_id',
        'specialty_name',
        'registration_fee',
        'school_fee',
        'level_id',
        'status',
        'description',
        'school_branch_id',
    ];

    protected $casts = [
        'school_fee' => 'decimal:2',
        'registration_fee' => 'decimal:2'
    ];
    public $keyType = 'string';
    public $table = 'specialties';
    public $incrementing = false;

    public function examJcSessionHall(): HasMany
    {
        return $this->hasMany(ExamJCSessionHall::class);
    }
    public function examSessionHall(): HasMany
    {
        return $this->hasMany(ExamSessionHall::class);
    }
    public function courses()
    {
        return $this->belongsToMany(
            Courses::class,              // Related model
            'course_specialties',         // Pivot table name
            'specialty_id',             // Foreign key on pivot for this model
            'course_id'                 // Foreign key on pivot for the related model
        )
            ->using(CourseSpecialty::class)
            ->withPivot(['id'])
            ->withTimestamps();
    }

    public function teachers(){
         return $this->belongsToMany(
             Teacher::class,
             'teacher_specialty_preferences',
             'specialty_id',
             'teacher_id'
         )
         ->using(TeacherSpecialty::class)
         ->withPivot(['id', 'school_branch_id'])
         ->withTimestamps();
    }
    public function resitExamRef(): HasMany
    {
        return $this->hasMany(ResitExamRef::class);
    }
    public function specialtyHall(): HasMany
    {
        return $this->hasMany(SpecialtyHall::class);
    }
    public function examCandidate(): HasMany
    {
        return $this->hasMany(AccessedStudent::class);
    }
    public function instructorAvailability(): HasMany
    {
        return $this->hasMany(InstructorAvailability::class);
    }

    public function instructorAvailabilitySlot(): HasMany
    {
        return $this->hasMany(InstructorAvailabilitySlot::class);
    }
    public function feeSchedule(): HasMany
    {
        return $this->hasMany(FeeSchedule::class);
    }

    public function electionParticipants(): HasMany
    {
        return $this->hasMany(ElectionParticipants::class);
    }
    public function feeWaiver(): HasMany
    {
        return $this->hasMany(FeeWaiver::class, 'specialty_id');
    }
    public function resitResults(): HasMany
    {
        return $this->hasMany(ResitResults::class);
    }
    public function studentFeeSchedule(): HasMany
    {
        return $this->hasMany(StudentFeeSchedule::class);
    }
    public function resitmarks(): HasMany
    {
        return $this->hasMany(ResitMarks::class, 'specialty_id');
    }
    public function studentResults(): HasMany
    {
        return $this->hasMany(StudentResults::class, 'specialty_id');
    }
    public function additionalFees(): HasMany
    {
        return $this->hasMany(AdditionalFees::class, 'specialty_id');
    }
    public function feePaymentSchedule(): HasMany
    {
        return $this->hasMany(FeePaymentSchedule::class, 'specialty_id');
    }
    public function registrationFee(): HasMany
    {
        return $this->hasMany(RegistrationFee::class, 'specialty_id');
    }
    public function tuitionFees(): HasMany
    {
        return $this->hasMany(TuitionFees::class, 'student_id');
    }
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
    public function level(): BelongsTo
    {
        return $this->belongsTo(Educationlevels::class);
    }
    public function exams(): HasMany
    {
        return $this->hasMany(Exams::class);
    }
    public function teacherSpecialty(): HasMany
    {
        return $this->hasMany(TeacherSpecialty::class);
    }
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
    public function schoolbranches(): BelongsTo
    {
        return $this->belongsTo(Schoolbranches::class);
    }

    public function student(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teacher(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }
    public function marks(): HasMany
    {
        return $this->hasMany(Marks::class, 'specialty_id');
    }
    public function studentresit(): HasMany
    {
        return $this->hasMany(Studentresit::class);
    }

    public function eventAudience()
    {
        return $this->morphMany(EventAudience::class, 'audienceable');
    }
    public function announcementAudience()
    {
        return $this->morphMany(AnnouncementAudience::class, 'audienceable');
    }
    public function schoolAcademicYear(): HasMany
    {
        return $this->hasMany(SchoolAcademicYear::class);
    }
}
