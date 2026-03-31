<div
    id="availability-onboarding-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/55 px-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="availability-onboarding-title"
>
    <div class="w-full max-w-5xl rounded-3xl bg-white p-8 shadow-2xl sm:p-10">
        <h3 id="availability-onboarding-title" class="text-center text-2xl font-bold text-gray-900">REGISTRO DE LA DISPONIBILIDAD DE HORARIO</h3>
        <p class="mt-2 text-center text-gray-700">Indica los dias y horas disponibles para enseñar o aprender.</p>
        <p class="mt-1 text-center text-sm text-gray-500">Esta informacion se puede modificar en cualquier momento en configuracion.</p>

        <div id="availability-grid" class="mx-auto mt-7 grid max-w-3xl grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"></div>

        <p id="availability-onboarding-error" class="mt-4 hidden text-center text-sm font-semibold text-red-600"></p>

        <div class="mt-8 text-center">
            <button id="availability-onboarding-save" type="button" class="rounded-full bg-sky-500 px-10 py-3 text-lg font-bold text-white transition hover:bg-sky-600">GUARDAR</button>
        </div>
    </div>
</div>
