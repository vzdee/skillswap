<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto flex w-full max-w-md flex-col items-center sm:max-w-xl">
                <a href="/login">
                    <x-application-logo class="w-full fill-current text-gray-500" />
                </a>
            </div>

            <div class="mx-auto mt-6 w-full max-w-md overflow-hidden rounded-2xl bg-white px-5 py-6 shadow-md sm:mt-8 sm:max-w-xl sm:px-8 sm:py-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
