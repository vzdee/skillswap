<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="flex justify-center">
            <p class="text-3xl font-semibold p-8">Bienvenido</p>
        </div>

        <!-- Email Address -->
        <div>
            <div class="relative mt-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </span>
                <x-text-input id="email" class="block w-full rounded-full bg-gray-200 pl-11" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="Correo Electronico *" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-8">
            <div class="relative mt-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5a1.5 1.5 0 0 1 1.5 1.5v7.5a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z" />
                    </svg>
                </span>
                <x-text-input id="password" class="block w-full rounded-full bg-gray-200 pl-11"
                                type="password"
                                name="password"
                                required autocomplete="current-password"
                                placeholder="Contraseña *" />
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex mt-4 justify-end p-5">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 text-[1em] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('Recuperar contraseña?') }}
                    </a>
                @endif
        </div>

        <div class="flex items-center justify-center mt-5 mb-10">
            <x-primary-button class="mt-4 bg-blue-400 hover:bg-blue-500 text-white font-bold text-[1em] py-3 px-4 focus:outline-none focus:shadow-outline rounded-full">
                {{ __('Iniciar Sesión') }}
            </x-primary-button>
        </div>

        <div class="flex mt-4 align-center justify-evenly p-5">
            <p class="text-[1em] text-color-gray-300 justify-center">¿No tienes cuenta?</p>
            <a class="underline text-[1em] text-blue-400 hover:text-gray-900 font-bold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('register') }}">
                    {{ __('Registrar Cuenta') }}
            </a>

        </div>
    </form>
</x-guest-layout>
