<div
    id="availability-onboarding-modal"
    class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-gray-900/55 p-3 sm:items-center sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="availability-onboarding-title"
>
    <div class="max-h-[calc(100vh-1.5rem)] w-full max-w-5xl overflow-y-auto rounded-3xl bg-white p-4 shadow-2xl sm:max-h-[min(92vh,56rem)] sm:p-8 lg:p-10">
        <h3 id="availability-onboarding-title" class="text-center text-base font-bold leading-tight text-gray-900 sm:text-2xl">REGISTRO DE LA DISPONIBILIDAD DE HORARIO</h3>
        <p class="mt-2 text-center text-sm text-gray-700 sm:text-base">Indica los dias y horas disponibles para enseñar o aprender.</p>
        <p class="mt-1 text-center text-sm text-gray-500">Esta informacion se puede modificar en cualquier momento en configuracion.</p>

        <div id="availability-grid" class="mx-auto mt-5 grid max-w-4xl grid-cols-1 gap-3 sm:mt-7 sm:grid-cols-2 lg:grid-cols-5"></div>

        <p id="availability-onboarding-error" class="mt-4 hidden text-center text-sm font-semibold text-red-600"></p>

        <div class="mt-6 text-center sm:mt-8">
            <button id="availability-onboarding-save" type="button" class="w-full rounded-full bg-sky-500 px-6 py-2.5 text-base font-bold text-white transition hover:bg-sky-600 sm:w-auto sm:px-10 sm:py-3 sm:text-lg">GUARDAR</button>
        </div>
    </div>
</div>
