<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/configuracion/perfil');
    Route::redirect('configuracion', '/configuracion/perfil');

    Route::get('configuracion/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('configuracion/perfil', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('configuracion/perfil', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('configuracion/contraseña', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('configuracion/contraseña', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('configuracion/apariencia', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');

    Route::get('configuracion/dos-factores', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});
