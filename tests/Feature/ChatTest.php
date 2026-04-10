<?php

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('participant can send attachment and authorized user can download it', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $partner = User::factory()->create();

    $ordered = collect([$user->id, $partner->id])->sort()->values();

    $chat = Chat::query()->create([
        'user_one_id' => (int) $ordered[0],
        'user_two_id' => (int) $ordered[1],
    ]);

    $file = UploadedFile::fake()->image('evidence.png')->size(500);

    $response = $this
        ->actingAs($user)
        ->post(route('dashboard.chat.messages.store', $chat), [
            'body' => '',
            'attachment' => $file,
        ]);

    $response->assertRedirect(route('dashboard.chat', ['chat' => $chat->id]));

    $message = ChatMessage::query()->where('chat_id', $chat->id)->latest('id')->firstOrFail();
    expect($message->attachment_path)->not()->toBeNull();

    $this
        ->actingAs($partner)
        ->get(route('dashboard.chat.messages.attachment', $message))
        ->assertOk();
});

test('opening chat marks incoming messages as read and receipts are exposed', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $ordered = collect([$sender->id, $receiver->id])->sort()->values();

    $chat = Chat::query()->create([
        'user_one_id' => (int) $ordered[0],
        'user_two_id' => (int) $ordered[1],
    ]);

    $message = ChatMessage::query()->create([
        'chat_id' => $chat->id,
        'user_id' => $sender->id,
        'body' => 'Pendiente de lectura',
    ]);

    $this
        ->actingAs($receiver)
        ->get(route('dashboard.chat', ['chat' => $chat->id]))
        ->assertOk();

    $message->refresh();
    expect($message->read_at)->not()->toBeNull();

    $receiptResponse = $this
        ->actingAs($sender)
        ->getJson(route('dashboard.chat.messages.index', ['chat' => $chat->id, 'after_id' => $message->id]));

    $receiptResponse->assertOk();
    $receiptResponse->assertJsonPath('ok', true);
    expect($receiptResponse->json('read_receipts'))->toContain((int) $message->id);
});
