<?php

use App\Http\Controllers\Auth\ConfirmPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Fortify maneja /login automáticamente, redirigir /iniciar-sesion a /login
Route::get('/iniciar-sesion', function () {
    return redirect('/login');
})->name('iniciar-sesion');

// Fortify maneja /register automáticamente

Route::get('/olvide-contraseña', function (Request $request) {
    return Inertia::render('auth/forgot-password', [
        'status' => $request->session()->get('status'),
    ]);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/confirmar-contraseña', [ConfirmPasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirmar-contraseña', [ConfirmPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.confirm.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $user = $request->user()->load('pareja.usuarios');

        return Inertia::render('dashboard', [
            'pareja' => $user->pareja,
        ]);
    })->name('dashboard');
});

require __DIR__.'/settings.php';
