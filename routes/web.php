<?php

use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Rutas de autenticación en español (redirecciones a las vistas de Fortify)
Route::get('/iniciar-sesion', function (Request $request) {
    return Inertia::render('auth/login', [
        'canResetPassword' => Features::enabled(Features::resetPasswords()),
        'canRegister' => Features::enabled(Features::registration()),
        'status' => $request->session()->get('status'),
    ]);
})->name('login');

// Redirigir /register a /registro/paso-uno
Route::get('/register', function () {
    return redirect('/registro/paso-uno');
});

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

// Rutas de onboarding - Paso uno (sin autenticación, es el registro inicial)
Route::get('/registro/paso-uno', [OnboardingController::class, 'pasoUno'])->name('onboarding.paso-uno');
Route::post('/registro/paso-uno', [OnboardingController::class, 'guardarPasoUno'])->name('onboarding.guardar-paso-uno');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Rutas de onboarding - Paso dos (requiere autenticación)
    Route::get('/registro/paso-dos', [OnboardingController::class, 'pasoDos'])->name('onboarding.paso-dos');
    Route::post('/registro/paso-dos', [OnboardingController::class, 'guardarPasoDos'])->name('onboarding.guardar-paso-dos');

    // Rutas de onboarding - Paso tres (requiere autenticación)
    Route::get('/registro/paso-tres', [OnboardingController::class, 'pasoTres'])->name('onboarding.paso-tres');
    Route::post('/registro/paso-tres', [OnboardingController::class, 'guardarPasoTres'])->name('onboarding.guardar-paso-tres');
    Route::get('/registro/buscar-usuarios', [OnboardingController::class, 'searchUsers'])->name('onboarding.search-users');

    // Rutas de onboarding - Paso cuatro (requiere autenticación)
    Route::get('/registro/paso-cuatro', [OnboardingController::class, 'pasoCuatro'])->name('onboarding.paso-cuatro');
    Route::post('/registro/paso-cuatro', [OnboardingController::class, 'guardarPasoCuatro'])->name('onboarding.guardar-paso-cuatro');
});

require __DIR__.'/settings.php';
