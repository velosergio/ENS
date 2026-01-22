<?php

use App\Models\User;
use App\Services\CumpleanosService;

test('cumpleanos service returns cumpleanos in date range', function () {
    // Crear pareja activa con usuario
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create([
        'estado' => 'activo',
    ]);
    $usuario = $pareja->usuarios()->first();
    $usuario->update([
        'fecha_nacimiento' => now()->subYears(30),
        'nombres' => 'Juan',
        'apellidos' => 'Pérez',
    ]);

    $service = new CumpleanosService;
    $fechaInicio = now()->format('Y-m-d');
    $fechaFin = now()->addDays(7)->format('Y-m-d');

    // Obtener día y mes del cumpleaños
    $diaNacimiento = (int) $usuario->fecha_nacimiento->format('d');
    $mesNacimiento = (int) $usuario->fecha_nacimiento->format('m');
    $diaHoy = (int) now()->format('d');
    $mesHoy = (int) now()->format('m');

    // Solo retornar cumpleaños si el cumpleaños está en el rango
    if ($mesNacimiento === $mesHoy && $diaNacimiento >= $diaHoy && $diaNacimiento <= $diaHoy + 7) {
        $cumpleanos = $service->obtenerCumpleanosEnRango($fechaInicio, $fechaFin);

        expect($cumpleanos)->not->toBeEmpty();
        $cumpleano = collect($cumpleanos)->firstWhere('extendedProps.usuario.id', $usuario->id);
        expect($cumpleano)->not->toBeNull();
        expect($cumpleano['title'])->toContain('Cumpleaños de Juan Pérez');
        expect($cumpleano['allDay'])->toBeTrue();
    }
});

test('cumpleanos service handles february 29 in non-leap years', function () {
    // Crear pareja activa con usuario nacido el 29 de febrero
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create([
        'estado' => 'activo',
    ]);
    $usuario = $pareja->usuarios()->first();
    $usuario->update([
        'fecha_nacimiento' => now()->setYear(2000)->setMonth(2)->setDay(29)->startOfDay(),
        'nombres' => 'Leap',
        'apellidos' => 'Year',
    ]);

    $service = new CumpleanosService;

    // Probar con un año no bisiesto (2025)
    $fechaInicio = now()->setYear(2025)->setMonth(2)->setDay(1)->format('Y-m-d');
    $fechaFin = now()->setYear(2025)->setMonth(2)->setDay(28)->format('Y-m-d');

    $cumpleanos = $service->obtenerCumpleanosEnRango($fechaInicio, $fechaFin);

    // En años no bisiestos, debería aparecer el 28 de febrero
    $cumpleanoFeb28 = collect($cumpleanos)->firstWhere('extendedProps.usuario.id', $usuario->id);

    expect($cumpleanoFeb28)->not->toBeNull();
    $fechaCumpleanos = \Carbon\Carbon::parse($cumpleanoFeb28['start']);
    expect($fechaCumpleanos->format('m-d'))->toBe('02-28');
});

test('cumpleanos service handles february 29 in leap years', function () {
    // Crear pareja activa con usuario nacido el 29 de febrero
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create([
        'estado' => 'activo',
    ]);
    $usuario = $pareja->usuarios()->first();
    $usuario->update([
        'fecha_nacimiento' => now()->setYear(2000)->setMonth(2)->setDay(29)->startOfDay(),
        'nombres' => 'Leap',
        'apellidos' => 'Year',
    ]);

    $service = new CumpleanosService;

    // Probar con un año bisiesto (2024)
    $fechaInicio = now()->setYear(2024)->setMonth(2)->setDay(1)->format('Y-m-d');
    $fechaFin = now()->setYear(2024)->setMonth(3)->setDay(1)->format('Y-m-d');

    $cumpleanos = $service->obtenerCumpleanosEnRango($fechaInicio, $fechaFin);

    // En años bisiestos, debería aparecer el 29 de febrero
    $cumpleanoFeb29 = collect($cumpleanos)->firstWhere('extendedProps.usuario.id', $usuario->id);

    expect($cumpleanoFeb29)->not->toBeNull();
    $fechaCumpleanos = \Carbon\Carbon::parse($cumpleanoFeb29['start']);
    expect($fechaCumpleanos->format('m-d'))->toBe('02-29');
});

test('cumpleanos service includes usuario information', function () {
    // Crear pareja activa con usuario
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create([
        'estado' => 'activo',
    ]);
    $usuario = $pareja->usuarios()->first();
    $usuario->update([
        'fecha_nacimiento' => now()->subYears(25),
        'nombres' => 'María',
        'apellidos' => 'González',
        'email' => 'maria@example.com',
    ]);

    $service = new CumpleanosService;
    $fechaInicio = now()->format('Y-m-d');
    $fechaFin = now()->addMonths(2)->format('Y-m-d');

    $cumpleanos = $service->obtenerCumpleanosEnRango($fechaInicio, $fechaFin);
    $cumpleano = collect($cumpleanos)->firstWhere('extendedProps.usuario.id', $usuario->id);

    expect($cumpleano)->not->toBeNull();
    expect($cumpleano['extendedProps'])->toHaveKey('usuario');
    expect($cumpleano['extendedProps']['usuario'])->toHaveKeys(['id', 'nombres', 'apellidos', 'email']);
    expect($cumpleano['extendedProps']['usuario']['id'])->toBe($usuario->id);
    expect($cumpleano['extendedProps']['usuario']['nombres'])->toBe('María');
    expect($cumpleano['extendedProps']['usuario']['apellidos'])->toBe('González');
});

test('cumpleanos service calculates edad correctly', function () {
    $añoNacimiento = 1990;
    // Crear pareja activa con usuario
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create([
        'estado' => 'activo',
    ]);
    $usuario = $pareja->usuarios()->first();
    $usuario->update([
        'fecha_nacimiento' => now()->setYear($añoNacimiento)->setMonth(6)->setDay(15)->startOfDay(),
    ]);

    $service = new CumpleanosService;
    $añoActual = (int) now()->format('Y');
    $fechaInicio = now()->setYear($añoActual)->setMonth(6)->setDay(1)->format('Y-m-d');
    $fechaFin = now()->setYear($añoActual)->setMonth(6)->setDay(30)->format('Y-m-d');

    $cumpleanos = $service->obtenerCumpleanosEnRango($fechaInicio, $fechaFin);
    $cumpleano = collect($cumpleanos)->firstWhere('extendedProps.usuario.id', $usuario->id);

    expect($cumpleano)->not->toBeNull();
    $edadEsperada = $añoActual - $añoNacimiento;
    expect($cumpleano['extendedProps']['edad'])->toBe($edadEsperada);
});

test('cumpleanos service does not return cumpleanos outside date range', function () {
    $usuario = User::factory()->create([
        'fecha_nacimiento' => now()->setMonth(12)->setDay(25)->startOfDay(), // 25 de diciembre
    ]);

    $service = new CumpleanosService;
    $fechaInicio = now()->setMonth(1)->format('Y-m-d'); // Enero
    $fechaFin = now()->setMonth(2)->format('Y-m-d'); // Febrero

    $cumpleanos = $service->obtenerCumpleanosEnRango($fechaInicio, $fechaFin);

    // El cumpleaños está en diciembre, no debería aparecer en enero-febrero
    $cumpleano = collect($cumpleanos)->firstWhere('extendedProps.usuario.id', $usuario->id);
    expect($cumpleano)->toBeNull();
});

test('cumpleanos service uses configuration color and icon', function () {
    $configuracion = [
        'color' => '#ff00ff',
        'icono' => 'Cake',
    ];

    // Crear pareja activa con usuario
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create([
        'estado' => 'activo',
    ]);
    $usuario = $pareja->usuarios()->first();
    $usuario->update([
        'fecha_nacimiento' => now()->subYears(30),
    ]);

    $service = new CumpleanosService;
    $fechaInicio = now()->format('Y-m-d');
    $fechaFin = now()->addMonths(2)->format('Y-m-d');

    $cumpleanos = $service->obtenerCumpleanosEnRango($fechaInicio, $fechaFin, $configuracion);
    $cumpleano = collect($cumpleanos)->firstWhere('extendedProps.usuario.id', $usuario->id);

    expect($cumpleano)->not->toBeNull();
    expect($cumpleano['backgroundColor'])->toBe('#ff00ff');
    expect($cumpleano['extendedProps']['icono'])->toBe('Cake');
});
