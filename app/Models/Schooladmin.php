<?php

namespace App\Models;

use App\Models\ExamTimetable\Invigilator;
use App\Traits\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use App\Models\Job\SystemJob;
use App\Models\OTP;

class Schooladmin extends Authenticatable
{
    use HasFactory, HasApiTokens, HasRoles, HasPermissions, Notifiable, Currency;

    protected $fillable = [
        'id',
        'name',
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'profile_picture',
        'address',
        'phone',
        'school_branch_id',
        'status',
        'gender_id',
        'phone'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public $keyType = 'string';
    public $incrementing = 'false';
    public $table = 'school_admins';
    protected $authTokenColumn = 'token';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class, 'gender_id');
    }
    public function systemJob(): MorphMany
    {
        return $this->morphMany(SystemJob::class, 'initiatedBy');
    }
    public function routeNotificationForFcm()
    {
        return $this->devices()->pluck('token')->toArray();
    }

    public function getMorphClass()
    {
        return static::class;
    }
    public function vote()
    {
        return $this->morphMany(ElectionVotes::class, 'votable');
    }

    public function voteStatus()
    {
        return $this->morphMany(VoterStatus::class, 'votableStatus');
    }

    public function schoolAdminAnnouncement(): HasMany
    {
        return $this->hasMany(SchoolAdminAnnouncement::class);
    }
    public function devices()
    {
        return $this->morphMany(UserDevices::class, 'devicesable');
    }
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_branch_id');
    }

    public function examInvigilator()
    {
        return $this->morphMany(Invigilator::class, 'invigilatable');
    }

    // public function userBadge()
    // {
    //     return $this->morphMany(UserBadge::class, 'actorable');
    // }
    public function passwordResetTokens()
    {
        return $this->morphMany(PasswordResetToken::class, 'actorable');
    }
    public function otp()
    {
        return $this->morphMany(OTP::class, 'actorable');
    }

    public function announcementAuthor(): MorphMany
    {
        return $this->morphMany(AnnouncementAuthor::class, 'authorable');
    }

    public function eventAuthor(): MorphMany
    {
        return $this->morphMany(EventAuthor::class, 'actorable');
    }

    // public function badges()
    // {
    //     return $this->morphMany(BadgeAssignment::class, 'assignable');
    // }
    public function eventAudience()
    {
        return $this->morphMany(EventAudience::class, 'audienceable');
    }
    public function announcementAudience()
    {
        return $this->morphMany(AnnouncementAudience::class, 'audienceable');
    }
}
