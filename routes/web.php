<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatchRequestController;
use App\Http\Controllers\UserReviewController;
use App\Http\Controllers\UserSetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/chat', [ChatController::class, 'Chat'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.chat');

Route::get('/dashboard/chat/{chat}/messages', [ChatController::class, 'messages'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.chat.messages.index');

Route::post('/dashboard/chat/{chat}/messages', [ChatController::class, 'storeMessage'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.chat.messages.store');

Route::post('/dashboard/chat/{chat}/review-suggestion/dismiss', [ChatController::class, 'dismissReviewSuggestion'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.chat.review-suggestion.dismiss');

Route::get('/dashboard/chat/messages/{chatMessage}/attachment', [ChatController::class, 'downloadAttachment'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.chat.messages.attachment');

Route::delete('/dashboard/chat/messages/{chatMessage}', [ChatController::class, 'destroyMessage'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.chat.messages.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/view', [ProfileController::class, 'view'])->name('profile.view');
    Route::get('/profile/view/{user}', [ProfileController::class, 'show'])->name('profile.show');

    Route::post('/onboarding/skills', [UserSetupController::class, 'storeSkills'])->name('onboarding.skills.store');
    Route::post('/onboarding/availability', [UserSetupController::class, 'storeAvailability'])->name('onboarding.availability.store');

    Route::post('/profile/skills/add', [UserSetupController::class, 'addSkill'])->name('profile.skills.add');
    Route::post('/profile/skills/remove', [UserSetupController::class, 'removeSkill'])->name('profile.skills.remove');
    Route::post('/profile/availability/add', [UserSetupController::class, 'addAvailability'])->name('profile.availability.add');
    Route::post('/profile/availability/remove', [UserSetupController::class, 'removeAvailability'])->name('profile.availability.remove');

    Route::post('/matches/request', [MatchRequestController::class, 'store'])->name('matches.request.store');
    Route::post('/matches/request/{matchRequest}/respond', [MatchRequestController::class, 'respond'])->name('matches.request.respond');
    Route::post('/reviews', [UserReviewController::class, 'store'])->name('reviews.store');
});

require __DIR__.'/auth.php';
