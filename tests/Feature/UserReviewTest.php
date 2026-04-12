<?php

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