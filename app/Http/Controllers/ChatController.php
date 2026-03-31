<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        if ($activeChat) {
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
                        'photoUrl' => $this->photoUrl($message->user->profile_photo_path),
                    ];
                });
        }

        return view('chat.index', [
            'chatItems' => $chatItems,
            'activeChat' => $activeChat,
            'activeMessages' => $activeMessages,
            'activePartner' => $activePartner,
            'activePartnerPhotoUrl' => $this->photoUrl($activePartner?->profile_photo_path),
        ]);
    }

    public function storeMessage(Request $request, Chat $chat): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->ensureParticipant($chat, (int) $user->id);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $chat->messages()->create([
            'user_id' => $user->id,
            'body' => trim($validated['body']),
        ]);

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
        $chatMessage->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Mensaje eliminado.',
                'chat_id' => (int) $chatId,
                'message_id' => $messageId,
            ]);
        }

        return redirect()->route('dashboard.chat', ['chat' => $chatId]);
    }

    public function messages(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();
        $this->ensureParticipant($chat, (int) $user->id);

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

        return response()->json([
            'ok' => true,
            'chat_id' => (int) $chat->id,
            'items' => $messages,
        ]);
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
        if (!$path) {
            return null;
        }

        return asset('storage/' . str_replace('\\', '/', $path));
    }

    private function serializeMessage(ChatMessage $message, int $authUserId): array
    {
        return [
            'id' => (int) $message->id,
            'user_id' => (int) $message->user_id,
            'user_name' => $message->user->name,
            'user_photo_url' => $this->photoUrl($message->user->profile_photo_path),
            'body' => $message->body,
            'created_at_time' => $message->created_at->format('g:i a'),
            'is_mine' => (int) $message->user_id === $authUserId,
        ];
    }
}
