<?php

namespace App\Models;

use App\Models\Badge\UserBadge;
use App\Traits\Currency;
use App\Models\Exam\ExamCandidate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasPermissions;
use App\Models\OTP;
use App\Models\Job\SystemJob;

class Student extends Model
{
    use HasFactory,  HasApiTokens, Notifiable, HasRoles, HasPermissions, Currency;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'first_name',
        'last_name',
        'username',
        'DOB',
        'gender_id',
        'phone',
        'school_branch_id',
        'specialty_id',
        'guardian_id',
        'student_batch_id',
        'account_status',
        'payment_format',
        'dropout_status',
        'email',
        'password',
        "sub_status",
        'profile_picture',
        'relationship_id',
        'student_source_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public $keyType = 'string';
    public $table = 'students';
    public $incrementing = 'false';
    // protected $authTokenColumn = 'token';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'dropout_status' => 'boolean'
        ];
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(StudentParentRelationship::class, 'relationship_id');
    }
    public function systemJob(): MorphMany
    {
        return $this->morphMany(SystemJob::class, 'initiatedBy');
    }
    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class, 'gender_id');
    }

    public function studentSource(): BelongsTo
    {
        return $this->belongsTo(StudentSource::class, 'student_source_id');
    }
    public function routeNotificationForFcm()
    {
        return $this->devices()->pluck('token')->toArray();
    }
    public function studentAnnouncement(): HasMany
    {
        return $this->hasMany(StudentAnnouncement::class);
    }
    public function studentFeeSchedule(): HasMany
    {
        return $this->hasMany(StudentFeeSchedule::class);
    }
    public function resitCandidates(): HasMany
    {
        return $this->hasMany(ResitCandidates::class, 'resit_id');
    }
    public function devices()
    {
        return $this->morphMany(UserDevices::class, 'devicesable');
    }
    public function pastElectionWinners(): HasMany
    {
        return $this->hasMany(PastElectionWinners::class);
    }
    public function currentElectionWinners(): BelongsTo
    {
        return $this->belongsTo(CurrentElectionWinners::class);
    }
    public function resitmarks(): HasMany
    {
        return $this->hasMany(ResitMarks::class, 'student_id');
    }
    public function accessedStudent(): HasMany
    {
        return $this->hasMany(AccessedStudent::class);
    }

    public function studentResults(): HasMany
    {
        return $this->hasMany(StudentResults::class);
    }
    public function passwordResetTokens()
    {
        return $this->morphMany(PasswordResetToken::class, 'actorable');
    }
    public function feeWaiver(): HasMany
    {
        return $this->hasMany(FeeWaiver::class, 'student_id');
    }

    public function otp()
    {
        return $this->morphMany(OTP::class, 'actorable');
    }

    public function userBadge()
    {
        return $this->morphMany(UserBadge::class, 'actorable');
    }

    public function activationCode(): MorphMany
    {
        return $this->morphMany(ActivationCodeUsage::class, 'actorable');
    }

    public function additionalFees(): HasMany
    {
        return $this->hasMany(AdditionalFees::class, 'student_id');
    }

    public function registrationFee(): HasMany
    {
        return $this->hasMany(RegistrationFee::class, 'student_id');
    }

    public function tuitionFees(): HasMany
    {
        return $this->hasMany(TuitionFees::class, 'student_id');
    }
    public function otps()
    {
        return $this->morphMany(OTP::class, 'otpable');
    }
    public function electionVotes(): HasMany
    {
        return $this->hasMany(ElectionVotes::class);
    }
    public function electionCandidate(): HasMany
    {
        return $this->hasMany(ElectionCandidates::class);
    }
    public function electionApplication(): HasMany
    {
        return $this->hasMany(ElectionApplication::class, 'student_id');
    }
    public function courses(): HasMany
    {
        return $this->hasMany(Courses::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exams::class);
    }

    public function feepayment(): HasMany
    {
        return $this->hasMany(Feepayment::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Marks::class, 'student_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Parents::class, 'guardian_id');
    }


    public function vote()
    {
        return $this->morphMany(ElectionVotes::class, 'votable');
    }

    public function voteStatus()
    {
        return $this->morphMany(VoterStatus::class, 'votableStatus');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolbranches(): BelongsTo
    {
        return $this->belongsTo(Schoolbranches::class, 'school_branch_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

    public function resitResults(): HasMany
    {
        return $this->hasMany(ResitResults::class);
    }
    public function studentBatch(): BelongsTo
    {
        return $this->belongsTo(studentBatch::class, 'student_batch_id');
    }

    public function studentresit(): HasMany
    {
        return $this->hasMany(Studentresit::class);
    }

    public function studentParentRelationship()
    {
        return $this->belongsTo(StudentParentRelationship::class, 'relationship_id');
    }

    public function examCandidate(): HasMany
    {
        return $this->hasMany(ExamCandidate::class, 'student_id');
    }
}
