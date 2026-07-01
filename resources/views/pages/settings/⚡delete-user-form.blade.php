<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Delete account') }}</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <x-button color="red" data-test="delete-user-button" wire:click="$dispatch('open-delete-user-modal')">
        {{ __('Delete account') }}
    </x-button>

    <livewire:pages::settings.delete-user-modal />
</section>
