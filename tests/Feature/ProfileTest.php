<?php

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\MatchRequest;
use App\Models\UserReview;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile view shows received reviews and rating summary', function () {
    $reviewedUser = User::factory()->create();
    $reviewer = User::factory()->create();

    MatchRequest::query()->create([
        'from_user_id' => $reviewer->id,
        'to_user_id' => $reviewedUser->id,
        'status' => 'accepted',
        'responded_at' => now(),
    ]);

    UserReview::query()->create([
        'reviewer_id' => $reviewer->id,
        'reviewed_user_id' => $reviewedUser->id,
        'rating' => 4,
        'comment' => 'Excelente compañero de intercambio.',
    ]);

    $response = $this
        ->actingAs($reviewedUser)
        ->get('/profile/view');

    $response
        ->assertOk()
        ->assertSee('Excelente compañero de intercambio.')
        ->assertSee('1 reseña');
});

test('profile review form is locked before ten exchanged messages', function () {
    $viewer = User::factory()->create();
    $target = User::factory()->create();

    MatchRequest::query()->create([
        'from_user_id' => $viewer->id,
        'to_user_id' => $target->id,
        'status' => 'accepted',
        'responded_at' => now(),
    ]);

    $chat = Chat::query()->create([
        'user_one_id' => min($viewer->id, $target->id),
        'user_two_id' => max($viewer->id, $target->id),
    ]);

    foreach (range(1, 9) as $index) {
        ChatMessage::query()->create([
            'chat_id' => $chat->id,
            'user_id' => $index % 2 === 0 ? $target->id : $viewer->id,
            'body' => 'Mensaje #' . $index,
        ]);
    }

    $response = $this
        ->actingAs($viewer)
        ->get(route('profile.show', ['user' => $target->id]));

    $response
        ->assertOk()
        ->assertSee('Actualmente llevan 9 de 10 mensajes.')
        ->assertDontSee('id="profile-review-form"', false);
});

test('profile review form is enabled after ten exchanged messages', function () {
    $viewer = User::factory()->create();
    $target = User::factory()->create();

    MatchRequest::query()->create([
        'from_user_id' => $viewer->id,
        'to_user_id' => $target->id,
        'status' => 'accepted',
        'responded_at' => now(),
    ]);

    $chat = Chat::query()->create([
        'user_one_id' => min($viewer->id, $target->id),
        'user_two_id' => max($viewer->id, $target->id),
    ]);

    foreach (range(1, 10) as $index) {
        ChatMessage::query()->create([
            'chat_id' => $chat->id,
            'user_id' => $index % 2 === 0 ? $target->id : $viewer->id,
            'body' => 'Mensaje #' . $index,
        ]);
    }

    $response = $this
        ->actingAs($viewer)
        ->get(route('profile.show', ['user' => $target->id]));

    $response
        ->assertOk()
        ->assertSee('id="profile-review-form"', false)
        ->assertSee('Selecciona estrellas');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();
    $originalEmail = $user->email;

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame($originalEmail, $user->email);
    $this->assertNotNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
