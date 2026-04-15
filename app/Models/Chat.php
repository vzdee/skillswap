<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_one_id',
    'user_two_id',
])]
class Chat extends Model
{
    use HasFactory;

    public const REVIEW_MIN_MESSAGES = 15;

    protected function casts(): array
    {
        return [
            'user_one_review_prompt_dismissed_at' => 'datetime',
            'user_two_review_prompt_dismissed_at' => 'datetime',
        ];
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public static function betweenUsers(int $userAId, int $userBId): ?self
    {
        [$firstUserId, $secondUserId] = self::orderedUserIds($userAId, $userBId);

        return self::query()
            ->where('user_one_id', $firstUserId)
            ->where('user_two_id', $secondUserId)
            ->first();
    }

    public static function messageCountBetweenUsers(int $userAId, int $userBId): int
    {
        $chat = self::betweenUsers($userAId, $userBId);

        if (!$chat) {
            return 0;
        }

        return (int) $chat->messages()->count();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function orderedUserIds(int $userAId, int $userBId): array
    {
        return $userAId <= $userBId
            ? [$userAId, $userBId]
            : [$userBId, $userAId];
    }
}
