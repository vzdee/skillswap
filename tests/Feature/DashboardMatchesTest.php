<?php

use App\Models\Skill;
use App\Models\MatchRequest;
use App\Models\UserReview;
use App\Models\User;

test('dashboard shows only users with reciprocal skills and shared availability as matches', function () {
    $laravel = Skill::query()->create(['name' => 'Laravel']);
    $python = Skill::query()->create(['name' => 'Python']);
    $docker = Skill::query()->create(['name' => 'Docker']);
    $excel = Skill::query()->create(['name' => 'Excel']);

    $user = User::factory()->create();
    $matchUser = User::factory()->create(['name' => 'Usuario Match']);
    $missingAvailabilityUser = User::factory()->create(['name' => 'Sin Horario']);
    $missingReciprocalSkillUser = User::factory()->create(['name' => 'Sin Interes Mutuo']);

    $user->skills()->syncWithPivotValues([$laravel->id, $docker->id], ['type' => 'teach'], false);
    $user->skills()->syncWithPivotValues([$python->id], ['type' => 'learn'], false);
    $user->availabilities()->createMany([
        ['weekday' => 'monday', 'time_block' => '7-8 AM'],
        ['weekday' => 'tuesday', 'time_block' => '9-10 AM'],
    ]);

    $matchUser->skills()->syncWithPivotValues([$laravel->id], ['type' => 'learn'], false);
    $matchUser->skills()->syncWithPivotValues([$python->id], ['type' => 'teach'], false);
    $matchUser->availabilities()->createMany([
        ['weekday' => 'monday', 'time_block' => '7-8 AM'],
    ]);
    $matchReviewer = User::factory()->create();
    UserReview::query()->create([
        'reviewer_id' => $matchReviewer->id,
        'reviewed_user_id' => $matchUser->id,
        'rating' => 4,
        'comment' => 'Buen intercambio.',
    ]);

    $missingAvailabilityUser->skills()->syncWithPivotValues([$docker->id], ['type' => 'learn'], false);
    $missingAvailabilityUser->skills()->syncWithPivotValues([$python->id], ['type' => 'teach'], false);
    $missingAvailabilityUser->availabilities()->createMany([
        ['weekday' => 'friday', 'time_block' => '4-5 PM'],
    ]);

    $missingReciprocalSkillUser->skills()->syncWithPivotValues([$laravel->id], ['type' => 'learn'], false);
    $missingReciprocalSkillUser->skills()->syncWithPivotValues([$excel->id], ['type' => 'teach'], false);
    $missingReciprocalSkillUser->availabilities()->createMany([
        ['weekday' => 'monday', 'time_block' => '7-8 AM'],
    ]);

    $pendingUser = User::factory()->create(['name' => 'Usuario Pendiente']);
    $pendingUser->skills()->syncWithPivotValues([$excel->id], ['type' => 'teach'], false);
    $pendingUser->skills()->syncWithPivotValues([$docker->id], ['type' => 'learn'], false);
    $pendingUser->availabilities()->createMany([
        ['weekday' => 'monday', 'time_block' => '7-8 AM'],
    ]);
    UserReview::query()->create([
        'reviewer_id' => $user->id,
        'reviewed_user_id' => $pendingUser->id,
        'rating' => 5,
        'comment' => 'Excelente.',
    ]);

    MatchRequest::query()->create([
        'from_user_id' => $pendingUser->id,
        'to_user_id' => $user->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($user)
        ->get('/dashboard');

    $response->assertOk();

    $matches = $response->viewData('matches');
    expect($matches)->toHaveCount(1);

    $entry = $matches->first();

    expect($entry['user']->is($matchUser))->toBeTrue();
    expect($entry['averageRating'])->toBe(4);
    expect($entry['skillsYouCanTeach'])->toContain('Python');
    expect($entry['skillsTheyCanTeach'])->toContain('Laravel');
    expect($entry['sharedAvailability'])->toContain('Lunes 7-8 AM');

    $pendingRequests = $response->viewData('pendingRequests');
    expect($pendingRequests)->toHaveCount(1);
    expect($pendingRequests->first()['user']->is($pendingUser))->toBeTrue();
    expect($pendingRequests->first()['averageRating'])->toBe(5);
});

test('dashboard has no matches when the user has no reciprocal data yet', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/dashboard');

    $response->assertOk();
    expect($response->viewData('matches'))->toHaveCount(0);
});
