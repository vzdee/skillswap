<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Asegúrate de que tu cuenta utiliza una contraseña larga y aleatoria para mantenerla segura. Una contraseña fuerte tiene al menos 8 caracteres.') }}
        </p>
    </header>

    <p id="update-password-success" class="mt-4 hidden text-sm font-medium text-green-600"></p>

    <form id="update-password-form" method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" placeholder="{{ __('Contraseña Actual') }}" />
            <p id="update-password-current-error" class="mt-2 hidden text-sm text-red-600"></p>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="{{ __('Al menos 8 caracteres *') }}" />
            <p id="update-password-new-error" class="mt-2 hidden text-sm text-red-600"></p>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="{{ __('Confirmar Contraseña *') }}" />
            <p id="update-password-confirm-error" class="mt-2 hidden text-sm text-red-600"></p>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button id="update-password-submit">{{ __('Save') }}</x-primary-button>
        </div>
    </form>

    <script>
        (() => {
            const form = document.getElementById('update-password-form');
            const submitButton = document.getElementById('update-password-submit');
            const success = document.getElementById('update-password-success');
            const currentError = document.getElementById('update-password-current-error');
            const passwordError = document.getElementById('update-password-new-error');
            const confirmError = document.getElementById('update-password-confirm-error');

            if (!form || !submitButton || !success || !currentError || !passwordError || !confirmError) {
                return;
            }

            const clearErrors = () => {
                [currentError, passwordError, confirmError].forEach((node) => {
                    node.textContent = '';
                    node.classList.add('hidden');
                });
            };

            const setError = (node, message) => {
                if (!message) {
                    return;
                }

                node.textContent = Array.isArray(message) ? message[0] : message;
                node.classList.remove('hidden');
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearErrors();

                success.textContent = '';
                success.classList.add('hidden');

                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        const errors = data.errors || {};
                        setError(currentError, errors.current_password);
                        setError(passwordError, errors.password);
                        setError(confirmError, errors.password_confirmation);
                        return;
                    }

                    form.reset();
                    success.textContent = data.message || '{{ __('Password updated successfully.') }}';
                    success.classList.remove('hidden');
                } catch (error) {
                    setError(passwordError, '{{ __('An unexpected error occurred. Please try again.') }}');
                } finally {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            });
        })();
    </script>
</section>
