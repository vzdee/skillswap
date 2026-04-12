@php
    $embeddedProfile = $embeddedProfile ?? false;
@endphp

@if ($embeddedProfile)
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
        @include('profile.partials.profile-content', ['embeddedProfile' => true])
    </body>
    </html>
@else
    <x-app-layout>
      <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('View Profile') }}
            </h2>
        </x-slot>

        @include('profile.partials.profile-content', ['embeddedProfile' => false])
    </x-app-layout>
@endif
