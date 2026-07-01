<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public bool $show = false;

    public string $password = '';

    #[On('open-delete-user-modal')]
    public function open(): void
    {
        $this->show = true;
    }

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }

    public function updatedShow(bool $value): void
    {
        if (! $value) {
            $this->reset('password');
            $this->resetErrorBag();
        }
    }
}; ?>

<x-modal wire="show" size="lg" :title="__('Are you sure you want to delete your account?')">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
        </p>

        <x-password wire:model="password" :label="__('Password')" :rules="false" />

        <div class="flex justify-end gap-2">
            <x-button type="button" color="secondary" outline wire:click="$set('show', false)">
                {{ __('Cancel') }}
            </x-button>

            <x-button color="red" type="submit" data-test="confirm-delete-user-button">
                {{ __('Delete account') }}
            </x-button>
        </div>
    </form>
</x-modal>
