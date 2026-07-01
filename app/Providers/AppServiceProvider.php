<?php

namespace App\Providers;

use App\Contracts\LlmClient;
use App\Support\FakeLlmClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use TallStackUi\TallStackUi;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LlmClient::class, FakeLlmClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureTallStackUi();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureTallStackUi(): void
    {
        $tallStackUi = app(TallStackUi::class);

        $tallStackUi->customize()
            ->layout('index')
            ->block('wrapper.second.expanded')
            ->remove(['transition-[padding]', 'duration-300']);

        $tallStackUi->customize()
            ->layout('index')
            ->block('wrapper.second.collapsed')
            ->remove(['transition-[padding]', 'duration-300']);

        $tallStackUi->customize()
            ->layout('index')
            ->block('main', 'mx-auto max-w-full');

        $tallStackUi->customize()
            ->layout('index')
            ->block('wrapper.first', 'min-h-full dark:bg-zinc-800');

        $tallStackUi->customize()
            ->layout('header')
            ->block([
                'wrapper' => 'sticky top-0 z-40 flex h-14 shrink-0 items-center gap-x-4 bg-white px-4 max-md:border-b max-md:border-zinc-200 sm:gap-x-6 sm:px-6 lg:px-8 md:h-0 md:overflow-visible md:px-0 dark:bg-zinc-800 dark:shadow-none max-md:dark:border-zinc-700',
                'button.icon.size' => 'h-5 w-5 text-zinc-500 dark:text-white/80',
                'collapse.icon.size' => 'h-5 w-5 text-zinc-500 dark:text-white/80',
            ]);

        $tallStackUi->customize()
            ->sideBar('side-bar')
            ->block([
                'desktop.wrapper.second' => 'flex grow flex-col border-r border-zinc-200 bg-zinc-50 pb-4 dark:border-zinc-800 dark:bg-zinc-900',
                'desktop.footer' => 'shrink-0 overflow-hidden px-2 py-3 dark:border-transparent',
                'mobile.wrapper.fourth' => 'flex grow flex-col bg-zinc-50 pb-4 dark:bg-zinc-900',
                'mobile.footer' => 'shrink-0 px-2 py-3 dark:border-transparent',
                'mobile.backdrop' => 'fixed inset-0 bg-zinc-900/80 dark:bg-black/70',
            ]);

        $tallStackUi->customize()
            ->sideBar('separator')
            ->block([
                'simple.wrapper' => 'flex px-3 py-2',
                'simple.base' => 'text-xs font-medium leading-6 whitespace-nowrap text-zinc-500 dark:text-zinc-500',
            ]);

        $tallStackUi->customize()
            ->sideBar('item')
            ->block([
                'item.state.base' => 'group flex items-center gap-x-3 rounded-md px-2 py-2 text-sm font-medium transition-colors',
                'item.state.normal' => 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white',
                'item.state.current' => 'bg-zinc-100 text-zinc-900 dark:bg-white/10 dark:text-white dark:[&_svg]:text-white',
                'item.icon' => 'h-4 w-4 shrink-0 text-zinc-500 transition-colors dark:text-zinc-400 dark:group-hover:text-white',
            ]);

        $tallStackUi->customize()
            ->table()
            ->block([
                'wrapper' => 'overflow-hidden rounded-lg shadow ring-1 ring-zinc-200 dark:ring-white/10',
                'table.base' => 'min-w-full divide-y divide-zinc-200 dark:divide-white/10',
                'table.th' => 'px-3 py-3.5 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-200',
                'table.tbody' => 'divide-y divide-zinc-200 bg-white dark:divide-white/10 dark:bg-zinc-900',
                'table.td' => 'whitespace-nowrap px-3 py-4 text-sm text-zinc-600 dark:text-zinc-200',
                'table.thead.normal' => 'bg-zinc-50 dark:bg-zinc-900',
                'table.thead.striped' => 'bg-white dark:bg-zinc-900',
                'empty' => 'col-span-full whitespace-nowrap px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400',
                'slots.header' => 'mb-2 text-zinc-500 dark:text-zinc-400',
                'slots.footer' => 'mt-2 text-zinc-500 dark:text-zinc-400',
            ]);

        $tallStackUi->customize()
            ->form('input')
            ->block([
                'input.base' => 'w-full rounded-md border-0 bg-transparent py-1.5 ring-0 placeholder:text-zinc-400 focus:outline-hidden focus:ring-transparent sm:text-sm sm:leading-6 dark:text-zinc-100 dark:placeholder:text-zinc-500',
                'input.color.base' => 'text-zinc-700 ring-zinc-300 dark:text-zinc-100 dark:ring-white/15',
                'input.color.background' => 'bg-white dark:bg-zinc-900',
                'input.color.disabled' => 'bg-zinc-100 dark:bg-zinc-800',
                'icon.wrapper' => 'pointer-events-none absolute inset-y-0 flex items-center text-zinc-500 dark:text-zinc-400',
                'icon.color' => 'text-zinc-500 dark:text-zinc-400',
            ]);

        $tallStackUi->customize()
            ->form('label')
            ->block([
                'text' => 'mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300',
            ]);

        $tallStackUi->customize()
            ->card()
            ->block([
                'wrapper.second' => 'flex w-full flex-col overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-200 dark:shadow-none dark:ring-white/10',
                'header.wrapper.border' => 'border-b border-zinc-100 dark:border-white/10',
                'header.text.color' => 'text-zinc-900 dark:text-white',
                'body' => 'grow rounded-b-xl px-4 py-5 text-zinc-700 dark:text-zinc-200',
                'footer.wrapper' => 'rounded-lg rounded-t-none border-t border-zinc-200 p-4 text-zinc-700 dark:border-white/10 dark:text-zinc-200',
            ]);

        $tallStackUi->customize()
            ->themeSwitch()
            ->block([
                'segmented.wrapper' => 'inline-flex w-full items-center gap-1 rounded-lg bg-zinc-200/80 p-1 dark:bg-zinc-800/80',
                'segmented.button' => 'flex flex-1 cursor-pointer items-center justify-center rounded-md p-1.5 transition-colors focus:outline-hidden',
                'segmented.active' => 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white',
                'segmented.inactive' => 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-100',
                'segmented.colors.moon' => 'text-zinc-600 dark:text-zinc-200',
                'segmented.colors.sun' => 'text-zinc-600 dark:text-zinc-200',
                'segmented.colors.system' => 'text-zinc-600 dark:text-zinc-200',
            ]);
    }
}
