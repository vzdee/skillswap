<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\MatchRequest;
use App\Models\User;
use App\Models\UserReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserReviewController extends Controller
{
    private const MINIMUM_MESSAGES_FOR_REVIEW = Chat::REVIEW_MIN_MESSAGES;

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'reviewed_user_id' => ['required', 'integer', 'exists:users,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:100'],
        ]);

        $reviewer = $request->user();
        $reviewedUser = User::query()->findOrFail($validated['reviewed_user_id']);

        if ($reviewer->is($reviewedUser)) {
            return $this->respondWithMessage($request, 'No puedes calificarte a ti mismo.', false, 422);
        }

        if (!$this->hasAcceptedMatchBetween($reviewer->id, $reviewedUser->id)) {
            return $this->respondWithMessage($request, 'Solo puedes calificar personas con las que hiciste match.', false, 422);
        }

        if (!$this->hasReachedMinimumMessages($reviewer->id, $reviewedUser->id)) {
            return $this->respondWithMessage(
                $request,
                'Deben intercambiar al menos ' . self::MINIMUM_MESSAGES_FOR_REVIEW . ' mensajes en el chat para dejar una reseña.',
                false,
                422
            );
        }

        UserReview::query()->updateOrCreate(
            [
                'reviewer_id' => $reviewer->id,
                'reviewed_user_id' => $reviewedUser->id,
            ],
            [
                'rating' => $validated['rating'],
                'comment' => trim((string) ($validated['comment'] ?? '')) ?: null,
            ]
        );

        return $this->respondWithMessage($request, 'Reseña guardada correctamente.', true);
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

    private function hasReachedMinimumMessages(int $userAId, int $userBId): bool
    {
        return Chat::messageCountBetweenUsers($userAId, $userBId) >= self::MINIMUM_MESSAGES_FOR_REVIEW;
    }

    private function respondWithMessage(Request $request, string $message, bool $ok, int $statusCode = 200, array $payload = []): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax() || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest') {
            return response()->json(array_merge([
                'ok' => $ok,
                'message' => $message,
            ], $payload), $statusCode);
        }

        return redirect()->back()->with($ok ? 'request_success' : 'request_error', $message);
    }
}