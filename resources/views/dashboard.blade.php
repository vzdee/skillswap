<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>

    <div
        id="skills-onboarding-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/55 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="skills-onboarding-title"
    >
        <div class="w-full max-w-3xl rounded-3xl bg-white p-8 shadow-2xl sm:p-10">
            <h3 id="skills-onboarding-title" class="text-center text-2xl font-bold text-gray-900">REGISTRO DE HABILIDADES</h3>
            <p class="mt-2 text-center text-sm text-gray-500">Esta informacion se puede modificar en cualquier momento en configuracion.</p>

            <div class="mx-auto mt-8 max-w-2xl space-y-8" id="skills-onboarding-root">
                <div>
                    <h4 class="text-2xl font-semibold text-gray-900">Habilidades que puedo enseñar:</h4>
                    <p class="mt-1 text-sm text-gray-600">Busca y selecciona 3 habilidades que puedes enseñar a otros.</p>
                    <div class="relative mt-3">
                        <input id="teach-onboarding-input" type="text" class="block w-full rounded-full border-gray-300 bg-gray-200 px-5 py-3 text-base" placeholder="E.J: Python, UI Design, Etc.">
                        <div id="teach-onboarding-options" class="absolute left-0 top-14 z-20 hidden max-h-56 w-full overflow-y-auto rounded-2xl border border-gray-200 bg-white p-1 shadow-lg"></div>
                    </div>
                    <div id="teach-onboarding-list" class="mt-3 flex flex-wrap gap-2"></div>
                </div>

                <div>
                    <h4 class="text-2xl font-semibold text-gray-900">Habilidades que quiero aprender:</h4>
                    <p class="mt-1 text-sm text-gray-600">Busca y selecciona 2 intereses que quieres aprender de otros.</p>
                    <div class="relative mt-3">
                        <input id="learn-onboarding-input" type="text" class="block w-full rounded-full border-gray-300 bg-gray-200 px-5 py-3 text-base" placeholder="E.J: Marketing, Desarrollo Web, Idioma Ingles, Etc.">
                        <div id="learn-onboarding-options" class="absolute left-0 top-14 z-20 hidden max-h-56 w-full overflow-y-auto rounded-2xl border border-gray-200 bg-white p-1 shadow-lg"></div>
                    </div>
                    <div id="learn-onboarding-list" class="mt-3 flex flex-wrap gap-2"></div>
                </div>

                <p id="skills-onboarding-error" class="hidden text-center text-sm font-semibold text-red-600"></p>

                <div class="pt-2 text-center">
                    <button id="skills-onboarding-save" type="button" class="rounded-full bg-sky-500 px-10 py-3 text-lg font-bold text-white transition hover:bg-sky-600">GUARDAR</button>
                </div>
            </div>
        </div>
    </div>

    <div
        id="availability-onboarding-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/55 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="availability-onboarding-title"
    >
        <div class="w-full max-w-5xl rounded-3xl bg-white p-8 shadow-2xl sm:p-10">
            <h3 id="availability-onboarding-title" class="text-center text-2xl font-bold text-gray-900">REGISTRO DE LA DISPONIBILIDAD DE HORARIO</h3>
            <p class="mt-2 text-center text-gray-700">Indica los dias y horas disponibles para enseñar o aprender.</p>
            <p class="mt-1 text-center text-sm text-gray-500">Esta informacion se puede modificar en cualquier momento en configuracion.</p>

            <div id="availability-grid" class="mx-auto mt-7 grid max-w-3xl grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"></div>

            <p id="availability-onboarding-error" class="mt-4 hidden text-center text-sm font-semibold text-red-600"></p>

            <div class="mt-8 text-center">
                <button id="availability-onboarding-save" type="button" class="rounded-full bg-sky-500 px-10 py-3 text-lg font-bold text-white transition hover:bg-sky-600">GUARDAR</button>
            </div>
        </div>
    </div>

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
