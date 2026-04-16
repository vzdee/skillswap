<div
    id="skills-onboarding-modal"
    class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-gray-900/55 p-3 sm:items-center sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="skills-onboarding-title"
>
    <div class="max-h-[calc(100vh-1.5rem)] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white p-4 shadow-2xl sm:max-h-[min(90vh,52rem)] sm:p-8 lg:p-10">
        <h3 id="skills-onboarding-title" class="text-center text-lg font-bold text-gray-900 sm:text-2xl">REGISTRO DE HABILIDADES</h3>
        <p class="mt-2 text-center text-sm text-gray-500">Esta informacion se puede modificar en cualquier momento en configuracion.</p>

        <div class="mx-auto mt-5 max-w-2xl space-y-6 sm:mt-8 sm:space-y-8" id="skills-onboarding-root">
            <div>
                <h4 class="text-lg font-semibold text-gray-900 sm:text-2xl">Habilidades que puedo enseñar:</h4>
                <p class="mt-1 text-sm text-gray-600">Busca y selecciona 3 habilidades que puedes enseñar a otros.</p>
                <div class="relative mt-3">
                    <input id="teach-onboarding-input" type="text" class="block w-full rounded-full border-gray-300 bg-gray-200 px-4 py-2.5 text-sm sm:px-5 sm:py-3 sm:text-base" placeholder="E.J: Python, UI Design, Etc.">
                    <div id="teach-onboarding-options" class="mt-2 hidden max-h-56 w-full overflow-y-auto rounded-2xl border border-gray-200 bg-white p-1 shadow-lg"></div>
                </div>
                <div id="teach-onboarding-list" class="mt-3 flex flex-wrap gap-2"></div>
            </div>

            <div>
                <h4 class="text-lg font-semibold text-gray-900 sm:text-2xl">Habilidades que quiero aprender:</h4>
                <p class="mt-1 text-sm text-gray-600">Busca y selecciona 2 intereses que quieres aprender de otros.</p>
                <div class="relative mt-3">
                    <input id="learn-onboarding-input" type="text" class="block w-full rounded-full border-gray-300 bg-gray-200 px-4 py-2.5 text-sm sm:px-5 sm:py-3 sm:text-base" placeholder="E.J: Marketing, Desarrollo Web, Idioma Ingles, Etc.">
                    <div id="learn-onboarding-options" class="mt-2 hidden max-h-56 w-full overflow-y-auto rounded-2xl border border-gray-200 bg-white p-1 shadow-lg"></div>
                </div>
                <div id="learn-onboarding-list" class="mt-3 flex flex-wrap gap-2"></div>
            </div>

            <p id="skills-onboarding-error" class="hidden text-center text-sm font-semibold text-red-600"></p>

            <div class="pt-2 text-center">
                <button id="skills-onboarding-save" type="button" class="w-full rounded-full bg-sky-500 px-6 py-2.5 text-base font-bold text-white transition hover:bg-sky-600 sm:w-auto sm:px-10 sm:py-3 sm:text-lg">GUARDAR</button>
            </div>
        </div>
    </div>
</div>
