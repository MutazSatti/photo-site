<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| إعدادات الحساب
|--------------------------------------------------------------------------
|
| صفحات خاصة بحساب المستخدم نفسه، منفصلة عن إعدادات الموقع في لوحة التحكم.
|
*/

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware(['password.confirm'])
        ->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
