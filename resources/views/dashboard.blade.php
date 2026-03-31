<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Matches') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div x-data="{ tab: 'solicitudes' }">
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
                    <template x-if="tab === 'solicitudes'">
                        <div>
                            <h1 class="text-xl font-bold">Solicitudes Pendientes</h1>
                            <p class="text-gray-600 mt-2">Aquí se mostrarán las solicitudes pendientes.</p>
                        </div>
                    </template>
                    <template x-if="tab === 'matches'">
                        <div>
                            <h1 class="text-xl font-bold">Matches</h1>
                            <p class="text-gray-600 mt-2">Aquí se mostrarán tus matches.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>


    @include('components.skills-modal')
    @include('components.availability-modal')

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
