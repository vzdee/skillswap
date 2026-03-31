<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId): bool {
    $chat = Chat::query()->find($chatId);

    if (!$chat) {
        return false;
    }

    return (int) $chat->user_one_id === (int) $user->id
        || (int) $chat->user_two_id === (int) $user->id;
});
