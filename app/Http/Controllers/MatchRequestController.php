<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\MatchRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MatchRequestController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $sender = $request->user();
        $target = User::query()->findOrFail($validated['target_user_id']);

        if ($sender->is($target)) {
            return $this->respondWithMessage($request, 'No puedes enviarte una solicitud a ti mismo.', false, 422);
        }

        if (!$this->hasMutualMatch($sender, $target)) {
            return $this->respondWithMessage($request, 'Esta persona ya no cumple las condiciones de match.', false, 422);
        }

        $sameDirection = MatchRequest::query()
            ->where('from_user_id', $sender->id)
            ->where('to_user_id', $target->id)
            ->first();

        if ($sameDirection) {
            if ($sameDirection->status === 'pending') {
                return $this->respondWithMessage($request, 'Ya enviaste una solicitud a esta persona.', false, 422);
            }

            if ($sameDirection->status === 'accepted') {
                return $this->respondWithMessage($request, 'Esta solicitud ya fue aceptada.', false, 422);
            }

            $sameDirection->forceFill([
                'status' => 'pending',
                'responded_at' => null,
            ])->save();

            return $this->respondWithMessage($request, 'Solicitud reenviada correctamente.', true, 200, [
                'target_user_id' => (int) $target->id,
            ]);
        }

        $reversePending = MatchRequest::query()
            ->where('from_user_id', $target->id)
            ->where('to_user_id', $sender->id)
            ->where('status', 'pending')
            ->exists();

        if ($reversePending) {
            return $this->respondWithMessage($request, 'Ya tienes una solicitud pendiente de esta persona.', false, 422);
        }

        $reverseAccepted = MatchRequest::query()
            ->where('from_user_id', $target->id)
            ->where('to_user_id', $sender->id)
            ->where('status', 'accepted')
            ->exists();

        if ($reverseAccepted) {
            return $this->respondWithMessage($request, 'Esta conexion ya fue aceptada.', false, 422);
        }

        MatchRequest::query()->create([
            'from_user_id' => $sender->id,
            'to_user_id' => $target->id,
            'status' => 'pending',
        ]);

        return $this->respondWithMessage($request, 'Solicitud enviada correctamente.', true, 200, [
            'target_user_id' => (int) $target->id,
        ]);
    }

    public function respond(Request $request, MatchRequest $matchRequest): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:accept,reject'],
        ]);

        if ((int) $matchRequest->to_user_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ($matchRequest->status !== 'pending') {
            return $this->respondWithMessage($request, 'Esta solicitud ya fue respondida.', false, 422);
        }

        $status = $validated['action'] === 'accept' ? 'accepted' : 'rejected';

        $matchRequest->forceFill([
            'status' => $status,
            'responded_at' => now(),
        ])->save();

        $chatId = null;

        if ($status === 'accepted') {
            $chatId = $this->findOrCreateChat((int) $matchRequest->from_user_id, (int) $matchRequest->to_user_id)->id;
        }

        if ($status === 'accepted' && !($request->expectsJson() || $request->ajax() || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest')) {
            return redirect()
                ->route('dashboard.chat', ['chat' => $chatId])
                ->with('request_success', 'Solicitud aceptada.');
        }

        return $this->respondWithMessage(
            $request,
            $status === 'accepted' ? 'Solicitud aceptada.' : 'Solicitud rechazada.',
            true,
            200,
            [
                'request_id' => (int) $matchRequest->id,
                'status' => $status,
                'from_user_id' => (int) $matchRequest->from_user_id,
                'to_user_id' => (int) $matchRequest->to_user_id,
                'chat_id' => $chatId,
                'redirect_url' => $status === 'accepted' && $chatId !== null
                    ? route('dashboard.chat', ['chat' => $chatId])
                    : null,
            ]
        );
    }

    private function hasMutualMatch(User $sender, User $target): bool
    {
        $sender->loadMissing([
            'taughtSkills:id,name',
            'learningSkills:id,name',
            'availabilities:id,user_id,weekday,time_block',
        ]);
        $target->loadMissing([
            'taughtSkills:id,name',
            'learningSkills:id,name',
            'availabilities:id,user_id,weekday,time_block',
        ]);

        $senderTeach = $sender->taughtSkills->pluck('id');
        $senderLearn = $sender->learningSkills->pluck('id');
        $targetTeach = $target->taughtSkills->pluck('id');
        $targetLearn = $target->learningSkills->pluck('id');

        $senderAvailability = $sender->availabilities
            ->map(fn ($item) => $item->weekday . '|' . $item->time_block);
        $targetAvailability = $target->availabilities
            ->map(fn ($item) => $item->weekday . '|' . $item->time_block);

        return $senderTeach->intersect($targetLearn)->isNotEmpty()
            && $senderLearn->intersect($targetTeach)->isNotEmpty()
            && $senderAvailability->intersect($targetAvailability)->isNotEmpty();
    }

    private function findOrCreateChat(int $userAId, int $userBId): Chat
    {
        $ordered = collect([$userAId, $userBId])->sort()->values();

        return Chat::query()->firstOrCreate([
            'user_one_id' => (int) $ordered[0],
            'user_two_id' => (int) $ordered[1],
        ]);
    }

    private function respondWithMessage(Request $request, string $message, bool $ok, int $statusCode = 200, array $payload = []): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax() || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest') {
            return response()->json(array_merge([
                'ok' => $ok,
                'message' => $message,
            ], $payload), $statusCode);
        }

        return redirect()->route('dashboard')->with($ok ? 'request_success' : 'request_error', $message);
    }
}
