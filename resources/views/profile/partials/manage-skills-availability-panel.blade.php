<section class="rounded-2xl border border-gray-200 bg-gray-50/60 p-5">
    <div id="profile-catalog-panel" class="space-y-6">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Intereses</h3>
            <div id="learn-skills-list" class="mt-2 flex flex-wrap gap-2">
                @foreach ($learningSkills as $skill)
                    <span class="inline-flex items-center gap-2 rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold uppercase text-gray-700" data-skill-pill="learn" data-skill-id="{{ $skill->id }}">
                        {{ $skill->name }}
                        <button type="button" class="h-4 w-4 rounded-full bg-red-500 text-[10px] font-bold leading-none text-white" data-remove-skill="learn" data-skill-id="{{ $skill->id }}">x</button>
                    </span>
                @endforeach
            </div>

            <div class="relative mt-3 flex gap-2">
                <input id="learn-skill-input" type="text" class="block w-full rounded-2xl border-gray-300 text-sm" placeholder="Buscar habilidad para aprender...">
                <button id="learn-skill-add" type="button" class="rounded-full bg-gray-200 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-300">AGREGAR</button>
                <div id="learn-skill-options" class="absolute left-0 top-11 z-20 hidden max-h-52 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-1 shadow-lg"></div>
            </div>
        </div>

        <div>
            <h3 class="text-base font-semibold text-gray-900">Habilidades</h3>
            <div id="teach-skills-list" class="mt-2 flex flex-wrap gap-2">
                @foreach ($taughtSkills as $skill)
                    <span class="inline-flex items-center gap-2 rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold uppercase text-gray-700" data-skill-pill="teach" data-skill-id="{{ $skill->id }}">
                        {{ $skill->name }}
                        <button type="button" class="h-4 w-4 rounded-full bg-red-500 text-[10px] font-bold leading-none text-white" data-remove-skill="teach" data-skill-id="{{ $skill->id }}">x</button>
                    </span>
                @endforeach
            </div>

            <div class="relative mt-3 flex gap-2">
                <input id="teach-skill-input" type="text" class="block w-full rounded-2xl border-gray-300 text-sm" placeholder="Buscar habilidad para ensenar...">
                <button id="teach-skill-add" type="button" class="rounded-full bg-gray-200 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-300">AGREGAR</button>
                <div id="teach-skill-options" class="absolute left-0 top-11 z-20 hidden max-h-52 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-1 shadow-lg"></div>
            </div>
        </div>

        <div>
            <h3 class="text-base font-semibold text-gray-900">Disponibilidad</h3>
            <div id="availability-list" class="mt-2 flex flex-wrap gap-2">
                @foreach ($availabilities as $availability)
                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold uppercase text-gray-700"
                        data-availability-pill="{{ $availability->weekday }}|{{ $availability->time_block }}"
                    >
                        {{ data_get($availabilityDays, $availability->weekday, ucfirst($availability->weekday)) }} {{ $availability->time_block }}
                        <button
                            type="button"
                            class="h-4 w-4 rounded-full bg-red-500 text-[10px] font-bold leading-none text-white"
                            data-remove-availability="{{ $availability->weekday }}|{{ $availability->time_block }}"
                        >x</button>
                    </span>
                @endforeach
            </div>

            <div class="mt-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]"> 
                <select id="availability-day" class="rounded-2xl border-gray-300 text-sm">
                    <option value="">Dia</option>
                    @foreach ($availabilityDays as $dayKey => $dayLabel)
                        <option value="{{ $dayKey }}">{{ $dayLabel }}</option>
                    @endforeach
                </select>

                <select id="availability-block" class="rounded-2xl border-gray-300 text-sm">
                    <option value="">Horario</option>
                    @foreach ($availabilityBlocks as $timeBlock)
                        <option value="{{ $timeBlock }}">{{ $timeBlock }}</option>
                    @endforeach
                </select>

                <button id="availability-add" type="button" class="rounded-full bg-gray-200 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-300">AGREGAR</button>
            </div>
        </div>

        <p id="profile-catalog-feedback" class="hidden text-sm font-medium"></p>
    </div>

    <script>
        (() => {
            const root = document.getElementById('profile-catalog-panel');
            if (!root) {
                return;
            }

            const token = '{{ csrf_token() }}';
            const skillCatalog = @json($skillsCatalog->map(fn ($skill) => ['id' => $skill->id, 'name' => $skill->name])->values());
            const dayLabels = @json($availabilityDays);
            const selectedSkillIds = {
                learn: new Set(@json($learningSkills->pluck('id')->values())),
                teach: new Set(@json($taughtSkills->pluck('id')->values())),
            };
            const selectedAvailability = new Set(@json($availabilities->map(fn ($item) => $item->weekday . '|' . $item->time_block)->values()));
            const getOppositeType = (type) => (type === 'teach' ? 'learn' : 'teach');

            const feedback = document.getElementById('profile-catalog-feedback');
            const setFeedback = (text, isError = false) => {
                feedback.textContent = text;
                feedback.classList.remove('hidden', 'text-green-600', 'text-red-600');
                feedback.classList.add(isError ? 'text-red-600' : 'text-green-600');

                window.clearTimeout(setFeedback.timer);
                setFeedback.timer = window.setTimeout(() => {
                    feedback.classList.add('hidden');
                }, 2500);
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
                    throw new Error(data.message || 'No se pudo completar la accion.');
                }

                return data;
            };

            const setupSkillPicker = ({ type, inputId, addButtonId, optionsId, listId }) => {
                const input = document.getElementById(inputId);
                const addButton = document.getElementById(addButtonId);
                const options = document.getElementById(optionsId);
                const list = document.getElementById(listId);
                let selectedSkill = null;

                if (!input || !addButton || !options || !list) {
                    return;
                }

                const hideOptions = () => {
                    options.classList.add('hidden');
                    options.innerHTML = '';
                };

                const renderOptions = () => {
                    const term = input.value.trim().toLowerCase();
                    const oppositeType = getOppositeType(type);

                    const matches = skillCatalog
                        .filter((skill) => !selectedSkillIds[type].has(skill.id) && !selectedSkillIds[oppositeType].has(skill.id) && skill.name.toLowerCase().includes(term))
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
                            selectedSkill = skill;
                            input.value = skill.name;
                            hideOptions();
                        });
                        options.appendChild(option);
                    });
                    options.classList.remove('hidden');
                };

                const addSkillPill = (skill) => {
                    const pill = document.createElement('span');
                    pill.className = 'inline-flex items-center gap-2 rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold uppercase text-gray-700';
                    pill.dataset.skillPill = type;
                    pill.dataset.skillId = String(skill.id);
                    pill.innerHTML = `${skill.name}<button type="button" class="h-4 w-4 rounded-full bg-red-500 text-[10px] font-bold leading-none text-white" data-remove-skill="${type}" data-skill-id="${skill.id}">x</button>`;
                    list.appendChild(pill);
                };

                input.addEventListener('input', () => {
                    selectedSkill = null;
                    renderOptions();
                });

                input.addEventListener('focus', renderOptions);
                input.addEventListener('click', renderOptions);

                input.addEventListener('blur', () => {
                    window.setTimeout(hideOptions, 120);
                });

                addButton.addEventListener('click', async () => {
                    const term = input.value.trim();
                    if (!term) {
                        setFeedback('Escribe una habilidad para agregar.', true);
                        return;
                    }

                    const resolved = selectedSkill && selectedSkill.name === term
                        ? selectedSkill
                        : skillCatalog.find((skill) => !selectedSkillIds[type].has(skill.id) && skill.name.toLowerCase() === term.toLowerCase());

                    if (!resolved) {
                        setFeedback('Selecciona una habilidad valida del catalogo.', true);
                        return;
                    }

                    try {
                        await postJson('{{ route('profile.skills.add') }}', {
                            skill_id: resolved.id,
                            type,
                        });

                        const oppositeType = getOppositeType(type);
                        selectedSkillIds[type].add(resolved.id);
                        selectedSkillIds[oppositeType].delete(resolved.id);

                        const oppositePill = document.querySelector(`[data-skill-pill="${oppositeType}"][data-skill-id="${resolved.id}"]`);
                        if (oppositePill) {
                            oppositePill.remove();
                        }

                        addSkillPill(resolved);
                        input.value = '';
                        selectedSkill = null;
                        hideOptions();
                        setFeedback('Habilidad agregada correctamente.');
                    } catch (error) {
                        setFeedback(error.message, true);
                    }
                });
            };

            root.addEventListener('click', async (event) => {
                const removeSkillButton = event.target.closest('[data-remove-skill]');
                if (removeSkillButton) {
                    const type = removeSkillButton.dataset.removeSkill;
                    const skillId = Number(removeSkillButton.dataset.skillId);
                    const pill = removeSkillButton.closest('[data-skill-pill]');

                    try {
                        await postJson('{{ route('profile.skills.remove') }}', {
                            type,
                            skill_id: skillId,
                        });

                        selectedSkillIds[type].delete(skillId);
                        if (pill) {
                            pill.remove();
                        }
                        setFeedback('Habilidad eliminada correctamente.');
                    } catch (error) {
                        setFeedback(error.message, true);
                    }

                    return;
                }

                const removeAvailabilityButton = event.target.closest('[data-remove-availability]');
                if (removeAvailabilityButton) {
                    const block = removeAvailabilityButton.dataset.removeAvailability;
                    const pill = removeAvailabilityButton.closest('[data-availability-pill]');

                    try {
                        await postJson('{{ route('profile.availability.remove') }}', { block });
                        selectedAvailability.delete(block);
                        if (pill) {
                            pill.remove();
                        }
                        setFeedback('Bloque horario eliminado correctamente.');
                    } catch (error) {
                        setFeedback(error.message, true);
                    }
                }
            });

            const availabilityDay = document.getElementById('availability-day');
            const availabilityBlock = document.getElementById('availability-block');
            const availabilityAdd = document.getElementById('availability-add');
            const availabilityList = document.getElementById('availability-list');

            if (availabilityDay && availabilityBlock && availabilityAdd && availabilityList) {
                availabilityAdd.addEventListener('click', async () => {
                    const day = availabilityDay.value;
                    const timeBlock = availabilityBlock.value;

                    if (!day || !timeBlock) {
                        setFeedback('Selecciona dia y horario.', true);
                        return;
                    }

                    const key = `${day}|${timeBlock}`;
                    if (selectedAvailability.has(key)) {
                        setFeedback('Ese bloque ya esta agregado.', true);
                        return;
                    }

                    try {
                        await postJson('{{ route('profile.availability.add') }}', { block: key });
                        selectedAvailability.add(key);

                        const pill = document.createElement('span');
                        pill.className = 'inline-flex items-center gap-2 rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold uppercase text-gray-700';
                        pill.dataset.availabilityPill = key;
                        pill.innerHTML = `${dayLabels[day]} ${timeBlock}<button type="button" class="h-4 w-4 rounded-full bg-red-500 text-[10px] font-bold leading-none text-white" data-remove-availability="${key}">x</button>`;
                        availabilityList.appendChild(pill);

                        setFeedback('Bloque horario agregado correctamente.');
                    } catch (error) {
                        setFeedback(error.message, true);
                    }
                });
            }

            setupSkillPicker({
                type: 'learn',
                inputId: 'learn-skill-input',
                addButtonId: 'learn-skill-add',
                optionsId: 'learn-skill-options',
                listId: 'learn-skills-list',
            });

            setupSkillPicker({
                type: 'teach',
                inputId: 'teach-skill-input',
                addButtonId: 'teach-skill-add',
                optionsId: 'teach-skill-options',
                listId: 'teach-skills-list',
            });
        })();
    </script>
</section>
