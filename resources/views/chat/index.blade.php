<x-app-layout>
    <div class="py-4">
        <div class="mx-auto max-w-7xl px-2 sm:px-4 lg:px-6">
            <div class="h-[calc(100vh-11rem)] max-h-[calc(100vh-11rem)] overflow-hidden rounded-2xl border border-gray-200 bg-[#b9bec8] shadow">
                <div class="grid h-full min-h-0 grid-cols-1 md:grid-cols-[320px_1fr] md:grid-rows-1">
                    <aside class="h-full min-h-0 overflow-y-auto border-r border-gray-200 bg-white/95">
                        <div class="border-b border-gray-200 px-4 py-3">
                            <h3 class="text-4xl font-black text-gray-900">Chats</h3>
                        </div>

                        @forelse ($chatItems as $item)
                            <a
                                href="{{ route('dashboard.chat', ['chat' => $item['chat']->id]) }}"
                                class="block border-b border-gray-100 px-3 py-3 transition hover:bg-gray-100 {{ $activeChat && $activeChat->id === $item['chat']->id ? 'bg-gray-200' : '' }}"
                            >
                                <div class="flex items-start gap-3">
                                    @if ($item['partnerPhotoUrl'])
                                        <img src="{{ $item['partnerPhotoUrl'] }}" alt="{{ $item['partner']->name }}" class="mt-1 h-11 w-11 rounded-full object-cover">
                                    @else
                                        <div class="mt-1 flex h-11 w-11 items-center justify-center rounded-full bg-blue-100 text-base font-bold text-blue-700">
                                            {{ strtoupper(substr($item['partner']->name, 0, 1)) }}
                                        </div>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="truncate text-sm font-black text-gray-900">{{ $item['partner']->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $item['lastAt'] ? $item['lastAt']->format('g:i a') : '' }}</p>
                                        </div>
                                        <p class="mt-1 truncate text-xs text-gray-700">{{ $item['preview'] }}</p>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="px-4 py-6 text-sm text-gray-500">Aún no tienes chats activos.</p>
                        @endforelse
                    </aside>

                    <section class="relative grid h-full min-h-0 grid-rows-[auto_minmax(0,1fr)_auto] overflow-hidden">
                        @if (!$activeChat)
                            <div class="flex h-full items-center justify-center px-6 text-center text-gray-700">
                                Acepta una solicitud para crear un chat y comenzar a conversar.
                            </div>
                        @else
                            <div class="shrink-0 border-b border-gray-300/80 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($activePartnerPhotoUrl)
                                        <img src="{{ $activePartnerPhotoUrl }}" alt="{{ $activePartner->name }}" class="h-11 w-11 rounded-full object-cover">
                                    @else
                                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-100 text-base font-bold text-blue-700">
                                            {{ strtoupper(substr($activePartner->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <p class="text-sm font-black text-gray-900">{{ $activePartner->name }}</p>
                                </div>
                            </div>

                            <div id="chat-messages-list" class="min-h-0 space-y-4 overflow-y-auto px-4 py-4 sm:px-5 sm:py-5" data-active-chat-id="{{ $activeChat->id }}">
                                @forelse ($activeMessages as $item)
                                    <div class="{{ $item['isMine'] ? 'flex justify-end' : 'flex justify-start' }}" data-chat-item="{{ $item['message']->id }}">
                                        <div
                                            data-chat-message
                                            data-message-id="{{ $item['message']->id }}"
                                            data-message-text="{{ $item['message']->body }}"
                                            data-mine="{{ $item['isMine'] ? '1' : '0' }}"
                                            data-delete-url="{{ route('dashboard.chat.messages.destroy', $item['message']->id) }}"
                                            class="group relative max-w-[80%]"
                                        >
                                            <div class="flex items-end gap-2 {{ $item['isMine'] ? 'flex-row-reverse' : '' }}">
                                                @if (!$item['isMine'])
                                                    @if ($item['photoUrl'])
                                                        <img src="{{ $item['photoUrl'] }}" alt="{{ $item['message']->user->name }}" class="h-9 w-9 rounded-full object-cover">
                                                    @else
                                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                                                            {{ strtoupper(substr($item['message']->user->name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                @endif

                                                <div class="rounded-3xl px-4 py-3 {{ $item['isMine'] ? 'bg-blue-500 text-white' : 'bg-white text-gray-900' }}">
                                                    <div class="mb-1 flex items-center gap-3">
                                                        <p class="text-xs font-black {{ $item['isMine'] ? 'text-blue-100' : 'text-gray-900' }}">{{ $item['message']->user->name }}</p>
                                                        <p class="text-[11px] {{ $item['isMine'] ? 'text-blue-100' : 'text-gray-500' }}">{{ $item['message']->created_at->format('g:i a') }}</p>
                                                    </div>
                                                    <p class="text-sm leading-snug">{{ $item['message']->body }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-center text-sm text-gray-700">Aún no hay mensajes en este chat.</p>
                                @endforelse
                            </div>

                            <div class="shrink-0 border-t border-gray-300/80 bg-[#b9bec8]/95 p-3 sm:p-4">
                                <form id="chat-send-form" method="POST" action="{{ route('dashboard.chat.messages.store', $activeChat) }}" class="flex items-center gap-3 rounded-md bg-[#212735] px-4 py-3">
                                    @csrf
                                    <span class="text-gray-300" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                            <path d="M16.5 6.75a4.5 4.5 0 0 0-6.364 0L4.72 12.167a3 3 0 0 0 4.243 4.243l4.95-4.95a1.5 1.5 0 0 0-2.122-2.121l-4.066 4.066a.75.75 0 0 0 1.06 1.061l3.36-3.36a.75.75 0 1 1 1.06 1.06l-3.358 3.36a2.25 2.25 0 1 1-3.182-3.182l4.95-4.95a3 3 0 1 1 4.243 4.243l-5.657 5.657a5.25 5.25 0 0 1-7.425-7.425l5.657-5.657a6 6 0 1 1 8.486 8.485l-4.243 4.243a.75.75 0 1 1-1.06-1.06l4.242-4.244a4.5 4.5 0 0 0 0-6.364Z" />
                                        </svg>
                                    </span>
                                    <input
                                        id="chat-message-input"
                                        type="text"
                                        name="body"
                                        maxlength="2000"
                                        required
                                        placeholder="Escribe tu mensaje"
                                        class="w-full border-none bg-transparent text-sm text-white placeholder:text-gray-400 focus:ring-0"
                                    >
                                    <button type="submit" class="rounded-full bg-blue-500 p-2 text-white transition hover:bg-blue-600" aria-label="Enviar mensaje">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                            <path d="M3.4 20.8a.75.75 0 0 1-.95-.95l2.37-7.11a.75.75 0 0 0 0-.48L2.45 5.15a.75.75 0 0 1 .95-.95l17.3 5.77a.75.75 0 0 1 0 1.42L3.4 20.8Zm3.02-8.8-.92 2.75 9.76-3.25-9.76-3.25.92 2.75h6.83a.75.75 0 0 1 0 1.5H6.42Z" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div id="chat-message-menu" class="fixed z-50 hidden w-32 overflow-hidden rounded-lg border border-gray-300 bg-white shadow-lg">
        <button id="chat-menu-delete" type="button" class="hidden w-full border-b border-gray-200 px-3 py-2 text-sm font-semibold text-red-500 hover:bg-red-50">Eliminar</button>
        <button id="chat-menu-copy" type="button" class="w-full px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Copiar</button>
    </div>

    <script>
        (() => {
            const csrfToken = '{{ csrf_token() }}';
            const authUserId = {{ (int) auth()->id() }};
            const menu = document.getElementById('chat-message-menu');
            const copyButton = document.getElementById('chat-menu-copy');
            const deleteButton = document.getElementById('chat-menu-delete');
            const messagesList = document.getElementById('chat-messages-list');
            const sendForm = document.getElementById('chat-send-form');
            const messageInput = document.getElementById('chat-message-input');
            const activeChatId = Number.parseInt(messagesList?.dataset.activeChatId || '0', 10);
            const hasEcho = Boolean(window.Echo && activeChatId > 0);

            if (!menu || !copyButton || !deleteButton || !messagesList) {
                return;
            }

            let currentText = '';
            let currentDeleteUrl = '';
            let currentMessageId = 0;
            let lastMessageId = Array.from(messagesList.querySelectorAll('[data-message-id]'))
                .reduce((maxValue, node) => Math.max(maxValue, Number.parseInt(node.dataset.messageId || '0', 10) || 0), 0);

            const formatPhoto = (url, name) => {
                if (url) {
                    return `<img src="${url}" alt="${name}" class="h-9 w-9 rounded-full object-cover">`;
                }

                const initial = (name || '?').charAt(0).toUpperCase();
                return `<div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">${initial}</div>`;
            };

            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const decodeHtml = (value) => {
                const element = document.createElement('textarea');
                element.innerHTML = value;
                return element.value;
            };

            const renderMessageNode = (item) => {
                const wrapper = document.createElement('div');
                wrapper.className = item.is_mine ? 'flex justify-end' : 'flex justify-start';
                wrapper.dataset.chatItem = String(item.id);

                const messageHtml = `
                    <div
                        data-chat-message
                        data-message-id="${item.id}"
                        data-message-text="${escapeHtml(item.body)}"
                        data-mine="${item.is_mine ? '1' : '0'}"
                        data-delete-url="${item.is_mine ? `{{ route('dashboard.chat.messages.destroy', '__id__') }}`.replace('__id__', item.id) : ''}"
                        class="group relative max-w-[80%]"
                    >
                        <div class="flex items-end gap-2 ${item.is_mine ? 'flex-row-reverse' : ''}">
                            ${item.is_mine ? '' : formatPhoto(item.user_photo_url, item.user_name)}
                            <div class="rounded-3xl px-4 py-3 ${item.is_mine ? 'bg-blue-500 text-white' : 'bg-white text-gray-900'}">
                                <div class="mb-1 flex items-center gap-3">
                                    <p class="text-xs font-black ${item.is_mine ? 'text-blue-100' : 'text-gray-900'}">${escapeHtml(item.user_name)}</p>
                                    <p class="text-[11px] ${item.is_mine ? 'text-blue-100' : 'text-gray-500'}">${item.created_at_time}</p>
                                </div>
                                <p class="text-sm leading-snug">${escapeHtml(item.body)}</p>
                            </div>
                        </div>
                    </div>
                `;

                wrapper.innerHTML = messageHtml.trim();
                return wrapper;
            };

            const appendMessages = (items) => {
                if (!Array.isArray(items) || items.length === 0) {
                    return;
                }

                const emptyState = messagesList.querySelector('p.text-center');
                if (emptyState) {
                    emptyState.remove();
                }

                items.forEach((item) => {
                    if (messagesList.querySelector(`[data-chat-item="${item.id}"]`)) {
                        return;
                    }

                    const node = renderMessageNode(item);
                    messagesList.appendChild(node);
                    lastMessageId = Math.max(lastMessageId, Number(item.id));
                });

                messagesList.scrollTop = messagesList.scrollHeight;
            };

            const scrollToLatest = () => {
                messagesList.scrollTop = messagesList.scrollHeight;
            };

            const hideMenu = () => {
                menu.classList.add('hidden');
            };

            const socketHeaders = () => {
                const socketId = window.Echo?.socketId?.();

                if (!socketId) {
                    return {};
                }

                return {
                    'X-Socket-Id': socketId,
                };
            };

            document.addEventListener('contextmenu', (event) => {
                const messageNode = event.target.closest('[data-chat-message]');
                if (!messageNode) {
                    return;
                }

                event.preventDefault();

                currentText = decodeHtml(messageNode.dataset.messageText || '');
                currentDeleteUrl = messageNode.dataset.deleteUrl || '';
                currentMessageId = Number.parseInt(messageNode.dataset.messageId || '0', 10) || 0;
                const isMine = messageNode.dataset.mine === '1';

                if (isMine && currentDeleteUrl) {
                    deleteButton.classList.remove('hidden');
                } else {
                    deleteButton.classList.add('hidden');
                }

                menu.style.left = `${event.pageX}px`;
                menu.style.top = `${event.pageY}px`;
                menu.classList.remove('hidden');
            });

            copyButton.addEventListener('click', async () => {
                if (currentText) {
                    try {
                        await navigator.clipboard.writeText(currentText);
                    } catch (error) {
                        // Ignore clipboard failures silently.
                    }
                }

                hideMenu();
            });

            deleteButton.addEventListener('click', () => {
                if (!currentDeleteUrl || !currentMessageId) {
                    return;
                }

                fetch(currentDeleteUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        _token: csrfToken,
                        _method: 'DELETE',
                    }),
                }).then(async (response) => {
                    const data = await response.json();

                    if (!response.ok || data.ok === false) {
                        throw new Error(data.message || 'No se pudo eliminar el mensaje.');
                    }

                    const node = messagesList.querySelector(`[data-chat-item="${currentMessageId}"]`);
                    if (node) {
                        node.remove();
                    }
                }).catch((error) => {
                    window.alert(error.message);
                });

                hideMenu();
            });

            if (sendForm && messageInput) {
                sendForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const text = messageInput.value.trim();
                    if (!text) {
                        return;
                    }

                    try {
                        const response = await fetch(sendForm.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                ...socketHeaders(),
                            },
                            body: new FormData(sendForm),
                        });

                        const data = await response.json();
                        if (!response.ok || data.ok === false) {
                            throw new Error(data.message || 'No se pudo enviar el mensaje.');
                        }

                        appendMessages([data.item]);
                        messageInput.value = '';
                        messageInput.focus();
                    } catch (error) {
                        window.alert(error.message);
                    }
                });
            }

            scrollToLatest();

            window.addEventListener('resize', () => {
                scrollToLatest();
            });

            if (hasEcho) {
                window.Echo.private(`chat.${activeChatId}`)
                    .listen('.chat.message.sent', (event) => {
                        if (!event || Number(event.chat_id) !== activeChatId) {
                            return;
                        }

                        if (!event.item || Number(event.item.user_id) === authUserId) {
                            return;
                        }

                        appendMessages([event.item]);
                    });
            }

            if (activeChatId > 0) {
                const pollUrl = `{{ route('dashboard.chat.messages.index', '__chat__') }}`.replace('__chat__', String(activeChatId));

                window.setInterval(async () => {
                    try {
                        const query = new URLSearchParams({ after_id: String(lastMessageId) });
                        const response = await fetch(`${pollUrl}?${query.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const data = await response.json();
                        if (!data.ok) {
                            return;
                        }

                        appendMessages(data.items || []);
                    } catch (error) {
                        // Ignore polling errors.
                    }
                }, 3000);
            }

            document.addEventListener('click', (event) => {
                if (!menu.contains(event.target)) {
                    hideMenu();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    hideMenu();
                }
            });
        })();
    </script>
</x-app-layout>
