<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
        @csrf

        <p class="text-orange-500 mb-5 font-semibold">Los campos obligatorios están marcados con un asterisco (*).</p>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 md:gap-8">
            <div>
                <!-- Name -->
                <div>
                    <x-input-label for="name" :value="__('Nombre')" class="ml-2" />
                    <x-text-input id="name" class="block mt-1 w-full bg-gray-200" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Nombre Completo *" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Email Address -->
                <div class="mt-6">
                    <x-input-label for="email" :value="__('Correo Electrónico')" class="ml-2" />
                    <x-text-input id="email" class="block mt-2 w-full bg-gray-200" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="Correo Electronico *" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="mt-6">
                    <x-input-label for="password" :value="__('Contraseña')" class="ml-2"/>
                    <x-text-input id="password" class="block mt-1 w-full bg-gray-200"
                                    type="password"
                                    name="password"
                                    required autocomplete="new-password"
                                    placeholder="Almenos 8 carácteres *"/>

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div class="mt-6">
                    <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" class="ml-2" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full bg-gray-200"
                                    type="password"
                                    name="password_confirmation" required autocomplete="new-password"
                                    placeholder="Confirmar Contraseña * "/>

                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
            </div>

            <div class="flex h-full flex-col items-center justify-center">
                <div class="w-full max-w-sm">
                    <label for="profile_photo" class="mb-2 block text-center text-md font-semibold text-gray-700">Sube tu foto de perfil</label>

                    <label for="profile_photo" class="group flex w-full cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-gray-400 bg-gray-200 px-6 py-10 text-center transition-colors hover:bg-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mb-3 h-10 w-10 text-gray-600 transition-transform group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5h9a5.25 5.25 0 1 0-.16-10.498A6 6 0 0 0 4.5 9.75m7.5 8.25V9.75m0 0-3 3m3-3 3 3" />
                        </svg>
                        <span class="text-sm font-semibold text-gray-700">Haz clic para subir tu foto</span>
                        <span class="mt-1 text-xs text-gray-600">Formatos permitidos: .png, .jpg</span>
                        <span class="mt-1 text-xs text-gray-600">Tamaño máximo: 3MB</span>
                    </label>

                    <input
                        id="profile_photo"
                        type="file"
                        name="profile_photo"
                        accept=".png,.jpg,.jpeg,image/png,image/jpeg"
                        class="sr-only"
                        onchange="if (this.files[0] && this.files[0].size > 3145728) { alert('La imagen no puede superar 3MB.'); this.value = ''; }"
                    />

                    <x-input-error :messages="$errors->get('profile_photo')" class="mt-2" />
                </div>
            </div>
        </div>
        
        <div class="flex items-center justify-center mt-5 mb-10">
            <x-primary-button class=" bg-blue-400 hover:bg-blue-500 text-white font-bold text-[1em] py-3 px-4 focus:outline-none focus:shadow-outline rounded-full">
                    {{ __('Registrar') }}
                </x-primary-button>
        </div>

        <div class="flex mt-4 align-center justify-evenly p-5">
            <p class="text-[1em] text-color-gray-300 justify-center">¿Ya tienes cuenta?</p>
            <a href="{{ route('login') }}" class="underline text-[1em] text-blue-400 hover:text-gray-900 font-bold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Inciar Sesión') }}
            </a>
        </div>
    </form>
</x-guest-layout>
