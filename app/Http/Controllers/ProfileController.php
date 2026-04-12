<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Chat;
use App\Models\MatchRequest;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserReview;
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
        return view('profile.view', $this->buildProfileViewData(
            $request->user(),
            $request->user(),
            $request->boolean('embed')
        ));
    }

    public function show(Request $request, User $user): View
    {
        return view('profile.view', $this->buildProfileViewData(
            $user,
            $request->user(),
            $request->boolean('embed')
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProfileViewData(User $user, ?User $viewer, bool $embeddedProfile = false): array
    {
        $hasSkillsSchema = Schema::hasTable('skills') && Schema::hasTable('user_skill');
        $hasAvailabilitySchema = Schema::hasTable('user_availabilities');
        $hasReviewsSchema = Schema::hasTable('user_reviews');
        $hasMatchRequestsSchema = Schema::hasTable('match_requests');
        $hasChatSchema = Schema::hasTable('chats') && Schema::hasTable('chat_messages');

        $relationsToLoad = [];

        if ($hasSkillsSchema) {
            $relationsToLoad[] = 'taughtSkills:id,name';
            $relationsToLoad[] = 'learningSkills:id,name';
        }

        if ($hasAvailabilitySchema) {
            $relationsToLoad[] = 'availabilities:id,user_id,weekday,time_block';
        }

        if ($hasReviewsSchema) {
            $relationsToLoad[] = 'receivedReviews.reviewer:id,name,profile_photo_path';
        }

        if (count($relationsToLoad) > 0) {
            $user->load($relationsToLoad);
        }

        $receivedReviews = $hasReviewsSchema ? $user->receivedReviews->sortByDesc('created_at')->values() : collect();
        $averageRating = $receivedReviews->isNotEmpty()
            ? (int) round((float) $receivedReviews->avg('rating'))
            : null;

        $minimumMessagesForReview = Chat::REVIEW_MIN_MESSAGES;
        $isOwnProfile = $viewer ? $viewer->is($user) : false;
        $hasAcceptedMatchWithViewer = false;
        $messagesExchangedWithViewer = 0;
        $canLeaveReview = false;
        $existingReview = null;

        if ($viewer && !$isOwnProfile) {
            $hasAcceptedMatchWithViewer = $hasMatchRequestsSchema
                ? $this->hasAcceptedMatchBetween($viewer->id, $user->id)
                : false;

            $messagesExchangedWithViewer = ($hasAcceptedMatchWithViewer && $hasChatSchema)
                ? Chat::messageCountBetweenUsers($viewer->id, $user->id)
                : 0;

            if ($hasReviewsSchema) {
                $existingReview = UserReview::query()
                    ->where('reviewer_id', $viewer->id)
                    ->where('reviewed_user_id', $user->id)
                    ->first();
            }

            $canLeaveReview = $hasReviewsSchema
                && $hasAcceptedMatchWithViewer
                && $messagesExchangedWithViewer >= $minimumMessagesForReview;
        }

        return [
            'user' => $user,
            'taughtSkills' => $hasSkillsSchema ? $user->taughtSkills : collect(),
            'learningSkills' => $hasSkillsSchema ? $user->learningSkills : collect(),
            'availabilities' => $hasAvailabilitySchema ? $user->availabilities : collect(),
            'availabilityDays' => UserSetupController::availabilityDays(),
            'receivedReviews' => $receivedReviews,
            'averageRating' => $averageRating,
            'embeddedProfile' => $embeddedProfile,
            'reviewEligibility' => [
                'isOwnProfile' => $isOwnProfile,
                'hasAcceptedMatch' => $hasAcceptedMatchWithViewer,
                'messagesExchangedCount' => $messagesExchangedWithViewer,
                'minimumMessagesRequired' => $minimumMessagesForReview,
                'canLeaveReview' => $canLeaveReview,
                'existingReview' => $existingReview,
            ],
        ];
    }

    private function hasAcceptedMatchBetween(int $userAId, int $userBId): bool
    {
        return MatchRequest::query()
            ->where(function ($query) use ($userAId, $userBId): void {
                $query->where(function ($innerQuery) use ($userAId, $userBId): void {
                    $innerQuery->where('from_user_id', $userAId)
                        ->where('to_user_id', $userBId);
                })
                ->orWhere(function ($innerQuery) use ($userAId, $userBId): void {
                    $innerQuery->where('from_user_id', $userBId)
                        ->where('to_user_id', $userAId);
                });
            })
            ->where('status', 'accepted')
            ->exists();
    }
}
