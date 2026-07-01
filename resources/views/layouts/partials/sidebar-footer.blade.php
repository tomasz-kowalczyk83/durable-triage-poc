@props(['showSettings' => false])

<ul role="list" class="flex flex-col gap-y-1 px-1">
    @if ($showSettings)
        <x-side-bar.item
            :text="__('Settings')"
            icon="cog-6-tooth"
            :route="route('profile.edit')"
            match="profile.edit,security.edit,appearance.edit"
        />
    @endif

    <li class="mt-2">
        <x-sidebar-user-menu />
    </li>
</ul>
