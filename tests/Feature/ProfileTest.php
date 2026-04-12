<?php

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
        ->assertSee('4.0/5')
        ->assertSee('Excelente compañero de intercambio.')
        ->assertSee('1 reseña');
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
