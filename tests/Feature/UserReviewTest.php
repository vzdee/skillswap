<?php

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\MatchRequest;
use App\Models\User;
use App\Models\UserReview;

test('user can leave a review for an accepted match', function () {
    $reviewer = User::factory()->create();
    $reviewedUser = User::factory()->create();

    MatchRequest::query()->create([
        'from_user_id' => $reviewer->id,
        'to_user_id' => $reviewedUser->id,
        'status' => 'accepted',
        'responded_at' => now(),
    ]);

    $chat = Chat::query()->create([
        'user_one_id' => min($reviewer->id, $reviewedUser->id),
        'user_two_id' => max($reviewer->id, $reviewedUser->id),
    ]);

    foreach (range(1, 10) as $index) {
        ChatMessage::query()->create([
            'chat_id' => $chat->id,
            'user_id' => $index % 2 === 0 ? $reviewedUser->id : $reviewer->id,
            'body' => 'Mensaje #' . $index,
        ]);
    }

    $response = $this
        ->actingAs($reviewer)
        ->post(route('reviews.store'), [
            'reviewed_user_id' => $reviewedUser->id,
            'rating' => 5,
            'comment' => 'Muy buena experiencia.',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('user_reviews', [
        'reviewer_id' => $reviewer->id,
        'reviewed_user_id' => $reviewedUser->id,
        'rating' => 5,
        'comment' => 'Muy buena experiencia.',
    ]);
});

test('user cannot review someone without an accepted match', function () {
    $reviewer = User::factory()->create();
    $reviewedUser = User::factory()->create();

    $response = $this
        ->actingAs($reviewer)
        ->post(route('reviews.store'), [
            'reviewed_user_id' => $reviewedUser->id,
            'rating' => 4,
            'comment' => 'No debería guardarse.',
        ]);

    $response->assertSessionHas('request_error');

    $this->assertDatabaseMissing('user_reviews', [
        'reviewer_id' => $reviewer->id,
        'reviewed_user_id' => $reviewedUser->id,
    ]);
});

test('user cannot review before reaching ten exchanged messages', function () {
    $reviewer = User::factory()->create();
    $reviewedUser = User::factory()->create();

    MatchRequest::query()->create([
        'from_user_id' => $reviewer->id,
        'to_user_id' => $reviewedUser->id,
        'status' => 'accepted',
        'responded_at' => now(),
    ]);

    $chat = Chat::query()->create([
        'user_one_id' => min($reviewer->id, $reviewedUser->id),
        'user_two_id' => max($reviewer->id, $reviewedUser->id),
    ]);

    foreach (range(1, 9) as $index) {
        ChatMessage::query()->create([
            'chat_id' => $chat->id,
            'user_id' => $index % 2 === 0 ? $reviewedUser->id : $reviewer->id,
            'body' => 'Mensaje previo #' . $index,
        ]);
    }

    $response = $this
        ->actingAs($reviewer)
        ->post(route('reviews.store'), [
            'reviewed_user_id' => $reviewedUser->id,
            'rating' => 5,
            'comment' => 'Aun no deberia guardarse.',
        ]);

    $response->assertSessionHas('request_error');

    $this->assertDatabaseMissing('user_reviews', [
        'reviewer_id' => $reviewer->id,
        'reviewed_user_id' => $reviewedUser->id,
    ]);
});