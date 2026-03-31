<?php

use App\Models\Skill;
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

    $response = $this
        ->actingAs($user)
        ->get('/dashboard');

    $response->assertOk();

    $matches = $response->viewData('matches');
    expect($matches)->toHaveCount(1);

    $entry = $matches->first();

    expect($entry['user']->is($matchUser))->toBeTrue();
    expect($entry['skillsYouCanTeach'])->toContain('Python');
    expect($entry['skillsTheyCanTeach'])->toContain('Laravel');
    expect($entry['sharedAvailability'])->toContain('Lunes 7-8 AM');
});

test('dashboard has no matches when the user has no reciprocal data yet', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/dashboard');

    $response->assertOk();
    expect($response->viewData('matches'))->toHaveCount(0);
});
