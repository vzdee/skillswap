@php
    $embeddedProfile = $embeddedProfile ?? false;
    $profilePhotoUrl = $user->profile_photo_url;
    $age = $user->birth_date?->age;
    $averageRating = $averageRating ?? null;
    $filledStars = (int) round((float) ($averageRating ?? 0));
    $reviewEligibility = $reviewEligibility ?? [];
    $isOwnProfile = (bool) data_get($reviewEligibility, 'isOwnProfile', true);
    $hasAcceptedMatch = (bool) data_get($reviewEligibility, 'hasAcceptedMatch', false);
    $messagesExchangedCount = (int) data_get($reviewEligibility, 'messagesExchangedCount', 0);
    $minimumMessagesRequired = (int) data_get($reviewEligibility, 'minimumMessagesRequired', 10);
    $canLeaveReview = (bool) data_get($reviewEligibility, 'canLeaveReview', false);
    $existingReview = data_get($reviewEligibility, 'existingReview');
    $existingRating = (int) old('rating', data_get($existingReview, 'rating', 0));
    $existingComment = (string) old('comment', data_get($existingReview, 'comment', ''));
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

<div class="{{ $embeddedProfile ? 'py-4 px-4 sm:px-6' : 'py-8 px-4 sm:px-6' }}">
    <div class="mx-auto max-w-4xl">
      <div class="relative bg-white rounded-3xl shadow-md p-5 sm:p-8">
            @if (! $embeddedProfile)
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-gray-800 font-semibold text-base mb-5 hover:text-gray-600 transition">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Regresar
                </a>
            @endif

            <div class="absolute top-5 right-5 sm:top-8 sm:right-8 flex flex-col items-end gap-1">
                <div class="flex items-center gap-1 text-3xl leading-none">
                    @for ($star = 1; $star <= 5; $star++)
                        <span class="{{ $star <= $filledStars ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                    @endfor
                </div>
            </div>

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
                    <div class="pr-28 sm:pr-36 lg:pr-44">
                        <h1 class="text-2xl font-bold text-gray-900 break-words">{{ $user->name }}</h1>
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
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-xl font-bold text-gray-900">Ultimas Reseñas</h2>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
                        {{ $receivedReviews->count() === 1 ? '1 reseña' : $receivedReviews->count() . ' reseñas' }}
                    </span>
                </div>

                <div class="mt-3 rounded-3xl bg-gray-50 min-h-56 p-4 sm:p-6">
                    @forelse ($receivedReviews as $review)
                        <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 {{ ! $loop->last ? 'mb-4' : '' }}">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex items-center gap-3">
                                    @if ($review->reviewer?->profile_photo_url)
                                        <img src="{{ $review->reviewer->profile_photo_url }}" alt="Foto de {{ $review->reviewer->name }}" class="h-10 w-10 rounded-full object-cover">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                                            {{ strtoupper(substr($review->reviewer?->name ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif

                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ $review->reviewer?->name ?? 'Usuario' }}</h3>
                                        <p class="text-sm text-gray-500">{{ $review->created_at->format('d/m/Y') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 text-lg leading-none">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <span class="{{ $star <= (int) $review->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                                    @endfor
                                </div>
                            </div>

                            @if ($review->comment)
                                <p class="mt-3 text-sm leading-6 text-gray-700">{{ $review->comment }}</p>
                            @else
                                <p class="mt-3 text-sm italic text-gray-400">Sin comentario adicional.</p>
                            @endif
                        </article>
                    @empty
                        <div class="flex min-h-40 flex-col items-center justify-center rounded-2xl border-gray-300 bg-white px-6 py-10 text-center">
                            <p class="text-base font-semibold text-gray-700">Todavía no tienes reseñas</p>
                            <p class="mt-1 text-sm text-gray-500">Cuando tus matches te califiquen, aparecerán aquí.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            @if (! $isOwnProfile)
                <div class="mt-8 rounded-3xl bg-gray-50 p-4 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-xl font-bold text-gray-900">Tu evaluación</h2>
                        <p class="text-sm font-semibold text-gray-500">Califica a {{ $user->name }}</p>
                    </div>

                    @if (session('request_success'))
                        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                            {{ session('request_success') }}
                        </div>
                    @endif

                    @if (session('request_error'))
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                            {{ session('request_error') }}
                        </div>
                    @endif

                    @if ($canLeaveReview)
                        <form id="profile-review-form" method="POST" action="{{ route('reviews.store') }}" class="mt-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 sm:p-5">
                            @csrf
                            <input type="hidden" name="reviewed_user_id" value="{{ $user->id }}">
                            <input type="hidden" id="profile-review-rating" name="rating" value="{{ $existingRating > 0 ? $existingRating : '' }}">

                            <div>
                                <p class="text-sm font-semibold text-gray-700">Selecciona estrellas</p>
                                <div class="mt-2 flex items-center gap-1 text-4xl leading-none" role="radiogroup" aria-label="Calificacion con estrellas">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <button
                                            type="button"
                                            class="profile-review-star transition hover:scale-105 {{ $star <= $existingRating ? 'text-yellow-400' : 'text-gray-300' }}"
                                            data-rating-star="{{ $star }}"
                                            aria-label="{{ $star }} estrella{{ $star > 1 ? 's' : '' }}"
                                        >★</button>
                                    @endfor
                                </div>
                                <p id="profile-review-rating-error" class="mt-2 hidden text-sm font-semibold text-red-600">Selecciona una calificación de 1 a 5 estrellas.</p>
                                @error('rating')
                                    <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4">
                                <label for="profile-review-comment" class="text-sm font-semibold text-gray-700">Reseña</label>
                                <textarea
                                    id="profile-review-comment"
                                    name="comment"
                                    rows="4"
                                    maxlength="100"
                                    placeholder="Escribe tu experiencia con esta persona..."
                                    class="mt-2 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >{{ $existingComment }}</textarea>
                                <p id="profile-review-comment-counter" class="mt-1 text-xs text-gray-500">100 caracteres restantes</p>
                                @error('comment')
                                    <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-3">
                                <p class="text-xs text-gray-500">{{ data_get($existingReview, 'id') ? 'Puedes actualizar tu evaluación cuando quieras.' : 'Tu evaluación ayudará a otros usuarios.' }}</p>
                                <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    {{ data_get($existingReview, 'id') ? 'Actualizar evaluación' : 'Guardar evaluación' }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="mt-4 rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-5 text-sm text-gray-700">
                            @if (! $hasAcceptedMatch)
                                <p class="font-semibold text-gray-800">Necesitas un match aceptado para poder calificar.</p>
                                <p class="mt-1 text-gray-500">Cuando esta persona y tú acepten la solicitud, se habilitará la evaluación.</p>
                            @else
                                <p class="font-semibold text-gray-800">La evaluación se habilita después de {{ $minimumMessagesRequired }} mensajes en conjunto.</p>
                                <p class="mt-1 text-gray-500">Actualmente llevan {{ $messagesExchangedCount }} de {{ $minimumMessagesRequired }} mensajes.</p>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@if (! $isOwnProfile && $canLeaveReview)
    <script>
        (() => {
            const form = document.getElementById('profile-review-form');
            const ratingInput = document.getElementById('profile-review-rating');
            const ratingError = document.getElementById('profile-review-rating-error');
            const commentInput = document.getElementById('profile-review-comment');
            const commentCounter = document.getElementById('profile-review-comment-counter');

            if (!form || !ratingInput) {
                return;
            }

            const stars = Array.from(form.querySelectorAll('[data-rating-star]'));

            const paintStars = (ratingValue) => {
                stars.forEach((star) => {
                    const value = Number.parseInt(star.dataset.ratingStar || '0', 10);
                    if (value <= ratingValue) {
                        star.classList.add('text-yellow-400');
                        star.classList.remove('text-gray-300');
                    } else {
                        star.classList.add('text-gray-300');
                        star.classList.remove('text-yellow-400');
                    }
                });
            };

            const updateCommentCounter = () => {
                if (!commentInput || !commentCounter) {
                    return;
                }

                const maxLength = Number.parseInt(commentInput.getAttribute('maxlength') || '100', 10);
                const used = commentInput.value.length;
                const remaining = Math.max(0, maxLength - used);
                commentCounter.textContent = `${remaining} caracteres restantes`;
            };

            if (commentInput) {
                commentInput.addEventListener('input', updateCommentCounter);
                updateCommentCounter();
            }

            stars.forEach((star) => {
                star.addEventListener('click', () => {
                    const value = Number.parseInt(star.dataset.ratingStar || '0', 10);
                    ratingInput.value = String(value);
                    paintStars(value);

                    if (ratingError) {
                        ratingError.classList.add('hidden');
                    }
                });
            });

            form.addEventListener('submit', (event) => {
                const selectedRating = Number.parseInt(ratingInput.value || '0', 10);
                if (selectedRating >= 1 && selectedRating <= 5) {
                    return;
                }

                event.preventDefault();
                if (ratingError) {
                    ratingError.classList.remove('hidden');
                }
            });
        })();
    </script>
@endif