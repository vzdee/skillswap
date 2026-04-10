<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $hasSkillsSchema = Schema::hasTable('skills') && Schema::hasTable('user_skill');
        $hasAvailabilitySchema = Schema::hasTable('user_availabilities');

        $relationsToLoad = [];

        if ($hasSkillsSchema) {
            $relationsToLoad[] = 'taughtSkills:id,name';
            $relationsToLoad[] = 'learningSkills:id,name';
        }

        if ($hasAvailabilitySchema) {
            $relationsToLoad[] = 'availabilities:id,user_id,weekday,time_block';
        }

        if (count($relationsToLoad) > 0) {
            $user->load($relationsToLoad);
        }

        $taughtSkills = $hasSkillsSchema ? $user->taughtSkills : collect();
        $learningSkills = $hasSkillsSchema ? $user->learningSkills : collect();
        $availabilities = $hasAvailabilitySchema ? $user->availabilities : collect();

        return view('profile.edit', [
            'user' => $user,
            'skillsCatalog' => $hasSkillsSchema
                ? Skill::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'availabilityDays' => UserSetupController::availabilityDays(),
            'availabilityBlocks' => UserSetupController::availabilityBlocks(),
            'taughtSkills' => $taughtSkills,
            'learningSkills' => $learningSkills,
            'availabilities' => $availabilities,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        unset($validated['profile_photo'], $validated['remove_profile_photo']);

        $user->fill($validated);

        $shouldRemovePhoto = $request->boolean('remove_profile_photo');

        if ($shouldRemovePhoto && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'profile-updated',
                'message' => __('Profile updated successfully.'),
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                ],
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function view(Request $request): View
    {
        $user = $request->user();
        $hasSkillsSchema = Schema::hasTable('skills') && Schema::hasTable('user_skill');
        $hasAvailabilitySchema = Schema::hasTable('user_availabilities');

        $relationsToLoad = [];

        if ($hasSkillsSchema) {
            $relationsToLoad[] = 'taughtSkills:id,name';
            $relationsToLoad[] = 'learningSkills:id,name';
        }

        if ($hasAvailabilitySchema) {
            $relationsToLoad[] = 'availabilities:id,user_id,weekday,time_block';
        }

        if (count($relationsToLoad) > 0) {
            $user->load($relationsToLoad);
        }

        return view('profile.view', [
            'user' => $user,
            'taughtSkills' => $hasSkillsSchema ? $user->taughtSkills : collect(),
            'learningSkills' => $hasSkillsSchema ? $user->learningSkills : collect(),
            'availabilities' => $hasAvailabilitySchema ? $user->availabilities : collect(),
            'availabilityDays' => UserSetupController::availabilityDays(),
        ]);
    }
}
