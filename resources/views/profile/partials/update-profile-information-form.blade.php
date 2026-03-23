<section>
    @php
        $profilePhotoUrl = $user->profile_photo_path
            ? asset('storage/' . $user->profile_photo_path)
            : null;
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

    <div class="mt-4 flex items-center gap-4">
        @if ($profilePhotoUrl)
            <img
                src="{{ $profilePhotoUrl }}"
                alt="Foto de perfil de {{ $user->name }}"
                class="h-20 w-20 rounded-full object-cover border border-gray-200"
            >
        @else
            <div class="h-20 w-20 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-2xl font-semibold border border-blue-200">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
        @endif

        <div>
            <p class="text-sm font-semibold text-gray-700">Foto de perfil</p>
            <p class="text-xs text-gray-500">Puedes cambiarla aquí cuando quieras.</p>
        </div>
    </div>

    <form id="profile-update-form" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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
            <x-input-label for="profile_photo" :value="__('Nueva foto de perfil (opcional)')" />
            <input
                id="profile_photo"
                type="file"
                name="profile_photo"
                accept=".png,.jpg,.jpeg,image/png,image/jpeg"
                class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-full file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-200"
            >
            <p class="mt-1 text-xs text-gray-500">Formatos permitidos: .png, .jpg. Tamaño máximo: 2MB.</p>
            <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
            <p id="profile_photo_error" class="mt-2 text-sm text-red-600 hidden"></p>

            <div id="profile_photo_preview_wrapper" class="mt-3 hidden">
                <img
                    id="profile_photo_preview"
                    src=""
                    alt="Vista previa de nueva foto de perfil"
                    class="h-24 w-24 rounded-full object-cover border border-gray-300"
                >
                <p id="profile_photo_name" class="mt-2 text-xs text-gray-600"></p>
            </div>
        </div>

        @if ($profilePhotoUrl)
            <div class="flex items-center gap-2">
                <input
                    id="remove_profile_photo"
                    type="checkbox"
                    name="remove_profile_photo"
                    value="1"
                    class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"
                >
                <label for="remove_profile_photo" class="text-sm text-gray-700">Quitar mi foto actual</label>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('remove_profile_photo')" />
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    <script>
        (() => {
            const form = document.getElementById('profile-update-form');
            const input = document.getElementById('profile_photo');
            const error = document.getElementById('profile_photo_error');
            const previewWrapper = document.getElementById('profile_photo_preview_wrapper');
            const previewImage = document.getElementById('profile_photo_preview');
            const previewName = document.getElementById('profile_photo_name');

            if (!form || !input || !error || !previewWrapper || !previewImage || !previewName) {
                return;
            }

            const maxBytes = 2 * 1024 * 1024;
            const allowedTypes = ['image/png', 'image/jpeg'];
            const allowedExtensions = ['png', 'jpg', 'jpeg'];

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

            const resetPreview = () => {
                previewImage.src = '';
                previewName.textContent = '';
                previewWrapper.classList.add('hidden');
            };

            const updatePreview = (file) => {
                const objectUrl = URL.createObjectURL(file);
                previewImage.src = objectUrl;
                previewName.textContent = file.name;
                previewWrapper.classList.remove('hidden');
            };

            input.addEventListener('change', () => {
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
                }
            });
        })();
    </script>
</section>
