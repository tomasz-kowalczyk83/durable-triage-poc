<a
    href="{{ route('dashboard') }}"
    wire:navigate
    class="mx-2 flex w-[calc(100%-1rem)] items-center gap-3 rounded-lg px-2 py-2 text-left text-zinc-900 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10 dark:hover:text-white"
>
    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-900 ring-1 ring-zinc-900 dark:bg-zinc-950 dark:ring-white/10">
        <x-app-logo-icon class="size-4 fill-current text-white" />
    </div>
    <span class="min-w-0 flex-1 truncate text-sm font-semibold">
        {{ config('app.name', 'Laravel') }}
    </span>
    <x-icon name="chevron-up-down" class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500" />
</a>
