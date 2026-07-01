<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <x-input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <x-input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <x-password
                name="password"
                :label="__('Password')"
                :rules="false"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
            />

            <x-password
                name="password_confirmation"
                :label="__('Confirm password')"
                :rules="false"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
            />

            <div class="flex items-center justify-end">
                <x-button type="submit" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </x-button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <x-link :href="route('login')" navigate>{{ __('Log in') }}</x-link>
        </div>
    </div>
</x-layouts::auth>
