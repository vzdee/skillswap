<?php

namespace App\Http\Controllers;

use App\Models\MatchRequest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $availabilityDays = UserSetupController::availabilityDays();
        $availabilityBlocks = UserSetupController::availabilityBlocks();

        $hasSkillsSchema = Schema::hasTable('skills')
            && Schema::hasTable('user_skill')
            && Schema::hasColumn('users', 'skills_onboarding_completed_at');
        $hasAvailabilitySchema = Schema::hasTable('user_availabilities')
            && Schema::hasColumn('users', 'availability_onboarding_completed_at');
        $hasMatchRequestsSchema = Schema::hasTable('match_requests');

        $matches = collect();
        $pendingRequests = collect();

        if ($hasSkillsSchema && $hasAvailabilitySchema) {
            $user->load([
                'taughtSkills:id,name',
                'learningSkills:id,name',
                'availabilities:id,user_id,weekday,time_block',
            ]);

            $myTeachSkillIds = $user->taughtSkills->pluck('id');
            $myLearnSkillIds = $user->learningSkills->pluck('id');
            $myAvailabilityKeys = $user->availabilities
                ->map(fn ($availability) => $availability->weekday . '|' . $availability->time_block)
                ->values();

            $mySkillContext = [
                'teachIds' => $myTeachSkillIds,
                'learnIds' => $myLearnSkillIds,
                'availabilityKeys' => $myAvailabilityKeys,
            ];

            if ($myTeachSkillIds->isNotEmpty() && $myLearnSkillIds->isNotEmpty() && $myAvailabilityKeys->isNotEmpty()) {
                $matchedUsers = User::query()
                    ->whereKeyNot($user->id)
                    ->whereExists(function ($query) use ($user): void {
                        $query->selectRaw('1')
                            ->from('user_skill as candidate_learn')
                            ->join('user_skill as my_teach', function (JoinClause $join) use ($user): void {
                                $join->on('my_teach.skill_id', '=', 'candidate_learn.skill_id')
                                    ->where('my_teach.user_id', $user->id)
                                    ->where('my_teach.type', 'teach');
                            })
                            ->whereColumn('candidate_learn.user_id', 'users.id')
                            ->where('candidate_learn.type', 'learn');
                    })
                    ->whereExists(function ($query) use ($user): void {
                        $query->selectRaw('1')
                            ->from('user_skill as candidate_teach')
                            ->join('user_skill as my_learn', function (JoinClause $join) use ($user): void {
                                $join->on('my_learn.skill_id', '=', 'candidate_teach.skill_id')
                                    ->where('my_learn.user_id', $user->id)
                                    ->where('my_learn.type', 'learn');
                            })
                            ->whereColumn('candidate_teach.user_id', 'users.id')
                            ->where('candidate_teach.type', 'teach');
                    })
                    ->whereExists(function ($query) use ($user): void {
                        $query->selectRaw('1')
                            ->from('user_availabilities as candidate_availability')
                            ->join('user_availabilities as my_availability', function (JoinClause $join) use ($user): void {
                                $join->on('my_availability.weekday', '=', 'candidate_availability.weekday')
                                    ->on('my_availability.time_block', '=', 'candidate_availability.time_block')
                                    ->where('my_availability.user_id', $user->id);
                            })
                            ->whereColumn('candidate_availability.user_id', 'users.id');
                    })
                    ->with([
                        'taughtSkills:id,name',
                        'learningSkills:id,name',
                        'availabilities:id,user_id,weekday,time_block',
                        'receivedReviews:id,reviewed_user_id,rating',
                    ])
                    ->orderBy('name')
                    ->get(['id', 'name', 'email', 'profile_photo_path', 'birth_date', 'career']);

                $relatedRequests = collect();

                if ($hasMatchRequestsSchema && $matchedUsers->isNotEmpty()) {
                    $matchedUserIds = $matchedUsers->pluck('id');

                    $relatedRequests = MatchRequest::query()
                        ->where(function ($query) use ($user, $matchedUserIds): void {
                            $query->where('from_user_id', $user->id)
                                ->whereIn('to_user_id', $matchedUserIds);
                        })
                        ->orWhere(function ($query) use ($user, $matchedUserIds): void {
                            $query->where('to_user_id', $user->id)
                                ->whereIn('from_user_id', $matchedUserIds);
                        })
                        ->orderByDesc('updated_at')
                        ->get(['id', 'from_user_id', 'to_user_id', 'status', 'updated_at']);
                }

                $matches = $matchedUsers
                    ->map(function (User $candidate) use ($user, $mySkillContext, $availabilityDays, $relatedRequests): array {
                        $skillsYouCanTeach = $candidate->taughtSkills
                            ->pluck('name')
                            ->values()
                            ->all();

                        $skillsTheyCanTeach = $candidate->learningSkills
                            ->pluck('name')
                            ->values()
                            ->all();

                        $sharedAvailability = $candidate->availabilities
                            ->filter(fn ($availability) => $mySkillContext['availabilityKeys']->contains($availability->weekday . '|' . $availability->time_block))
                            ->map(function ($availability) use ($availabilityDays): string {
                                $dayLabel = data_get($availabilityDays, $availability->weekday, ucfirst($availability->weekday));

                                return $dayLabel . ' ' . $availability->time_block;
                            })
                            ->values()
                            ->all();

                        [$requestState, $requestId] = $this->resolveRequestState($relatedRequests, $user->id, $candidate->id);

                        return [
                            'user' => $candidate,
                            'photoUrl' => $this->profilePhotoUrl($candidate->profile_photo_path),
                            'age' => $candidate->birth_date?->age,
                            'career' => $this->careerLabel($candidate->career),
                            'reviewsCount' => $candidate->receivedReviews->count(),
                            'averageRating' => $this->averageRatingFromReviews($candidate->receivedReviews),
                            'skillsYouCanTeach' => $skillsYouCanTeach,
                            'skillsTheyCanTeach' => $skillsTheyCanTeach,
                            'sharedAvailability' => $sharedAvailability,
                            'requestState' => $requestState,
                            'requestId' => $requestId,
                        ];
                    })
                    ->filter(function (array $match): bool {
                        return count($match['skillsYouCanTeach']) > 0
                            && count($match['skillsTheyCanTeach']) > 0
                            && count($match['sharedAvailability']) > 0;
                    })
                    ->values();
            }

            if ($hasMatchRequestsSchema) {
                $pendingRequests = MatchRequest::query()
                    ->where('to_user_id', $user->id)
                    ->where('status', 'pending')
                    ->with([
                        'fromUser:id,name,email,profile_photo_path,birth_date,career',
                        'fromUser.taughtSkills:id,name',
                        'fromUser.learningSkills:id,name',
                        'fromUser.availabilities:id,user_id,weekday,time_block',
                        'fromUser.receivedReviews:id,reviewed_user_id,rating',
                    ])
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(function (MatchRequest $pendingRequest) use ($mySkillContext, $availabilityDays): array {
                        $requester = $pendingRequest->fromUser;

                        $skillsTheyCanTeach = $requester->taughtSkills
                            ->pluck('name')
                            ->values()
                            ->all();

                        $theirInterests = $requester->learningSkills
                            ->pluck('name')
                            ->values()
                            ->all();

                        $sharedAvailability = $requester->availabilities
                            ->filter(fn ($availability) => $mySkillContext['availabilityKeys']->contains($availability->weekday . '|' . $availability->time_block))
                            ->map(function ($availability) use ($availabilityDays): string {
                                $dayLabel = data_get($availabilityDays, $availability->weekday, ucfirst($availability->weekday));

                                return $dayLabel . ' ' . $availability->time_block;
                            })
                            ->values()
                            ->all();

                        return [
                            'request' => $pendingRequest,
                            'user' => $requester,
                            'photoUrl' => $this->profilePhotoUrl($requester->profile_photo_path),
                            'age' => $requester->birth_date?->age,
                            'career' => $this->careerLabel($requester->career),
                            'reviewsCount' => $requester->receivedReviews->count(),
                            'averageRating' => $this->averageRatingFromReviews($requester->receivedReviews),
                            'skillsTheyCanTeach' => $skillsTheyCanTeach,
                            'theirInterests' => $theirInterests,
                            'sharedAvailability' => $sharedAvailability,
                        ];
                    })
                    ->values();
            }
        }

        return view('dashboard', [
            'skillsCatalog' => $hasSkillsSchema
                ? Skill::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'availabilityDays' => $availabilityDays,
            'availabilityBlocks' => $availabilityBlocks,
            'matches' => $matches,
            'pendingRequests' => $pendingRequests,
            'requiresSkillsOnboarding' => $hasSkillsSchema && $user->skills_onboarding_completed_at === null,
            'requiresAvailabilityOnboarding' => $hasAvailabilitySchema && $user->availability_onboarding_completed_at === null,
        ]);
    }

    /**
     * Resolve request state between authenticated user and candidate.
     *
     * @return array{0: string, 1: int|null}
     */
    private function resolveRequestState(Collection $requests, int $authUserId, int $candidateId): array
    {
        $pairRequests = $requests->filter(function (MatchRequest $matchRequest) use ($authUserId, $candidateId): bool {
            return ($matchRequest->from_user_id === $authUserId && $matchRequest->to_user_id === $candidateId)
                || ($matchRequest->from_user_id === $candidateId && $matchRequest->to_user_id === $authUserId);
        });

        $pendingSent = $pairRequests->first(fn (MatchRequest $item) => $item->status === 'pending' && $item->from_user_id === $authUserId);
        if ($pendingSent) {
            return ['pending_sent', $pendingSent->id];
        }

        $pendingReceived = $pairRequests->first(fn (MatchRequest $item) => $item->status === 'pending' && $item->to_user_id === $authUserId);
        if ($pendingReceived) {
            return ['pending_received', $pendingReceived->id];
        }

        $accepted = $pairRequests->first(fn (MatchRequest $item) => $item->status === 'accepted');
        if ($accepted) {
            return ['accepted', $accepted->id];
        }

        return ['none', null];
    }

    private function profilePhotoUrl(?string $path): ?string
    {
        return User::buildProfilePhotoUrl($path);
    }

    private function averageRatingFromReviews(Collection $reviews): ?int
    {
        if ($reviews->isEmpty()) {
            return null;
        }

        return (int) round((float) $reviews->avg('rating'));
    }

    private function careerLabel(?string $career): ?string
    {
        if (!$career) {
            return null;
        }

        $labels = [
            'ingenieria_biomedica' => 'Ingenieria biomedica',
            'ingenieria_sistemas' => 'Ingenieria en sistemas',
            'administracion_de_empresas' => 'Administracion de empresas',
            'ingenieria_industrial' => 'Ingenieria industrial',
        ];

        if (array_key_exists($career, $labels)) {
            return $labels[$career];
        }

        return Str::ucfirst(Str::lower(str_replace('_', ' ', $career)));
    }
}
