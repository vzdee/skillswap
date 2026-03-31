<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div x-data="{ tab: 'solicitudes' }">
                @if (session('request_success'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ session('request_success') }}
                    </div>
                @endif

                @if (session('request_error'))
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        {{ session('request_error') }}
                    </div>
                @endif

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-evenly p-6 text-gray-900">
                        <button
                            :class="tab === 'solicitudes' ? 'bg-sky-500 text-white' : 'bg-gray-200 text-gray-900'"
                            class="p-3 rounded-full text-center font-semibold transition-colors duration-200 focus:outline-none"
                            @click="tab = 'solicitudes'"
                        >
                            {{ __('Solicitudes Pendientes') }}
                        </button>
                        <button
                            :class="tab === 'matches' ? 'bg-sky-500 text-white' : 'bg-gray-200 text-gray-900'"
                            class="p-3 rounded-full text-center font-semibold transition-colors duration-200 focus:outline-none"
                            @click="tab = 'matches'"
                        >
                            {{ __('Matches') }}
                        </button>
                    </div>
                </div>
                <div class="p-6 mt-6 bg-white rounded-lg shadow min-h-[120px]">
                    <div x-show="tab === 'solicitudes'" x-cloak>
                            <div class="mb-4 flex items-center justify-between">
                                <h1 class="text-xl font-bold text-gray-900">Solicitudes Pendientes</h1>
                                <span id="pending-requests-count" class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                                    {{ $pendingRequests->count() }} pendientes
                                </span>
                            </div>

                            @if ($pendingRequests->isEmpty())
                                <p class="text-gray-600">No tienes solicitudes pendientes por responder.</p>
                            @else
                                <div class="grid gap-4 lg:grid-cols-2">
                                    @foreach ($pendingRequests as $pending)
                                        <article class="rounded-[32px] bg-zinc-200/80 p-6" data-pending-card="{{ $pending['request']->id }}" data-from-user-id="{{ $pending['user']->id }}">
                                            <div class="flex items-start gap-4">
                                                @if ($pending['photoUrl'])
                                                    <img src="{{ $pending['photoUrl'] }}" alt="{{ $pending['user']->name }}" class="h-20 w-20 rounded-full object-cover">
                                                @else
                                                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-white text-2xl font-bold text-gray-600">
                                                        {{ strtoupper(substr($pending['user']->name, 0, 1)) }}
                                                    </div>
                                                @endif

                                                <div>
                                                    <h2 class="text-4xl font-black leading-none text-gray-900">
                                                        {{ $pending['user']->name }}{{ $pending['age'] ? ', ' . $pending['age'] . ' años' : '' }}
                                                    </h2>
                                                    <p class="mt-2 text-xl font-semibold text-gray-900">
                                                        {{ $pending['career'] ?: 'Carrera no especificada' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-5 space-y-4">
                                                <div>
                                                    <p class="text-3xl font-black text-gray-900">Habilidades</p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        @forelse ($pending['skillsTheyCanTeach'] as $skill)
                                                            <span class="rounded-lg bg-gray-300 px-3 py-1 text-sm font-medium text-gray-800">{{ $skill }}</span>
                                                        @empty
                                                            <span class="rounded-lg bg-gray-300 px-3 py-1 text-sm font-medium text-gray-800">Sin datos</span>
                                                        @endforelse
                                                    </div>
                                                </div>

                                                <div>
                                                    <p class="text-3xl font-black text-gray-900">Intereses</p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        @forelse ($pending['theirInterests'] as $interest)
                                                            <span class="rounded-lg bg-gray-300 px-3 py-1 text-sm font-medium text-gray-800">{{ $interest }}</span>
                                                        @empty
                                                            <span class="rounded-lg bg-gray-300 px-3 py-1 text-sm font-medium text-gray-800">Sin datos</span>
                                                        @endforelse
                                                    </div>
                                                </div>

                                                <div>
                                                    <p class="text-3xl font-black text-gray-900">Horarios en Común</p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        @forelse ($pending['sharedAvailability'] as $sharedSlot)
                                                            <span class="rounded-lg bg-gray-300 px-3 py-1 text-sm font-medium text-gray-800">{{ $sharedSlot }}</span>
                                                        @empty
                                                            <span class="rounded-lg bg-gray-300 px-3 py-1 text-sm font-medium text-gray-800">Sin horarios en comun</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-6 flex justify-end gap-4">
                                                <form method="POST" action="{{ route('matches.request.respond', $pending['request']) }}" class="js-request-respond-form" data-request-id="{{ $pending['request']->id }}" data-from-user-id="{{ $pending['user']->id }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="accept">
                                                    <button type="submit" class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-500 text-3xl font-black text-white transition hover:bg-blue-600">✓</button>
                                                </form>

                                                <form method="POST" action="{{ route('matches.request.respond', $pending['request']) }}" class="js-request-respond-form" data-request-id="{{ $pending['request']->id }}" data-from-user-id="{{ $pending['user']->id }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-400 text-3xl font-black text-white transition hover:bg-gray-500">✕</button>
                                                </form>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                    </div>
                    <div x-show="tab === 'matches'" x-cloak>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h1 class="text-xl font-bold text-gray-900">Matches</h1>
                                <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                                    {{ $matches->count() }} encontrados
                                </span>
                            </div>

                            @if ($matches->isEmpty())
                                <p class="mt-2 text-gray-600">Aun no tienes matches. Completa tus intereses, habilidades y disponibilidad para encontrar personas compatibles.</p>
                            @else
                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                    @foreach ($matches as $match)
                                        <article class="rounded-[32px] bg-zinc-200/80 p-6" data-match-card="{{ $match['user']->id }}">
                                            <div class="flex items-start gap-4">
                                                @if ($match['photoUrl'])
                                                    <img src="{{ $match['photoUrl'] }}" alt="{{ $match['user']->name }}" class="h-20 w-20 rounded-full object-cover">
                                                @else
                                                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-white text-2xl font-bold text-gray-600">
                                                        {{ strtoupper(substr($match['user']->name, 0, 1)) }}
                                                    </div>
                                                @endif

                                                <div>
                                                    <h2 class="text-4xl font-black leading-none text-gray-900">
                                                        {{ $match['user']->name }}{{ $match['age'] ? ', ' . $match['age'] . ' años' : '' }}
                                                    </h2>
                                                    <p class="mt-2 text-xl font-semibold text-gray-900">
                                                        {{ $match['career'] ?: 'Carrera no especificada' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-5 space-y-4">
                                                <div>
                                                    <p class="text-3xl font-black text-gray-900">Habilidades</p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        @foreach ($match['skillsYouCanTeach'] as $skill)
                                                            <span class="rounded-lg bg-gray-300 px-3 py-1 text-sm font-medium text-gray-800">{{ $skill }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div>
                                                    <p class="text-3xl font-black text-gray-900">Intereses</p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        @foreach ($match['skillsTheyCanTeach'] as $skill)
                                                            <span class="rounded-lg bg-gray-300 px-3 py-1 text-sm font-medium text-gray-800">{{ $skill }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div>
                                                    <p class="text-3xl font-black text-gray-900">Horarios en Común</p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        @foreach ($match['sharedAvailability'] as $sharedSlot)
                                                            <span class="rounded-lg bg-gray-300 px-3 py-1 text-sm font-medium text-gray-800">{{ $sharedSlot }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-6 flex justify-end" data-match-action-container="{{ $match['user']->id }}">
                                                @if ($match['requestState'] === 'pending_sent')
                                                    <button type="button" disabled class="rounded-full bg-gray-300 px-5 py-2 text-lg font-bold text-gray-700">Solicitud enviada</button>
                                                @elseif ($match['requestState'] === 'pending_received')
                                                    <button type="button" class="rounded-full bg-blue-500 px-5 py-2 text-lg font-bold text-white" @click="tab = 'solicitudes'">Revisar solicitud</button>
                                                @elseif ($match['requestState'] === 'accepted')
                                                    <button type="button" disabled class="rounded-full bg-emerald-200 px-5 py-2 text-lg font-bold text-emerald-800">Solicitud aceptada</button>
                                                @else
                                                    <form method="POST" action="{{ route('matches.request.store') }}" class="js-send-request-form" data-target-user-id="{{ $match['user']->id }}">
                                                        @csrf
                                                        <input type="hidden" name="target_user_id" value="{{ $match['user']->id }}">
                                                        <button type="submit" class="rounded-full bg-transparent px-5 py-2 text-2xl font-black text-gray-900 transition hover:text-blue-600">-> Enviar Solicitud</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                    </div>
                </div>
            </div>
        </div>
    </div>


    @include('components.skills-modal')
    @include('components.availability-modal')

    <script>
        (() => {
            const csrfToken = '{{ csrf_token() }}';
            const sendRequestUrl = '{{ route('matches.request.store') }}';
            const pendingCount = document.getElementById('pending-requests-count');

            const postForm = async (form) => {
                const actionUrl = form.getAttribute('action') || window.location.href;
                const method = (form.getAttribute('method') || 'POST').toUpperCase();

                const response = await fetch(actionUrl, {
                    method,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });

                const data = await response.json();

                if (!response.ok || data.ok === false) {
                    throw new Error(data.message || 'No se pudo completar la accion.');
                }

                return data;
            };

            const updatePendingCount = (nextValue) => {
                if (!pendingCount) {
                    return;
                }

                const safeValue = Math.max(0, nextValue);
                pendingCount.textContent = `${safeValue} pendientes`;
            };

            const renderMatchAction = ({ userId, state, chatId = null }) => {
                const container = document.querySelector(`[data-match-action-container="${userId}"]`);
                if (!container) {
                    return;
                }

                if (state === 'accepted') {
                    const chatHref = chatId
                        ? `{{ route('dashboard.chat') }}?chat=${chatId}`
                        : '{{ route('dashboard.chat') }}';

                    container.innerHTML = `<a href="${chatHref}" class="rounded-full bg-emerald-200 px-5 py-2 text-lg font-bold text-emerald-800">Ir al chat</a>`;
                    return;
                }

                if (state === 'pending_sent') {
                    container.innerHTML = '<button type="button" disabled class="rounded-full bg-gray-300 px-5 py-2 text-lg font-bold text-gray-700">Solicitud enviada</button>';
                    return;
                }

                container.innerHTML = `
                    <form method="POST" action="${sendRequestUrl}" class="js-send-request-form" data-target-user-id="${userId}">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="target_user_id" value="${userId}">
                        <button type="submit" class="rounded-full bg-transparent px-5 py-2 text-2xl font-black text-gray-900 transition hover:text-blue-600">-> Enviar Solicitud</button>
                    </form>
                `;
            };

            document.addEventListener('submit', async (event) => {
                const respondForm = event.target.closest('.js-request-respond-form');
                if (respondForm) {
                    event.preventDefault();

                    try {
                        const data = await postForm(respondForm);
                        const card = document.querySelector(`[data-pending-card="${data.request_id}"]`);
                        if (card) {
                            card.remove();
                        }

                        const currentCount = Number.parseInt((pendingCount?.textContent || '0').replace(/\D/g, ''), 10) || 0;
                        updatePendingCount(currentCount - 1);

                        const sourceUserId = data.from_user_id;
                        if (sourceUserId) {
                            renderMatchAction({
                                userId: sourceUserId,
                                state: data.status === 'accepted' ? 'accepted' : 'none',
                                chatId: data.chat_id,
                            });
                        }

                        if (data.status === 'accepted' && data.redirect_url) {
                            window.location.assign(data.redirect_url);
                            return;
                        }
                    } catch (error) {
                        window.alert(error.message);
                    }

                    return;
                }

                const sendForm = event.target.closest('.js-send-request-form');
                if (sendForm) {
                    event.preventDefault();

                    try {
                        const data = await postForm(sendForm);
                        const targetUserId = data.target_user_id || sendForm.dataset.targetUserId;
                        renderMatchAction({ userId: targetUserId, state: 'pending_sent' });
                    } catch (error) {
                        window.alert(error.message);
                    }
                }
            });
        })();
    </script>

    <script>
        (() => {
            const token = '{{ csrf_token() }}';
            const requiresSkills = @json($requiresSkillsOnboarding);
            const requiresAvailability = @json($requiresAvailabilityOnboarding);

            if (!requiresSkills && !requiresAvailability) {
                return;
            }

            const skillCatalog = @json($skillsCatalog->map(fn ($skill) => ['id' => $skill->id, 'name' => $skill->name])->values());
            const availabilityDays = @json($availabilityDays);
            const availabilityBlocks = @json($availabilityBlocks);

            const skillsModal = document.getElementById('skills-onboarding-modal');
            const availabilityModal = document.getElementById('availability-onboarding-modal');

            const openModal = (modal) => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = (modal) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const postJson = async (url, payload) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();
                if (!response.ok) {
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(firstError || data.message || 'No se pudo guardar.');
                }

                return data;
            };

            const createSkillSelector = ({ type, inputId, optionsId, listId, selectedSet, blockedSetResolver, emptyText }) => {
                const input = document.getElementById(inputId);
                const options = document.getElementById(optionsId);
                const list = document.getElementById(listId);

                if (!input || !options || !list) {
                    return;
                }

                const renderPills = () => {
                    list.innerHTML = '';

                    if (selectedSet.size === 0) {
                        const empty = document.createElement('p');
                        empty.className = 'text-xs font-medium text-gray-400';
                        empty.textContent = emptyText;
                        list.appendChild(empty);
                        return;
                    }

                    skillCatalog
                        .filter((skill) => selectedSet.has(skill.id))
                        .forEach((skill) => {
                            const pill = document.createElement('span');
                            pill.className = 'inline-flex items-center gap-2 rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold uppercase text-gray-700';
                            pill.innerHTML = `${skill.name}<button type="button" class="h-4 w-4 rounded-full bg-red-500 text-[10px] font-bold leading-none text-white">x</button>`;
                            pill.querySelector('button').addEventListener('click', () => {
                                selectedSet.delete(skill.id);
                                renderPills();
                            });
                            list.appendChild(pill);
                        });
                };

                const hideOptions = () => {
                    options.classList.add('hidden');
                    options.innerHTML = '';
                };

                const renderOptions = () => {
                    const term = input.value.trim().toLowerCase();
                    const blockedSet = blockedSetResolver();
                    const matches = skillCatalog
                        .filter((skill) => !selectedSet.has(skill.id) && !blockedSet.has(skill.id) && skill.name.toLowerCase().includes(term))
                        .slice(0, 8);

                    if (matches.length === 0) {
                        hideOptions();
                        return;
                    }

                    options.innerHTML = '';
                    matches.forEach((skill) => {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'block w-full rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-blue-50';
                        option.textContent = skill.name;
                        option.addEventListener('click', () => {
                            selectedSet.add(skill.id);
                            input.value = '';
                            hideOptions();
                            renderPills();
                            if (type === 'teach' && learnSelector?.selectedSet.has(skill.id)) {
                                learnSelector.selectedSet.delete(skill.id);
                                learnSelector.renderPills();
                            }
                            if (type === 'learn' && teachSelector?.selectedSet.has(skill.id)) {
                                teachSelector.selectedSet.delete(skill.id);
                                teachSelector.renderPills();
                            }
                        });
                        options.appendChild(option);
                    });
                    options.classList.remove('hidden');
                };

                input.addEventListener('input', renderOptions);
                input.addEventListener('focus', renderOptions);
                input.addEventListener('click', renderOptions);
                input.addEventListener('blur', () => {
                    window.setTimeout(hideOptions, 120);
                });

                renderPills();

                return {
                    selectedSet,
                    renderPills,
                    renderOptions,
                };
            };

            const setupAvailabilityGrid = () => {
                const container = document.getElementById('availability-grid');
                if (!container) {
                    return null;
                }

                const selected = new Set();

                Object.entries(availabilityDays).forEach(([dayKey, dayLabel]) => {
                    const dayColumn = document.createElement('div');
                    dayColumn.className = 'space-y-2';

                    const dayTitle = document.createElement('p');
                    dayTitle.className = 'text-xl font-bold text-gray-900';
                    dayTitle.textContent = dayLabel;
                    dayColumn.appendChild(dayTitle);

                    availabilityBlocks.forEach((block) => {
                        const key = `${dayKey}|${block}`;
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'block w-full rounded-md bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-600 transition hover:bg-gray-300';
                        button.textContent = block;
                        button.dataset.blockKey = key;

                        button.addEventListener('click', () => {
                            if (selected.has(key)) {
                                selected.delete(key);
                                button.classList.remove('bg-sky-500', 'text-white');
                                button.classList.add('bg-gray-200', 'text-gray-600');
                            } else {
                                selected.add(key);
                                button.classList.add('bg-sky-500', 'text-white');
                                button.classList.remove('bg-gray-200', 'text-gray-600');
                            }
                        });

                        dayColumn.appendChild(button);
                    });

                    container.appendChild(dayColumn);
                });
                return selected;
            };

            const selectedSkillIds = {
                teach: new Set(),
                learn: new Set(),
            };

            let teachSelector;
            let learnSelector;

            teachSelector = createSkillSelector({
                type: 'teach',
                inputId: 'teach-onboarding-input',
                optionsId: 'teach-onboarding-options',
                listId: 'teach-onboarding-list',
                selectedSet: selectedSkillIds.teach,
                blockedSetResolver: () => selectedSkillIds.learn,
                emptyText: 'No has agregado habilidades para enseñar.',
            });

            learnSelector = createSkillSelector({
                type: 'learn',
                inputId: 'learn-onboarding-input',
                optionsId: 'learn-onboarding-options',
                listId: 'learn-onboarding-list',
                selectedSet: selectedSkillIds.learn,
                blockedSetResolver: () => selectedSkillIds.teach,
                emptyText: 'No has agregado habilidades para aprender.',
            });

            const selectedAvailability = setupAvailabilityGrid();

            const skillsError = document.getElementById('skills-onboarding-error');
            const availabilityError = document.getElementById('availability-onboarding-error');

            const skillsSave = document.getElementById('skills-onboarding-save');
            const availabilitySave = document.getElementById('availability-onboarding-save');

            if (skillsSave) {
                skillsSave.addEventListener('click', async () => {
                    skillsError.classList.add('hidden');

                    const teachCount = teachSelector?.selectedSet.size ?? 0;
                    const learnCount = learnSelector?.selectedSet.size ?? 0;

                    if (teachCount !== 3 || learnCount !== 2) {
                        skillsError.textContent = 'Debes seleccionar exactamente 3 habilidades para enseñar y 2 intereses para aprender.';
                        skillsError.classList.remove('hidden');
                        return;
                    }

                    try {
                        await postJson('{{ route('onboarding.skills.store') }}', {
                            teach_skill_ids: Array.from(teachSelector.selectedSet),
                            learn_skill_ids: Array.from(learnSelector.selectedSet),
                        });

                        closeModal(skillsModal);
                        if (requiresAvailability) {
                            openModal(availabilityModal);
                        }
                    } catch (error) {
                        skillsError.textContent = error.message;
                        skillsError.classList.remove('hidden');
                    }
                });
            }

            if (availabilitySave) {
                availabilitySave.addEventListener('click', async () => {
                    availabilityError.classList.add('hidden');

                    try {
                        await postJson('{{ route('onboarding.availability.store') }}', {
                            blocks: Array.from(selectedAvailability),
                        });

                        closeModal(availabilityModal);
                    } catch (error) {
                        availabilityError.textContent = error.message;
                        availabilityError.classList.remove('hidden');
                    }
                });
            }

            if (requiresSkills) {
                openModal(skillsModal);
            } else if (requiresAvailability) {
                openModal(availabilityModal);
            }
        })();
    </script>
</x-app-layout>
