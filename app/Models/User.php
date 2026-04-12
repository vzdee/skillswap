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
use Illuminate\Support\Str;

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

    public function sentMatchRequests(): HasMany
    {
        return $this->hasMany(MatchRequest::class, 'from_user_id');
    }

    public function receivedMatchRequests(): HasMany
    {
        return $this->hasMany(MatchRequest::class, 'to_user_id');
    }

    public function chatsAsUserOne(): HasMany
    {
        return $this->hasMany(Chat::class, 'user_one_id');
    }

    public function chatsAsUserTwo(): HasMany
    {
        return $this->hasMany(Chat::class, 'user_two_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function givenReviews(): HasMany
    {
        return $this->hasMany(UserReview::class, 'reviewer_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(UserReview::class, 'reviewed_user_id');
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return self::buildProfilePhotoUrl($this->profile_photo_path);
    }

    public static function buildProfilePhotoUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $normalized = str_replace('\\', '/', $path);

        if (Str::startsWith($normalized, ['http://', 'https://', 'data:'])) {
            return $normalized;
        }

        if (Str::startsWith($normalized, '/storage/')) {
            return $normalized;
        }

        if (Str::startsWith($normalized, 'storage/')) {
            return '/' . ltrim($normalized, '/');
        }

        return '/storage/' . ltrim($normalized, '/');
    }
}
