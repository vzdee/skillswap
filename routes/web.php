<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserSetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/view', [ProfileController::class, 'view'])->name('profile.view');

    Route::post('/onboarding/skills', [UserSetupController::class, 'storeSkills'])->name('onboarding.skills.store');
    Route::post('/onboarding/availability', [UserSetupController::class, 'storeAvailability'])->name('onboarding.availability.store');

    Route::post('/profile/skills/add', [UserSetupController::class, 'addSkill'])->name('profile.skills.add');
    Route::post('/profile/skills/remove', [UserSetupController::class, 'removeSkill'])->name('profile.skills.remove');
    Route::post('/profile/availability/add', [UserSetupController::class, 'addAvailability'])->name('profile.availability.add');
    Route::post('/profile/availability/remove', [UserSetupController::class, 'removeAvailability'])->name('profile.availability.remove');
});

require __DIR__.'/auth.php';
