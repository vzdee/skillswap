<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-7">
        @csrf

        <div class="flex flex-col text-center justify-center">
            <h1 class="text-2xl font-bold">Recuperar Contraseña</h1>
            <p class="text-sm text-gray-600">Correo electrónico para la recuperación de contraseña.</p>
        </div>
        <!-- Email Address -->
        <div>
            <x-text-input id="email" class="block mt-1 w-full bg-gray-200" type="email" name="email" :value="old('email')" placeholder="example@email.com *" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-center pt-2">
            <x-primary-button class="text-[1em]">
                {{ __('Enviar') }}
            </x-primary-button>
        </div>
        <div class="flex justify-center pt-2">
            <a href="{{ route('login') }}" class="underline text-sm text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Regresar al inicio de sesión
            </a>
        </div>
    </form>
</x-guest-layout>
