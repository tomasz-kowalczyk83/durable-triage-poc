<?php

use App\Models\Incident;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Title('Incidents')]
class extends Component {
    use WithPagination;

    public ?int $quantity = 10;

    public ?string $search = null;

    public array $sort = [
        'column' => 'created_at',
        'direction' => 'desc',
    ];

    #[Computed]
    public function headers(): array
    {
        return [
            ['index' => 'id', 'label' => __('ID')],
            ['index' => 'payload_excerpt', 'label' => __('Payload'), 'sortable' => false],
            ['index' => 'severity', 'label' => __('Severity'), 'sortable' => false],
            ['index' => 'status', 'label' => __('Status')],
            ['index' => 'created_at', 'label' => __('Created')],
            ['index' => 'action', 'label' => '', 'sortable' => false],
        ];
    }

    #[Computed]
    public function rows()
    {
        return Incident::query()
            ->when($this->search, function (Builder $query): void {
                $query->where('raw_payload', 'like', "%{$this->search}%")
                    ->orWhere('status', 'like', "%{$this->search}%")
                    ->orWhere('severity', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sort['column'], $this->sort['direction'])
            ->paginate($this->quantity)
            ->withQueryString();
    }
}; ?>

<div class="space-y-8">
    <div class="flex flex-col gap-4 border-b border-zinc-200 pb-6 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('Incidents') }}</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                {{ __('All incidents submitted for triage.') }}
            </p>
        </div>

        <x-button :href="route('incidents.create')" icon="plus" wire:navigate>
            {{ __('New incident') }}
        </x-button>
    </div>

    <x-table
        :headers="$this->headers"
        :rows="$this->rows"
        :sort="$sort"
        filter
        paginate
        loading
        id="incidents"
        link="/incidents/{id}"
        :empty="__('No incidents found.')"
    >
        @interact('column_payload_excerpt', $row)
            <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $row->payload_excerpt }}</span>
        @endinteract

        @interact('column_severity', $row)
            @if ($row->severity)
                <x-badge :text="$row->severity" color="sky" light />
            @else
                <span class="text-sm text-zinc-500 dark:text-zinc-400">—</span>
            @endif
        @endinteract

        @interact('column_status', $row)
            <x-badge :text="$row->status" color="zinc" light />
        @endinteract

        @interact('column_created_at', $row)
            <span class="text-sm text-zinc-700 dark:text-zinc-200">
                {{ $row->created_at?->format('Y-m-d H:i') }}
            </span>
        @endinteract

        @interact('column_action', $row)
            <x-button.circle
                icon="eye"
                sm
                color="secondary"
                :href="route('incidents.show', $row)"
                wire:navigate
            />
        @endinteract
    </x-table>
</div>
