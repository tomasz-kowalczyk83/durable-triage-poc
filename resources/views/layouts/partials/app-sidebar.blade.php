<x-side-bar smart navigate>
    <x-slot:brand>
        <x-sidebar-brand />
    </x-slot:brand>

    <x-side-bar.separator :text="__('Platform')" />

    <x-side-bar.item
        :text="__('Dashboard')"
        icon="home"
        :route="route('dashboard')"
        match="dashboard"
    />
    <x-side-bar.item
        :text="__('Incidents')"
        icon="exclamation-triangle"
        :route="route('incidents.index')"
        match="incidents.*"
        :badge="(string) \App\Models\Incident::query()->count()"
        badge-color="zinc"
    />

    <x-slot:footer>
        @include('layouts.partials.sidebar-footer', ['showSettings' => true])
    </x-slot:footer>
</x-side-bar>
