<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <div></div>
            <x-input-label for="email" :value="__('Correo Electrónico')" />
            <x-text-input id="email" class="block mt-1 w-full rounded-2xl" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input id="password" class="block mt-1 w-full rounded-2xl"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex mt-4 justify-end">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('Recuperar contraseña?') }}
                    </a>
                @endif
        </div>

        <x-primary-button class="flex mt-4 justify-center w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:shadow-outline rounded-2xl">
            {{ __('Iniciar Sesión') }}
        </x-primary-button>

        <div class="flex mt-4 align-center justify-evenly">
            <p class="text-sm text-color-gray-300 justify-center">No tienes cuenta?</p>
            <a class="underline text-sm text-blue-500 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('register') }}">
                    {{ __('Registrate') }}
            </a>

        </div>
    </form>
</x-guest-layout>
