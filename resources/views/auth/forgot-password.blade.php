<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form id="forgot-password-form" method="POST" action="{{ route('password.email') }}" class="space-y-7">
        @csrf

        <div class="flex flex-col text-center justify-center">
            <h1 class="text-2xl font-bold">Recuperar Contraseña</h1>
            <p class="text-sm text-gray-600">Correo electrónico para la recuperación de contraseña.</p>
        </div>
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Correo Electrónico')" class="ml-2" />
            <x-text-input id="email" class="block mt-1 w-full bg-gray-200" type="email" name="email" :value="old('email')" placeholder="example@email.com *" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <p id="reset-cooldown-message" class="mt-2 hidden text-sm text-gray-600"></p>
        </div>

        <div class="flex items-center justify-center pt-2">
            <x-primary-button id="send-reset-link-btn" class="text-[1em] disabled:bg-blue-200 disabled:hover:bg-blue-200 disabled:cursor-not-allowed" autocomplete="off">
                {{ __('Enviar') }}
            </x-primary-button>
        </div>
        <div class="flex justify-center pt-2">
            <a href="{{ route('login') }}" class="underline text-sm text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Regresar al inicio de sesión
            </a>
        </div>
    </form>

    <script>
        (() => {
            const sendButton = document.getElementById('send-reset-link-btn');
            const cooldownMessage = document.getElementById('reset-cooldown-message');
            const cooldownStorageKey = 'passwordResetCooldownEndsAt';
            const hasStatus = @json(session()->has('status'));
            const hasErrors = @json($errors->any());
            let interval = null;

            if (!sendButton || !cooldownMessage) {
                return;
            }

            const formatSeconds = (seconds) => {
                return `00:${String(seconds).padStart(2, '0')}s`;
            };

            const stopCooldown = () => {
                if (interval !== null) {
                    window.clearInterval(interval);
                    interval = null;
                }

                sendButton.disabled = false;
                cooldownMessage.classList.add('hidden');
                cooldownMessage.textContent = '';
                window.localStorage.removeItem(cooldownStorageKey);
            };

            const startCooldownFromTimestamp = (endsAt) => {
                if (interval !== null) {
                    window.clearInterval(interval);
                }

                sendButton.disabled = true;
                cooldownMessage.classList.remove('hidden');

                const tick = () => {
                    const remaining = Math.ceil((endsAt - Date.now()) / 1000);

                    if (remaining <= 0) {
                        stopCooldown();
                        return;
                    }

                    cooldownMessage.textContent = `Enviar nuevamente en ${formatSeconds(remaining)}`;
                };

                tick();
                interval = window.setInterval(tick, 1000);
            };

            const startCooldown = (durationSeconds) => {
                const endsAt = Date.now() + (durationSeconds * 1000);
                window.localStorage.setItem(cooldownStorageKey, String(endsAt));
                startCooldownFromTimestamp(endsAt);
            };

            const savedEndsAt = Number(window.localStorage.getItem(cooldownStorageKey));

            if (savedEndsAt && savedEndsAt > Date.now()) {
                startCooldownFromTimestamp(savedEndsAt);
            } else {
                window.localStorage.removeItem(cooldownStorageKey);
            }

            if (hasStatus) {
                startCooldown(30);
            } else if (hasErrors) {
                stopCooldown();
            }
        })();
    </script>
</x-guest-layout>
