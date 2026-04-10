<a href="{{ route('dashboard') }}"
    class="inline-flex items-center text-gray-800 font-semibold text-base mb-5 hover:text-gray-600 transition">
    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
    </svg>
    Regresar
</a>
<section>
    @php
        $profilePhotoUrl = $user->profile_photo_url;
    @endphp

    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form id="profile-update-form" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data"
        class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <p id="profile-update-success" class="hidden text-sm font-medium text-green-600"></p>

        <div
            class="flex flex-col gap-4 rounded-lg border border-gray-200 p-4 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <div class="h-24 w-24">
                    <img id="profile_photo_current" src="{{ $profilePhotoUrl ?? '' }}"
                        alt="Foto de perfil de {{ $user->name }}"
                        class="h-24 w-24 rounded-full object-cover border border-gray-200 {{ $profilePhotoUrl ? '' : 'hidden' }}">

                    <div id="profile_photo_placeholder"
                        class="h-24 w-24 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-2xl font-semibold border border-blue-200 {{ $profilePhotoUrl ? 'hidden' : '' }}">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
            </div>

            <div class="w-full md:max-w-sm">
                <p class="text-sm font-medium text-gray-700">{{ __('Nueva foto de perfil (opcional)') }}</p>
                <input id="profile_photo" type="file" name="profile_photo"
                    accept=".png,.jpg,.jpeg,image/png,image/jpeg" class="hidden">
                <button id="profile-photo-trigger" type="button"
                    class="mt-1 inline-flex items-center rounded-full border-0 bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-200">
                    Choose File
                </button>
                <input id="remove_profile_photo" type="hidden" name="remove_profile_photo" value="0">
                <p class="mt-1 text-xs text-gray-500">Formatos permitidos: .png, .jpg. Tamaño máximo: 2MB.</p>
                <p id="profile_photo_name" class="mt-2 text-xs text-gray-600"></p>
                <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
                <p id="profile_photo_error" class="mt-2 text-sm text-red-600 hidden"></p>

                @if ($profilePhotoUrl)
                    <div class="mt-3">
                        <button id="open-remove-photo-modal" type="button"
                            class="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                fill="currentColor" aria-hidden="true">
                                <path
                                    d="M9 3.75A2.25 2.25 0 0 1 11.25 1.5h1.5A2.25 2.25 0 0 1 15 3.75V4.5h3.75a.75.75 0 0 1 0 1.5h-.633l-.86 13.75A2.25 2.25 0 0 1 15.013 22.5H8.987a2.25 2.25 0 0 1-2.244-2.25L5.883 6H5.25a.75.75 0 0 1 0-1.5H9v-.75Zm1.5.75h3v-.75a.75.75 0 0 0-.75-.75h-1.5a.75.75 0 0 0-.75.75v.75Zm-1.495 3a.75.75 0 0 0-.75.798l.6 10.5a.75.75 0 1 0 1.498-.086l-.6-10.5a.75.75 0 0 0-.748-.712Zm6.74.799a.75.75 0 1 0-1.498-.086l-.6 10.5a.75.75 0 0 0 1.498.086l.6-10.5Z" />
                            </svg>
                            Remover foto
                        </button>
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('remove_profile_photo')" />
                @endif
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)"
                required autofocus autocomplete="name" />
            <p id="profile-name-error" class="mt-2 hidden text-sm text-red-600"></p>
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email"
                class="mt-1 block w-full bg-gray-100 text-gray-500 cursor-not-allowed" :value="$user->email"
                autocomplete="username" readonly disabled />
            <p class="mt-2 text-xs text-gray-500">
                {{ __('This email belongs to your logged-in account and cannot be changed.') }}</p>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification"
                            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="birth_date" value="Fecha de nacimiento" />
            <x-text-input id="birth_date" name="birth_date" type="text" class="mt-1 block w-full" :value="old('birth_date', optional(data_get($user, 'birth_date'))->format('Y-m-d'))"
                data-birth-date-picker placeholder="Selecciona tu fecha de nacimiento" />
            <p id="profile-birth-date-error" class="mt-2 hidden text-sm text-red-600"></p>
            <x-input-error class="mt-2" :messages="$errors->get('birth_date')" />
        </div>

        <div>
            <x-input-label for="career" value="Carrera" />
            <div class="relative mt-1">
                <select id="career" name="career"
                    class="block w-full appearance-none rounded-2xl border-gray-300 py-2.5 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Selecciona tu carrera</option>
                    <option value="ingenieria_biomedica" @selected(old('career', data_get($user, 'career')) === 'ingenieria_biomedica' || old('career', data_get($user, 'career')) === 'derecho')>Ingenieria Biomedica</option>
                    <option value="ingenieria_sistemas" @selected(old('career', data_get($user, 'career')) === 'ingenieria_sistemas')>Ingenieria en Sistemas</option>
                    <option value="administracion_de_empresas" @selected(old('career', data_get($user, 'career')) === 'administracion_de_empresas')>Administracion de Empresas
                    </option>
                    <option value="ingenieria_industrial" @selected(old('career', data_get($user, 'career')) === 'ingenieria_industrial')>Ingenieria Industrial</option>
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"
                        aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
            </div>
            <p id="profile-career-error" class="mt-2 hidden text-sm text-red-600"></p>
            <x-input-error class="mt-2" :messages="$errors->get('career')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button id="profile-update-submit">{{ __('Save') }}</x-primary-button>
        </div>
    </form>

    @if ($profilePhotoUrl)
        <div id="remove-photo-modal"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 px-4 opacity-0 transition-opacity duration-500 ease-out">
            <div id="remove-photo-modal-panel"
                class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl opacity-0 scale-95 translate-y-1 transition duration-200 ease-out">
                <h3 class="text-lg font-semibold text-gray-900">Eliminar foto de perfil</h3>
                <p class="mt-2 text-sm text-gray-600">¿Seguro que quieres borrar tu foto de perfil? Esta acción no se
                    puede deshacer.</p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button id="cancel-remove-photo" type="button"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                        Cancelar
                    </button>
                    <button id="confirm-remove-photo" type="button"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script>
        (() => {
            const form = document.getElementById('profile-update-form');
            const input = document.getElementById('profile_photo');
            const photoTrigger = document.getElementById('profile-photo-trigger');
            const removePhotoInput = document.getElementById('remove_profile_photo');
            const openRemovePhotoModalButton = document.getElementById('open-remove-photo-modal');
            const removePhotoModal = document.getElementById('remove-photo-modal');
            const removePhotoModalPanel = document.getElementById('remove-photo-modal-panel');
            const cancelRemovePhotoButton = document.getElementById('cancel-remove-photo');
            const confirmRemovePhotoButton = document.getElementById('confirm-remove-photo');
            const error = document.getElementById('profile_photo_error');
            const nameError = document.getElementById('profile-name-error');
            const birthDateError = document.getElementById('profile-birth-date-error');
            const careerError = document.getElementById('profile-career-error');
            const currentPhoto = document.getElementById('profile_photo_current');
            const photoPlaceholder = document.getElementById('profile_photo_placeholder');
            const previewName = document.getElementById('profile_photo_name');
            const submitButton = document.getElementById('profile-update-submit');
            const success = document.getElementById('profile-update-success');

            if (!form || !input || !photoTrigger || !removePhotoInput || !error || !nameError || !birthDateError || !
                careerError || !currentPhoto || !photoPlaceholder || !previewName || !submitButton || !success) {
                return;
            }

            const maxBytes = 2 * 1024 * 1024;
            const allowedTypes = ['image/png', 'image/jpeg'];
            const allowedExtensions = ['png', 'jpg', 'jpeg'];
            let currentSavedPhotoUrl = '{{ $profilePhotoUrl ?? '' }}';
            let currentPreviewObjectUrl = null;

            const getExtension = (filename) => filename.split('.').pop().toLowerCase();

            const validateFile = (file) => {
                if (!file) {
                    return '';
                }

                const extension = getExtension(file.name || '');
                const isAllowedType = allowedTypes.includes(file.type) || allowedExtensions.includes(extension);

                if (!isAllowedType) {
                    return 'Solo se permiten archivos PNG o JPG.';
                }

                if (file.size > maxBytes) {
                    return 'La imagen no puede superar 2MB.';
                }

                return '';
            };

            const setError = (message) => {
                if (message) {
                    error.textContent = message;
                    error.classList.remove('hidden');
                } else {
                    error.textContent = '';
                    error.classList.add('hidden');
                }
            };

            const clearPreviewObjectUrl = () => {
                if (currentPreviewObjectUrl) {
                    URL.revokeObjectURL(currentPreviewObjectUrl);
                    currentPreviewObjectUrl = null;
                }
            };

            const showPlaceholder = () => {
                currentPhoto.src = '';
                currentPhoto.classList.add('hidden');
                photoPlaceholder.classList.remove('hidden');
            };

            const showPhoto = (src) => {
                currentPhoto.src = src;
                currentPhoto.classList.remove('hidden');
                photoPlaceholder.classList.add('hidden');
            };

            const resetPreview = () => {
                clearPreviewObjectUrl();
                previewName.textContent = '';

                if (currentSavedPhotoUrl) {
                    showPhoto(currentSavedPhotoUrl);
                    return;
                }

                showPlaceholder();
            };

            const updatePreview = (file) => {
                clearPreviewObjectUrl();
                currentPreviewObjectUrl = URL.createObjectURL(file);
                showPhoto(currentPreviewObjectUrl);
                previewName.textContent = file.name;
            };

            photoTrigger.addEventListener('click', () => {
                input.click();
            });

            input.addEventListener('change', () => {
                removePhotoInput.value = '0';
                const file = input.files[0];
                const message = validateFile(file);

                if (message) {
                    input.value = '';
                    resetPreview();
                } else if (file) {
                    updatePreview(file);
                } else {
                    resetPreview();
                }

                setError(message);
            });

            form.addEventListener('submit', (event) => {
                const message = validateFile(input.files[0]);

                if (message) {
                    event.preventDefault();
                    setError(message);
                    return;
                }

                event.preventDefault();

                error.textContent = '';
                error.classList.add('hidden');
                nameError.textContent = '';
                nameError.classList.add('hidden');
                birthDateError.textContent = '';
                birthDateError.classList.add('hidden');
                careerError.textContent = '';
                careerError.classList.add('hidden');
                success.textContent = '';
                success.classList.add('hidden');

                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');

                fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                    .then(async (response) => {
                        const data = await response.json();

                        if (!response.ok) {
                            const errors = data.errors || {};

                            if (errors.name) {
                                nameError.textContent = Array.isArray(errors.name) ? errors.name[0] :
                                    errors.name;
                                nameError.classList.remove('hidden');
                            }

                            if (errors.birth_date) {
                                birthDateError.textContent = Array.isArray(errors.birth_date) ? errors
                                    .birth_date[0] : errors.birth_date;
                                birthDateError.classList.remove('hidden');
                            }

                            if (errors.career) {
                                careerError.textContent = Array.isArray(errors.career) ? errors.career[
                                    0] : errors.career;
                                careerError.classList.remove('hidden');
                            }

                            if (errors.profile_photo) {
                                error.textContent = Array.isArray(errors.profile_photo) ? errors
                                    .profile_photo[0] : errors.profile_photo;
                                error.classList.remove('hidden');
                            }

                            return;
                        }

                        removePhotoInput.value = '0';

                        success.textContent = data.message ||
                            '{{ __('Profile updated successfully.') }}';
                        success.classList.remove('hidden');

                        const user = data.user || {};
                        if (user.name) {
                            document.querySelectorAll('[data-profile-name]').forEach((node) => {
                                node.textContent = user.name;
                            });
                        }

                        if (Object.prototype.hasOwnProperty.call(user, 'profile_photo_url')) {
                            currentSavedPhotoUrl = user.profile_photo_url || '';

                            if (currentSavedPhotoUrl) {
                                showPhoto(currentSavedPhotoUrl);
                            } else {
                                showPlaceholder();
                            }

                            clearPreviewObjectUrl();
                            previewName.textContent = '';

                            document.querySelectorAll('[data-profile-avatar]').forEach((node) => {
                                if (node.tagName === 'IMG') {
                                    if (user.profile_photo_url) {
                                        node.src = user.profile_photo_url;
                                        node.classList.remove('hidden');
                                    } else {
                                        node.classList.add('hidden');
                                    }
                                }
                            });
                        }
                    })
                    .catch(() => {
                        nameError.textContent =
                            '{{ __('An unexpected error occurred. Please try again.') }}';
                        nameError.classList.remove('hidden');
                    })
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.classList.remove('opacity-60', 'cursor-not-allowed');
                    });
            });

            if (openRemovePhotoModalButton && removePhotoModal && removePhotoModalPanel && cancelRemovePhotoButton &&
                confirmRemovePhotoButton) {
                const openRemovePhotoModal = () => {
                    removePhotoModal.classList.remove('hidden');
                    removePhotoModal.classList.add('flex');

                    requestAnimationFrame(() => {
                        removePhotoModal.classList.add('opacity-100');
                        removePhotoModalPanel.classList.add('opacity-100', 'scale-100', 'translate-y-0');
                        removePhotoModalPanel.classList.remove('opacity-0', 'scale-95', 'translate-y-1');
                    });
                };

                const closeRemovePhotoModal = () => {
                    removePhotoModal.classList.remove('opacity-100');
                    removePhotoModalPanel.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
                    removePhotoModalPanel.classList.add('opacity-0', 'scale-95', 'translate-y-1');

                    window.setTimeout(() => {
                        removePhotoModal.classList.add('hidden');
                        removePhotoModal.classList.remove('flex');
                    }, 200);
                };

                openRemovePhotoModalButton.addEventListener('click', openRemovePhotoModal);
                cancelRemovePhotoButton.addEventListener('click', closeRemovePhotoModal);

                removePhotoModal.addEventListener('click', (event) => {
                    if (event.target === removePhotoModal) {
                        closeRemovePhotoModal();
                    }
                });

                confirmRemovePhotoButton.addEventListener('click', () => {
                    removePhotoInput.value = '1';
                    currentSavedPhotoUrl = '';
                    input.value = '';
                    resetPreview();
                    closeRemovePhotoModal();
                    form.requestSubmit();
                });
            }
        })();
    </script>
</section>
