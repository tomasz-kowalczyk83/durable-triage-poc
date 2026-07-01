<?php

use App\Models\Incident;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Incident')]
class extends Component {
    public Incident $incident;
}; ?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ __('Incident #:id', ['id' => $incident->id]) }}
            </h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                {{ __('Created :time', ['time' => $incident->created_at?->format('Y-m-d H:i:s')]) }}
            </p>
        </div>

        <x-button :href="route('incidents.index')" icon="arrow-left" color="secondary" outline wire:navigate>
            {{ __('Back to incidents') }}
        </x-button>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card :title="__('Status')" class="lg:col-span-1">
            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                    <dd class="mt-1">
                        <x-badge :text="$incident->status" color="zinc" light />
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Severity') }}</dt>
                    <dd class="mt-1">
                        @if ($incident->severity)
                            <x-badge :text="$incident->severity" color="sky" light />
                        @else
                            <span class="text-zinc-700 dark:text-zinc-200">—</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Updated') }}</dt>
                    <dd class="mt-1 text-zinc-900 dark:text-white">
                        {{ $incident->updated_at?->format('Y-m-d H:i:s') }}
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card :title="__('Payload')" class="lg:col-span-2">
            <p class="whitespace-pre-wrap text-sm text-zinc-900 dark:text-zinc-100">{{ $incident->raw_payload }}</p>
        </x-card>
    </div>

    @if ($incident->suggestion)
        <x-card :title="__('Suggestion')">
            <p class="whitespace-pre-wrap text-sm text-zinc-900 dark:text-zinc-100">{{ $incident->suggestion }}</p>
        </x-card>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card :title="__('Correlation IDs')">
            @if ($incident->correlation_ids === [])
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('None yet.') }}</p>
            @else
                <ul class="list-inside list-disc space-y-1 text-sm text-zinc-900 dark:text-zinc-100">
                    @foreach ($incident->correlation_ids as $correlationId)
                        <li>{{ is_array($correlationId) ? json_encode($correlationId) : $correlationId }}</li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card :title="__('Runbook references')">
            @if ($incident->runbook_refs === [])
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('None yet.') }}</p>
            @else
                <ul class="list-inside list-disc space-y-1 text-sm text-zinc-900 dark:text-zinc-100">
                    @foreach ($incident->runbook_refs as $runbookRef)
                        <li>{{ is_array($runbookRef) ? json_encode($runbookRef) : $runbookRef }}</li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</div>
