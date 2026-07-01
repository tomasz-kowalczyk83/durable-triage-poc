<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('incidents', 'pages::incidents.index')->name('incidents.index');
    Route::livewire('incidents/create', 'pages::incidents.create')->name('incidents.create');
    Route::livewire('incidents/{incident}', 'pages::incidents.show')->name('incidents.show');

    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

require __DIR__.'/settings.php';
