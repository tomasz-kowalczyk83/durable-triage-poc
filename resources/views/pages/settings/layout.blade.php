<div class="w-full max-w-lg">
    <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $heading ?? '' }}</h2>
        @if (filled($subheading ?? null))
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $subheading }}</p>
        @endif
    </div>

    <div class="mt-5 w-full">
        {{ $slot }}
    </div>
</div>
