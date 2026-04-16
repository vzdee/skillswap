<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\MatchRequest;
use App\Models\User;
use App\Models\UserReview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ChatController extends Controller
{
    public function Chat(Request $request): View
    {
        $authUser = $request->user();

        $chats = Chat::query()
            ->where(function ($query) use ($authUser): void {
                $query->where('user_one_id', $authUser->id)
                    ->orWhere('user_two_id', $authUser->id);
            })
            ->with([
                'userOne:id,name,profile_photo_path',
                'userTwo:id,name,profile_photo_path',
                'userOne.receivedReviews:id,reviewed_user_id,rating',
                'userTwo.receivedReviews:id,reviewed_user_id,rating',
                'latestMessage',
                'latestMessage.user:id,name',
            ])
            ->get();

        $requestedChatId = $request->query('chat');
        if ($requestedChatId !== null && !$chats->contains('id', (int) $requestedChatId)) {
            abort(403);
        }

        $chatItems = $chats
            ->map(function (Chat $chat) use ($authUser): array {
                $partner = (int) $chat->user_one_id === (int) $authUser->id ? $chat->userTwo : $chat->userOne;
                $lastMessage = $chat->latestMessage;
                $preview = $lastMessage
                    ? ((int) $lastMessage->user_id === (int) $authUser->id ? 'Tú: ' : '') . $lastMessage->body
                    : 'Sin mensajes todavía';

                return [
                    'chat' => $chat,
                    'partner' => $partner,
                    'partnerPhotoUrl' => $this->photoUrl($partner?->profile_photo_path),
                    'partnerAverageRating' => $partner && $partner->receivedReviews->isNotEmpty()
                        ? (int) round((float) $partner->receivedReviews->avg('rating'))
                        : null,
                    'preview' => $preview,
                    'lastAt' => $lastMessage?->created_at,
                ];
            })
            ->sortByDesc(function (array $item) {
                return $item['lastAt']?->getTimestamp() ?? $item['chat']->created_at->getTimestamp();
            })
            ->values();

        $activeChat = $this->resolveActiveChat($chatItems, $requestedChatId);
        $activeMessages = collect();
        $activePartner = null;
        $reviewPrompt = [
            'minimumMessagesRequired' => Chat::REVIEW_MIN_MESSAGES,
            'messagesExchangedCount' => 0,
            'showSuggestion' => false,
            'canLeaveReview' => false,
            'alreadyReviewed' => false,
            'dismissed' => false,
        ];

        if ($activeChat) {
            $this->markIncomingMessagesAsRead($activeChat, (int) $authUser->id);

            $activePartner = (int) $activeChat->user_one_id === (int) $authUser->id
                ? $activeChat->userTwo
                : $activeChat->userOne;

            $activeMessages = $activeChat->messages()
                ->with('user:id,name,profile_photo_path')
                ->orderBy('created_at')
                ->get()
                ->map(function (ChatMessage $message) use ($authUser): array {
                    return [
                        'message' => $message,
                        'isMine' => (int) $message->user_id === (int) $authUser->id,
                        'isRead' => $message->read_at !== null,
                        'photoUrl' => $this->photoUrl($message->user->profile_photo_path),
                    ];
                });

            $messagesExchangedCount = (int) $activeChat->messages()->count();
            $hasAcceptedMatch = Schema::hasTable('match_requests')
                ? $this->hasAcceptedMatchBetween((int) $authUser->id, (int) $activePartner->id)
                : false;
            $alreadyReviewed = Schema::hasTable('user_reviews')
                ? UserReview::query()
                    ->where('reviewer_id', $authUser->id)
                    ->where('reviewed_user_id', $activePartner->id)
                    ->exists()
                : false;
            $dismissed = $this->isReviewSuggestionDismissed($activeChat, (int) $authUser->id);

            $canLeaveReview = $hasAcceptedMatch && $messagesExchangedCount >= Chat::REVIEW_MIN_MESSAGES;

            $reviewPrompt = [
                'minimumMessagesRequired' => Chat::REVIEW_MIN_MESSAGES,
                'messagesExchangedCount' => $messagesExchangedCount,
                'showSuggestion' => $canLeaveReview && !$dismissed,
                'canLeaveReview' => $canLeaveReview,
                'alreadyReviewed' => $alreadyReviewed,
                'dismissed' => $dismissed,
            ];
        }

        return view('chat.index', [
            'chatItems' => $chatItems,
            'activeChat' => $activeChat,
            'activeMessages' => $activeMessages,
            'activePartner' => $activePartner,
            'activePartnerPhotoUrl' => $this->photoUrl($activePartner?->profile_photo_path),
            'reviewPrompt' => $reviewPrompt,
        ]);
    }

    public function storeMessage(Request $request, Chat $chat): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->ensureParticipant($chat, (int) $user->id);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:2048'],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $attachment = $request->file('attachment');

        if ($body === '' && !$attachment) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Escribe un mensaje o adjunta un archivo.',
                ], 422);
            }

            return redirect()
                ->route('dashboard.chat', ['chat' => $chat->id])
                ->with('request_error', 'Escribe un mensaje o adjunta un archivo.');
        }

        $messageData = [
            'user_id' => $user->id,
            'body' => $body,
        ];

        if ($attachment) {
            $messageData['attachment_path'] = $attachment->store('chat-attachments', 'local');
            $messageData['attachment_name'] = $attachment->getClientOriginalName();
            $messageData['attachment_mime'] = $attachment->getClientMimeType();
            $messageData['attachment_size'] = $attachment->getSize();
        }

        $message = $chat->messages()->create($messageData);
        $messagesExchangedCount = (int) $chat->messages()->count();

        $message->load('user:id,name,profile_photo_path');
        $item = $this->serializeMessage($message, $user->id);

        try {
            broadcast(new ChatMessageSent((int) $chat->id, $item))->toOthers();
        } catch (Throwable $exception) {
            report($exception);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Mensaje enviado.',
                'chat_id' => (int) $chat->id,
                'item' => $item,
                'messages_exchanged_count' => $messagesExchangedCount,
                'review_threshold' => Chat::REVIEW_MIN_MESSAGES,
                'review_suggestion_dismissed' => $this->isReviewSuggestionDismissed($chat, (int) $user->id),
            ]);
        }

        return redirect()->route('dashboard.chat', ['chat' => $chat->id]);
    }

    public function destroyMessage(Request $request, ChatMessage $chatMessage): RedirectResponse|JsonResponse
    {
        $userId = (int) $request->user()->id;
        $chat = $chatMessage->chat;
        $this->ensureParticipant($chat, $userId);

        if ((int) $chatMessage->user_id !== $userId) {
            abort(403);
        }

        $chatId = $chatMessage->chat_id;
        $messageId = (int) $chatMessage->id;

        if ($chatMessage->attachment_path && Storage::disk('local')->exists($chatMessage->attachment_path)) {
            Storage::disk('local')->delete($chatMessage->attachment_path);
        }

        $chatMessage->delete();
        $messagesExchangedCount = (int) $chat->messages()->count();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Mensaje eliminado.',
                'chat_id' => (int) $chatId,
                'message_id' => $messageId,
                'messages_exchanged_count' => $messagesExchangedCount,
                'review_threshold' => Chat::REVIEW_MIN_MESSAGES,
                'review_suggestion_dismissed' => $this->isReviewSuggestionDismissed($chat, $userId),
            ]);
        }

        return redirect()->route('dashboard.chat', ['chat' => $chatId]);
    }

    public function messages(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();
        $this->ensureParticipant($chat, (int) $user->id);
        $this->markIncomingMessagesAsRead($chat, (int) $user->id);

        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $afterId = (int) ($validated['after_id'] ?? 0);

        $messages = $chat->messages()
            ->with('user:id,name,profile_photo_path')
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $message) => $this->serializeMessage($message, (int) $user->id))
            ->values();

        $readReceipts = $chat->messages()
            ->where('user_id', $user->id)
            ->whereNotNull('read_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json([
            'ok' => true,
            'chat_id' => (int) $chat->id,
            'items' => $messages,
            'read_receipts' => $readReceipts,
            'messages_exchanged_count' => (int) $chat->messages()->count(),
            'review_threshold' => Chat::REVIEW_MIN_MESSAGES,
            'review_suggestion_dismissed' => $this->isReviewSuggestionDismissed($chat, (int) $user->id),
        ]);
    }

    public function dismissReviewSuggestion(Request $request, Chat $chat): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $this->ensureParticipant($chat, $userId);

        if ((int) $chat->user_one_id === $userId) {
            $chat->forceFill([
                'user_one_review_prompt_dismissed_at' => now(),
            ])->save();
        } else {
            $chat->forceFill([
                'user_two_review_prompt_dismissed_at' => now(),
            ])->save();
        }

        return response()->json([
            'ok' => true,
            'chat_id' => (int) $chat->id,
            'review_suggestion_dismissed' => true,
        ]);
    }

    public function downloadAttachment(Request $request, ChatMessage $chatMessage)
    {
        $chat = $chatMessage->chat;
        $this->ensureParticipant($chat, (int) $request->user()->id);

        if (!$chatMessage->attachment_path || !Storage::disk('local')->exists($chatMessage->attachment_path)) {
            abort(404);
        }

        return response()->download(
            Storage::disk('local')->path($chatMessage->attachment_path),
            $chatMessage->attachment_name ?: 'archivo'
        );
    }

    private function ensureParticipant(Chat $chat, int $userId): void
    {
        if ((int) $chat->user_one_id !== $userId && (int) $chat->user_two_id !== $userId) {
            abort(403);
        }
    }

    private function resolveActiveChat(Collection $chatItems, mixed $requestedChatId): ?Chat
    {
        if ($chatItems->isEmpty()) {
            return null;
        }

        if ($requestedChatId !== null) {
            $requested = $chatItems
                ->first(fn (array $item) => (int) $item['chat']->id === (int) $requestedChatId);

            if ($requested) {
                return $requested['chat'];
            }
        }

        return $chatItems->first()['chat'];
    }

    private function photoUrl(?string $path): ?string
    {
        return User::buildProfilePhotoUrl($path);
    }

    private function markIncomingMessagesAsRead(Chat $chat, int $authUserId): void
    {
        $chat->messages()
            ->where('user_id', '!=', $authUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function serializeMessage(ChatMessage $message, int $authUserId): array
    {
        $attachmentName = $message->attachment_name;
        $attachmentUrl = $message->attachment_path
            ? route('dashboard.chat.messages.attachment', $message)
            : null;
        $attachmentMime = $message->attachment_mime;

        return [
            'id' => (int) $message->id,
            'user_id' => (int) $message->user_id,
            'user_name' => $message->user->name,
            'user_photo_url' => $this->photoUrl($message->user->profile_photo_path),
            'body' => $message->body,
            'created_at_time' => $message->created_at->format('g:i a'),
            'created_at_iso' => $message->created_at->copy()->utc()->toIso8601String(),
            'is_mine' => (int) $message->user_id === $authUserId,
            'is_read' => $message->read_at !== null,
            'attachment_name' => $attachmentName,
            'attachment_url' => $attachmentUrl,
            'attachment_mime' => $attachmentMime,
            'attachment_is_image' => $attachmentMime ? str_starts_with($attachmentMime, 'image/') : false,
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

    private function isReviewSuggestionDismissed(Chat $chat, int $userId): bool
    {
        if ((int) $chat->user_one_id === $userId) {
            return $chat->user_one_review_prompt_dismissed_at !== null;
        }

        if ((int) $chat->user_two_id === $userId) {
            return $chat->user_two_review_prompt_dismissed_at !== null;
        }

        return false;
    }
}
