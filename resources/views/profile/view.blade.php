<x-app-layout>
  <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('View Profile') }}
        </h2>
    </x-slot>
    @php
        $profilePhotoUrl = $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : null;
        $age = $user->birth_date?->age;
        $careerLabels = [
            'ingenieria_biomedica' => 'Ingenieria Biomedica',
            'derecho' => 'Ingenieria Biomedica',
            'ingenieria_sistemas' => 'Ingenieria En Sistemas',
            'administracion' => 'Administracion',
            'ingenieria_industrial' => 'Ingenieria Industrial',
        ];
        $careerLabel = $user->career
            ? ($careerLabels[$user->career] ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $user->career)))
            : null;
    @endphp

    <div class="py-8 px-4 sm:px-6">
        <div class="mx-auto max-w-4xl">
          
          <div class="bg-white rounded-3xl shadow-md p-5 sm:p-8">
              <a href="{{ route('dashboard') }}" class="inline-flex items-center text-gray-800 font-semibold text-base mb-5 hover:text-gray-600 transition">
                  <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                  </svg>
                  Regresar
              </a>
                <div class="flex flex-col md:flex-row md:items-start gap-6">
                    <div class="flex-shrink-0">
                        @if ($profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="Foto de perfil" class="w-28 h-28 sm:w-36 sm:h-36 rounded-full object-cover border border-gray-200">
                        @else
                            <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-2xl sm:text-3xl font-semibold border border-blue-200">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                            <h1 class="text-2xl font-bold text-gray-900 break-words">{{ $user->name }}</h1>
                            <div class="flex text-yellow-400 text-4xl leading-none">
                                <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
                            </div>
                        </div>

                        <div class="mt-2 space-y-1 text-md text-gray-700">
                            @if ($age)
                                <p>Tengo {{ $age }} años</p>
                            @else
                                <p class="text-sm text-red-600 font-medium">Debes colocar tu fecha de nacimiento para mostrar tu edad.</p>
                            @endif

                            @if ($careerLabel)
                                <p>Estudio {{ $careerLabel }}</p>
                            @else
                                <p class="text-sm text-red-600 font-medium">Debes seleccionar una carrera para mostrarla en tu perfil.</p>
                            @endif

                            @if (! $profilePhotoUrl)
                                <p class="text-sm text-red-600 font-medium">Debes agregar una foto de perfil.</p>
                            @endif
                        </div>

                        <div class="mt-6 space-y-4">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Intereses</h2>
                                <div class="mt-2 min-h-12 p-2 flex flex-wrap gap-2">
                                    @forelse ($learningSkills as $skill)
                                        <span class="inline-flex items-center rounded-md bg-gray-300 px-3 py-1 text-xs font-semibold uppercase text-gray-700">{{ $skill->name }}</span>
                                    @empty
                                        <p class="text-sm text-red-500">No has agregado intereses todavia.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Habilidades</h2>
                                <div class="mt-2 min-h-12  p-2 flex flex-wrap gap-2">
                                    @forelse ($taughtSkills as $skill)
                                        <span class="inline-flex items-center rounded-md bg-gray-300 px-3 py-1 text-xs font-semibold uppercase text-gray-700">{{ $skill->name }}</span>
                                    @empty
                                        <p class="text-sm text-red-500">No has agregado habilidades todavia.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Horarios</h2>
                                <div class="mt-2 min-h-14 p-2 flex flex-wrap gap-2 items-start">
                                    @forelse ($availabilities as $availability)
                                        <span class="inline-flex items-center rounded-md bg-gray-300 px-3 py-1 text-xs font-semibold uppercase text-gray-700">
                                            {{ data_get($availabilityDays, $availability->weekday, ucfirst($availability->weekday)) }} {{ $availability->time_block }}
                                        </span>
                                    @empty
                                        <p class="text-sm text-red-500">No has agregado horarios todavia.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <h2 class="text-xl font-bold text-gray-900">Ultimas Reseñas</h2>
                    <div class="mt-3 rounded-3xl bg-gray-50 min-h-56 p-4 sm:p-6"></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
