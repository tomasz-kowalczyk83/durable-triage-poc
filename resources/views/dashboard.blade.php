<x-layouts::app :title="__('Dashboard')">
    <div class="space-y-8">
        <x-page-header
            :title="__('Dashboard')"
            :subtitle="__('Incident triage administration.')"
        />

        <div class="grid gap-4 md:grid-cols-2">
            <x-card :title="__('Incidents')">
                <p class="text-3xl font-bold text-zinc-900 dark:text-white">
                    {{ \App\Models\Incident::query()->count() }}
                </p>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                    {{ __('Total incidents in the system.') }}
                </p>

                <x-slot:footer>
                    <x-button :href="route('incidents.index')" wire:navigate>
                        {{ __('View incidents') }}
                    </x-button>
                </x-slot:footer>
            </x-card>

            <x-card :title="__('Quick actions')">
                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                    {{ __('Create a new incident to start durable triage.') }}
                </p>

                <x-slot:footer>
                    <x-button :href="route('incidents.create')" icon="plus" wire:navigate>
                        {{ __('New incident') }}
                    </x-button>
                </x-slot:footer>
            </x-card>
        </div>
    </div>
</x-layouts::app>
