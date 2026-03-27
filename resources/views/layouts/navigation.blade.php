<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    @php
        $authUser = \App\Models\User::query()->findOrFail(Auth::id());

        $profilePhotoUrl = $authUser->profile_photo_path
            ? asset('storage/' . $authUser->profile_photo_path)
            : null;
    @endphp

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Matches') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-4 lg:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex max-w-[14rem] items-center px-2 py-2 text-sm leading-4 font-medium rounded-md border border-transparent bg-white text-gray-500 transition ease-in-out duration-150 hover:text-gray-700 focus:outline-none lg:max-w-none lg:px-3">
                            @if ($profilePhotoUrl)
                                <img
                                    src="{{ $profilePhotoUrl }}"
                                    alt="Foto de perfil de {{ $authUser->name }}"
                                    data-profile-avatar
                                    class="h-9 w-9 rounded-full object-cover border border-gray-200"
                                >
                            @else
                                <div class="h-9 w-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold border border-blue-200">
                                    {{ strtoupper(substr($authUser->name, 0, 1)) }}
                                </div>
                            @endif

                            <div class="ms-2 max-w-[8.5rem] truncate lg:ms-3 lg:max-w-none" data-profile-name>{{ $authUser->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.view')">
                            {{ __('Ver Perfil') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Configurar Cuenta') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar Sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t">
            <div class="px-4">
                @if ($profilePhotoUrl)
                    <img
                        src="{{ $profilePhotoUrl }}"
                        alt="Foto de perfil de {{ $authUser->name }}"
                        data-profile-avatar
                        class="h-14 w-14 rounded-full object-cover border border-gray-200 mb-3"
                    >
                @else
                    <div class="h-14 w-14 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-semibold border border-blue-200 mb-3">
                        {{ strtoupper(substr($authUser->name, 0, 1)) }}
                    </div>
                @endif

                <div class="font-medium text-base text-gray-800" data-profile-name>{{ $authUser->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ $authUser->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
