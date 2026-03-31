<?php

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;

test('chat page displays chats for participant', function () {
    $user = User::factory()->create();
    $partner = User::factory()->create(['name' => 'Maria']);

    $ordered = collect([$user->id, $partner->id])->sort()->values();

    $chat = Chat::query()->create([
        'user_one_id' => (int) $ordered[0],
        'user_two_id' => (int) $ordered[1],
    ]);

    ChatMessage::query()->create([
        'chat_id' => $chat->id,
        'user_id' => $partner->id,
        'body' => 'Hola, mensaje inicial',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard.chat', ['chat' => $chat->id]));

    $response->assertOk();
    $response->assertSee('Maria');
    $response->assertSee('Hola, mensaje inicial');
});

test('participant can send and delete own message', function () {
    $user = User::factory()->create();
    $partner = User::factory()->create();

    $ordered = collect([$user->id, $partner->id])->sort()->values();

    $chat = Chat::query()->create([
        'user_one_id' => (int) $ordered[0],
        'user_two_id' => (int) $ordered[1],
    ]);

    $sendResponse = $this
        ->actingAs($user)
        ->post(route('dashboard.chat.messages.store', $chat), [
            'body' => 'Mensaje para borrar',
        ]);

    $sendResponse->assertRedirect(route('dashboard.chat', ['chat' => $chat->id]));

    $message = ChatMessage::query()->where('chat_id', $chat->id)->latest('id')->first();
    expect($message)->not()->toBeNull();

    $deleteResponse = $this
        ->actingAs($user)
        ->delete(route('dashboard.chat.messages.destroy', $message));

    $deleteResponse->assertRedirect(route('dashboard.chat', ['chat' => $chat->id]));
    $this->assertDatabaseMissing('chat_messages', ['id' => $message->id]);
});

test('non participant cannot access another chat', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $intruder = User::factory()->create();

    $ordered = collect([$userA->id, $userB->id])->sort()->values();

    $chat = Chat::query()->create([
        'user_one_id' => (int) $ordered[0],
        'user_two_id' => (int) $ordered[1],
    ]);

    $this
        ->actingAs($intruder)
        ->get(route('dashboard.chat', ['chat' => $chat->id]))
        ->assertForbidden();
});
