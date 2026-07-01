<?php

use App\Models\Incident;
use App\Services\IncidentTriageLauncher;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new
#[Title('New incident')]
class extends Component {
    use Interactions;

    public string $raw_payload = '';

    public function save(IncidentTriageLauncher $launcher): void
    {
        $validated = $this->validate([
            'raw_payload' => ['required', 'string', 'max:10000'],
        ]);

        $incident = Incident::query()->create([
            'raw_payload' => $validated['raw_payload'],
            'severity' => null,
            'correlation_ids' => [],
            'runbook_refs' => [],
            'suggestion' => null,
            'status' => 'pending',
        ]);

        $launcher->start($incident->id);

        $this->toast()
            ->success(__('Incident created'), __('Durable triage workflow started.'))
            ->flash()
            ->send();

        $this->redirect(route('incidents.show', $incident), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('New incident') }}</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Describe the incident. Durable triage will start automatically after creation.') }}
        </p>
    </div>

    <x-card>
        <form wire:submit="save" class="space-y-6">
            <x-textarea
                wire:model="raw_payload"
                :label="__('Incident payload')"
                :hint="__('Raw alert text or incident description.')"
                rows="6"
                required
            />

            <div class="flex items-center gap-3">
                <x-button type="submit" wire:loading.attr="disabled" loading="save">
                    {{ __('Create incident') }}
                </x-button>

                <x-button :href="route('incidents.index')" color="secondary" outline wire:navigate>
                    {{ __('Cancel') }}
                </x-button>
            </div>
        </form>
    </x-card>
</div>
