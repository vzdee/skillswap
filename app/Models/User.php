<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'profile_photo_path',
    'birth_date',
    'career',
    'skills_onboarding_completed_at',
    'availability_onboarding_completed_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'skills_onboarding_completed_at' => 'datetime',
            'availability_onboarding_completed_at' => 'datetime',
        ];
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skill')
            ->withPivot('type')
            ->withTimestamps();
    }

    public function taughtSkills(): BelongsToMany
    {
        return $this->skills()
            ->wherePivot('type', 'teach')
            ->withPivotValue('type', 'teach');
    }

    public function learningSkills(): BelongsToMany
    {
        return $this->skills()
            ->wherePivot('type', 'learn')
            ->withPivotValue('type', 'learn');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(UserAvailability::class);
    }
}
