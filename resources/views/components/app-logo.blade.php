@props([
    'sidebar' => false,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 font-medium']) }}>
    <span class="flex aspect-square size-8 items-center justify-center rounded-md bg-gray-900 text-white dark:bg-white dark:text-gray-900">
        <x-app-logo-icon class="size-5 fill-current" />
    </span>
    <span>{{ config('app.name', 'Laravel') }}</span>
</div>
