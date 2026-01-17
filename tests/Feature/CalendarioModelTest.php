<?php

use App\Models\Equipo;
use App\Models\EventoCalendario;
use App\Models\User;

test('evento calendario has creadoPor relationship', function () {
    $user = User::factory()->create();
    $evento = EventoCalendario::factory()->create(['creado_por' => $user->id]);

    expect($evento->creadoPor)->not->toBeNull();
    expect($evento->creadoPor->id)->toBe($user->id);
});

test('evento calendario has equipo relationship', function () {
    $equipo = Equipo::factory()->create();
    $evento = EventoCalendario::factory()->deEquipo($equipo->id)->create();

    expect($evento->equipo)->not->toBeNull();
    expect($evento->equipo->id)->toBe($equipo->id);
});

test('evento calendario scope porEquipo filters correctly', function () {
    $equipo1 = Equipo::factory()->create();
    $equipo2 = Equipo::factory()->create();

    EventoCalendario::factory()->deEquipo($equipo1->id)->count(3)->create();
    EventoCalendario::factory()->deEquipo($equipo2->id)->count(2)->create();

    $eventosEquipo1 = EventoCalendario::porEquipo($equipo1->id)->get();

    expect($eventosEquipo1)->toHaveCount(3);
    $eventosEquipo1->each(fn ($evento) => expect($evento->equipo_id)->toBe($equipo1->id));
});

test('evento calendario scope globales filters correctly', function () {
    EventoCalendario::factory()->global()->count(3)->create();
    $equipo = Equipo::factory()->create();
    EventoCalendario::factory()->deEquipo($equipo->id)->count(2)->create();

    $eventosGlobales = EventoCalendario::globales()->get();

    expect($eventosGlobales)->toHaveCount(3);
    $eventosGlobales->each(fn ($evento) => expect($evento->alcance)->toBe('global'));
});

test('evento calendario scope porRangoFechas filters correctly', function () {
    $fechaInicio = now()->addDays(5);
    $fechaFin = now()->addDays(10);

    // Evento que empieza y termina dentro del rango
    $evento1 = EventoCalendario::factory()->create([
        'fecha_inicio' => $fechaInicio->copy()->addDay(),
        'fecha_fin' => $fechaFin->copy()->subDay(),
    ]);

    // Evento que empieza antes y termina dentro del rango
    $evento2 = EventoCalendario::factory()->create([
        'fecha_inicio' => $fechaInicio->copy()->subDay(),
        'fecha_fin' => $fechaInicio->copy()->addDay(),
    ]);

    // Evento que empieza dentro y termina después del rango
    $evento3 = EventoCalendario::factory()->create([
        'fecha_inicio' => $fechaFin->copy()->subDay(),
        'fecha_fin' => $fechaFin->copy()->addDay(),
    ]);

    // Evento que engloba todo el rango
    $evento4 = EventoCalendario::factory()->create([
        'fecha_inicio' => $fechaInicio->copy()->subDay(),
        'fecha_fin' => $fechaFin->copy()->addDay(),
    ]);

    // Evento fuera del rango
    EventoCalendario::factory()->create([
        'fecha_inicio' => $fechaFin->copy()->addDays(5),
        'fecha_fin' => $fechaFin->copy()->addDays(6),
    ]);

    $eventos = EventoCalendario::porRangoFechas(
        $fechaInicio->format('Y-m-d'),
        $fechaFin->format('Y-m-d')
    )->get();

    expect($eventos)->toHaveCount(4);
    expect($eventos->pluck('id')->toArray())->toContain($evento1->id)
        ->toContain($evento2->id)
        ->toContain($evento3->id)
        ->toContain($evento4->id);
});

test('evento calendario scope porTipo filters correctly', function () {
    EventoCalendario::factory()->general()->count(3)->create();
    EventoCalendario::factory()->formacion()->count(2)->create();
    EventoCalendario::factory()->retiroEspiritual()->count(1)->create();

    $eventosGenerales = EventoCalendario::porTipo('general')->get();

    expect($eventosGenerales)->toHaveCount(3);
    $eventosGenerales->each(fn ($evento) => expect($evento->tipo)->toBe('general'));
});

test('evento calendario esGlobal method works correctly', function () {
    $equipo = Equipo::factory()->create();
    $eventoGlobal = EventoCalendario::factory()->global()->create();
    $eventoEquipo = EventoCalendario::factory()->deEquipo($equipo->id)->create();

    expect($eventoGlobal->esGlobal())->toBeTrue();
    expect($eventoEquipo->esGlobal())->toBeFalse();
});

test('evento calendario perteneceAEquipo method works correctly', function () {
    $equipo1 = Equipo::factory()->create();
    $equipo2 = Equipo::factory()->create();
    $evento = EventoCalendario::factory()->deEquipo($equipo1->id)->create();

    expect($evento->perteneceAEquipo($equipo1->id))->toBeTrue();
    expect($evento->perteneceAEquipo($equipo2->id))->toBeFalse();
});

test('evento calendario estaEnRango method works correctly', function () {
    $evento = EventoCalendario::factory()->create([
        'fecha_inicio' => now()->addDays(5),
        'fecha_fin' => now()->addDays(10),
    ]);

    // Rango que intersecta con el evento
    expect($evento->estaEnRango(
        now()->addDays(3)->format('Y-m-d'),
        now()->addDays(7)->format('Y-m-d')
    ))->toBeTrue();

    // Rango que no intersecta (antes)
    expect($evento->estaEnRango(
        now()->addDays(1)->format('Y-m-d'),
        now()->addDays(3)->format('Y-m-d')
    ))->toBeFalse();

    // Rango que no intersecta (después)
    expect($evento->estaEnRango(
        now()->addDays(15)->format('Y-m-d'),
        now()->addDays(20)->format('Y-m-d')
    ))->toBeFalse();
});

test('evento calendario toFullCalendarFormat returns correct format', function () {
    $evento = EventoCalendario::factory()
        ->todoElDia()
        ->create([
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $formato = $evento->toFullCalendarFormat();

    expect($formato)->toHaveKeys(['id', 'title', 'start', 'end', 'allDay', 'backgroundColor', 'borderColor']);
    expect($formato['id'])->toBe($evento->id);
    expect($formato['title'])->toBe($evento->titulo);
    expect($formato['allDay'])->toBeTrue();
});

test('evento calendario toFullCalendarFormat includes time for non-all-day events', function () {
    $evento = EventoCalendario::factory()->create([
        'fecha_inicio' => now()->addDay(),
        'fecha_fin' => now()->addDay(),
        'hora_inicio' => '09:00:00',
        'hora_fin' => '17:00:00',
        'es_todo_el_dia' => false,
    ]);

    $formato = $evento->toFullCalendarFormat();

    expect($formato['allDay'])->toBeFalse();
    expect($formato['start'])->toContain('T');
    expect($formato['end'])->toContain('T');
});
