<x-side-bar smart navigate>
    <x-slot:brand>
        <x-sidebar-brand />
    </x-slot:brand>

    <x-side-bar.item
        :text="__('Back to app')"
        icon="arrow-left"
        :route="route('dashboard')"
        match="dashboard"
    />

    <x-side-bar.separator :text="__('Account')" />

    <x-side-bar.item
        :text="__('Profile')"
        icon="document-text"
        :route="route('profile.edit')"
        match="profile.edit"
    />
    <x-side-bar.item
        :text="__('Security')"
        icon="eye"
        :route="route('security.edit')"
        match="security.edit"
    />
    <x-side-bar.item
        :text="__('Appearance')"
        icon="swatch"
        :route="route('appearance.edit')"
        match="appearance.edit"
    />

    <x-side-bar.separator :text="__('Workspace')" />

    <x-slot:footer>
        @include('layouts.partials.sidebar-footer', ['showSettings' => false])
    </x-slot:footer>
</x-side-bar>
