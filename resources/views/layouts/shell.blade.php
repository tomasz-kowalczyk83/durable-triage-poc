@props(['title' => null])

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="tallstackui_darkTheme()"
    x-bind:class="{ 'dark': darkTheme }"
>
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-800 dark:text-zinc-100">
        <x-dialog />
        <x-toast />

        <x-layout>
            <x-slot:header>
                <x-layout.header>
                    <x-slot:left>
                        <span class="text-sm font-medium text-zinc-700 md:sr-only dark:text-zinc-200">
                            {{ filled($title) ? $title : config('app.name', 'Laravel') }}
                        </span>
                    </x-slot:left>
                </x-layout.header>
            </x-slot:header>

            <x-slot:menu>
                {{ $menu }}
            </x-slot:menu>

            <div class="p-6 md:p-8 lg:p-10 app-prose">
                {{ $slot }}
            </div>
        </x-layout>

        @livewireScripts
    </body>
</html>
