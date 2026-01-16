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
    Route::get('/confirmar-contraseña', [ConfirmPasswordController::class, 'show'])->name('password.confirm.show');
    Route::post('/confirmar-contraseña', [ConfirmPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.confirm.show.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $user = $request->user()->load('pareja.usuarios');

        return Inertia::render('dashboard', [
            'pareja' => $user->pareja,
        ]);
    })->name('dashboard');
});

// Módulo de Parejas (mango/admin)
Route::middleware(['auth', 'permission:parejas,view'])->group(function () {
    Route::resource('parejas', \App\Http\Controllers\ParejaController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);

    Route::post('parejas/{pareja}/retirar', [\App\Http\Controllers\ParejaController::class, 'retirar'])
        ->middleware('permission:parejas,update')
        ->name('parejas.retirar');

    Route::post('parejas/{pareja}/reactivar', [\App\Http\Controllers\ParejaController::class, 'reactivar'])
        ->middleware('permission:parejas,reactivar')
        ->name('parejas.reactivar');
});

require __DIR__.'/settings.php';
