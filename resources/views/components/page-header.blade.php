@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'border-b border-zinc-200 pb-6 dark:border-white/10']) }}>
    <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $title }}</h1>
    @if (filled($subtitle))
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $subtitle }}</p>
    @endif
</div>
