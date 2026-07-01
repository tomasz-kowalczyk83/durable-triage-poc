<x-layouts::shell :title="$title ?? null">
    <x-slot:menu>
        @include('layouts.partials.app-sidebar')
    </x-slot:menu>

    {{ $slot }}
</x-layouts::shell>
