<?php

use App\Models\MatchRequest;
use App\Models\Skill;
use App\Models\User;

test('user can send a request from matches', function () {
    $teachSkill = Skill::query()->create(['name' => 'Laravel']);
    $learnSkill = Skill::query()->create(['name' => 'Python']);

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $sender->skills()->syncWithPivotValues([$teachSkill->id], ['type' => 'teach'], false);
    $sender->skills()->syncWithPivotValues([$learnSkill->id], ['type' => 'learn'], false);
    $sender->availabilities()->createMany([
        ['weekday' => 'monday', 'time_block' => '7-8 AM'],
    ]);

    $receiver->skills()->syncWithPivotValues([$teachSkill->id], ['type' => 'learn'], false);
    $receiver->skills()->syncWithPivotValues([$learnSkill->id], ['type' => 'teach'], false);
    $receiver->availabilities()->createMany([
        ['weekday' => 'monday', 'time_block' => '7-8 AM'],
    ]);

    $response = $this
        ->actingAs($sender)
        ->post(route('matches.request.store'), [
            'target_user_id' => $receiver->id,
        ]);

    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('match_requests', [
        'from_user_id' => $sender->id,
        'to_user_id' => $receiver->id,
        'status' => 'pending',
    ]);
});

test('receiver can accept a pending match request', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $matchRequest = MatchRequest::query()->create([
        'from_user_id' => $sender->id,
        'to_user_id' => $receiver->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($receiver)
        ->post(route('matches.request.respond', $matchRequest), [
            'action' => 'accept',
        ]);

    $this->assertDatabaseHas('match_requests', [
        'id' => $matchRequest->id,
        'status' => 'accepted',
    ]);

    $ordered = collect([$sender->id, $receiver->id])->sort()->values();

    $this->assertDatabaseHas('chats', [
        'user_one_id' => (int) $ordered[0],
        'user_two_id' => (int) $ordered[1],
    ]);

    $chat = \App\Models\Chat::query()->where([
        'user_one_id' => (int) $ordered[0],
        'user_two_id' => (int) $ordered[1],
    ])->firstOrFail();

    $response->assertRedirect(route('dashboard.chat', ['chat' => $chat->id]));
});

test('pending requests are exposed on dashboard for the receiver', function () {
    $teachSkill = Skill::query()->create(['name' => 'Docker']);
    $learnSkill = Skill::query()->create(['name' => 'Node']);

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $receiver->skills()->syncWithPivotValues([$teachSkill->id], ['type' => 'teach'], false);
    $receiver->skills()->syncWithPivotValues([$learnSkill->id], ['type' => 'learn'], false);
    $receiver->availabilities()->createMany([
        ['weekday' => 'tuesday', 'time_block' => '9-10 AM'],
    ]);

    $sender->skills()->syncWithPivotValues([$learnSkill->id], ['type' => 'teach'], false);
    $sender->skills()->syncWithPivotValues([$teachSkill->id], ['type' => 'learn'], false);
    $sender->availabilities()->createMany([
        ['weekday' => 'tuesday', 'time_block' => '9-10 AM'],
    ]);

    MatchRequest::query()->create([
        'from_user_id' => $sender->id,
        'to_user_id' => $receiver->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($receiver)
        ->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('pendingRequests'))->toHaveCount(1);
});

test('user can send a request via ajax and receive json response', function () {
    $teachSkill = Skill::query()->create(['name' => 'Laravel']);
    $learnSkill = Skill::query()->create(['name' => 'Python']);

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $sender->skills()->syncWithPivotValues([$teachSkill->id], ['type' => 'teach'], false);
    $sender->skills()->syncWithPivotValues([$learnSkill->id], ['type' => 'learn'], false);
    $sender->availabilities()->createMany([
        ['weekday' => 'monday', 'time_block' => '7-8 AM'],
    ]);

    $receiver->skills()->syncWithPivotValues([$teachSkill->id], ['type' => 'learn'], false);
    $receiver->skills()->syncWithPivotValues([$learnSkill->id], ['type' => 'teach'], false);
    $receiver->availabilities()->createMany([
        ['weekday' => 'monday', 'time_block' => '7-8 AM'],
    ]);

    $response = $this
        ->actingAs($sender)
        ->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->post(route('matches.request.store'), [
            'target_user_id' => $receiver->id,
        ]);

    $response->assertOk();
    $response->assertJsonPath('ok', true);
    $response->assertJsonPath('target_user_id', (int) $receiver->id);

    $this->assertDatabaseHas('match_requests', [
        'from_user_id' => $sender->id,
        'to_user_id' => $receiver->id,
        'status' => 'pending',
    ]);
});

test('receiver can reject a pending request via ajax and receive json payload', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $matchRequest = MatchRequest::query()->create([
        'from_user_id' => $sender->id,
        'to_user_id' => $receiver->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($receiver)
        ->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->post(route('matches.request.respond', $matchRequest), [
            'action' => 'reject',
        ]);

    $response->assertOk();
    $response->assertJsonPath('ok', true);
    $response->assertJsonPath('status', 'rejected');
    $response->assertJsonPath('request_id', (int) $matchRequest->id);
    $response->assertJsonPath('from_user_id', (int) $sender->id);

    $this->assertDatabaseHas('match_requests', [
        'id' => $matchRequest->id,
        'status' => 'rejected',
    ]);
});

test('receiver gets chat redirect url when accepting via ajax', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $matchRequest = MatchRequest::query()->create([
        'from_user_id' => $sender->id,
        'to_user_id' => $receiver->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($receiver)
        ->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->post(route('matches.request.respond', $matchRequest), [
            'action' => 'accept',
        ]);

    $response->assertOk();
    $response->assertJsonPath('ok', true);
    $response->assertJsonPath('status', 'accepted');
    $response->assertJsonPath('request_id', (int) $matchRequest->id);
    expect((string) $response->json('redirect_url'))->toContain('/dashboard/chat?chat=');
});
