<div class="rounded-lg border border-zinc-200 bg-white p-1 dark:border-white/10 dark:bg-transparent">
    <x-dropdown class="sidebar-user-menu w-full" position="top-start">
        <x-slot:action>
            <button
                type="button"
                class="sidebar-user-trigger flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-zinc-900 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10 dark:hover:text-white"
                x-on:click="show = !show; $refs.dropdown.dispatchEvent(new CustomEvent('open', {detail: {status: show}}))"
                aria-controls="dropdown-menu"
            >
                <x-avatar :text="auth()->user()->name" sm />
                <span class="min-w-0 flex-1 truncate text-sm font-medium">
                    {{ auth()->user()->name }}
                </span>
                <x-icon name="chevron-up-down" class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500" />
            </button>
        </x-slot:action>

        <x-dropdown.items
            :text="__('Log out')"
            icon="arrow-right-start-on-rectangle"
            onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();"
        />
    </x-dropdown>
</div>

<form id="sidebar-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
    @csrf
</form>
