<?php

use App\Models\Equipo;
use App\Models\EventoCalendario;
use App\Models\User;

test('guests cannot access calendario index', function () {
    $response = $this->get(route('calendario.index'));
    $response->assertRedirect();
    expect($response->getTargetUrl())->toMatch('/login|iniciar-sesion/');
});

test('authenticated users can access calendario index', function () {
    $user = User::factory()->equipista()->create();

    $this->actingAs($user)
        ->get(route('calendario.index'))
        ->assertOk();
});

test('calendario index returns equipos for mango and admin users', function () {
    $equipo = Equipo::factory()->create();
    $mango = User::factory()->mango()->create();

    $this->actingAs($mango)
        ->get(route('calendario.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('equipos', 1)
            ->where('equipos.0.id', $equipo->id)
        );
});

test('calendario events endpoint requires authentication', function () {
    $response = $this->getJson(route('calendario.events', [
        'start' => now()->format('Y-m-d'),
        'end' => now()->addMonth()->format('Y-m-d'),
    ]));

    $response->assertUnauthorized();
});

test('calendario events endpoint returns events in date range', function () {
    $user = User::factory()->equipista()->create();
    $evento = EventoCalendario::factory()
        ->global()
        ->create([
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $this->actingAs($user)
        ->getJson(route('calendario.events', [
            'start' => now()->format('Y-m-d'),
            'end' => now()->addDays(7)->format('Y-m-d'),
        ]))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment([
            'id' => $evento->id,
            'title' => $evento->titulo,
        ]);
});

test('mango users can see all events', function () {
    $mango = User::factory()->mango()->create();
    $equipo = Equipo::factory()->create();

    $eventoGlobal = EventoCalendario::factory()->global()->create([
        'fecha_inicio' => now()->addDay(),
        'fecha_fin' => now()->addDay(),
    ]);
    $eventoEquipo = EventoCalendario::factory()->deEquipo($equipo->id)->create([
        'fecha_inicio' => now()->addDay(),
        'fecha_fin' => now()->addDay(),
    ]);

    $this->actingAs($mango)
        ->getJson(route('calendario.events', [
            'start' => now()->format('Y-m-d'),
            'end' => now()->addDays(7)->format('Y-m-d'),
        ]))
        ->assertOk()
        ->assertJsonCount(2);
});

test('equipista users can only see their team events and global events', function () {
    $equipo1 = Equipo::factory()->create(['numero' => 1]);
    $equipo2 = Equipo::factory()->create(['numero' => 2]);
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo1->id]);
    $user = $pareja->usuarios->first();

    $eventoGlobal = EventoCalendario::factory()->global()->create([
        'fecha_inicio' => now()->addDay(),
        'fecha_fin' => now()->addDay(),
    ]);
    $eventoEquipo1 = EventoCalendario::factory()->deEquipo($equipo1->id)->create([
        'fecha_inicio' => now()->addDay(),
        'fecha_fin' => now()->addDay(),
    ]);
    $eventoEquipo2 = EventoCalendario::factory()->deEquipo($equipo2->id)->create([
        'fecha_inicio' => now()->addDay(),
        'fecha_fin' => now()->addDay(),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('calendario.events', [
            'start' => now()->format('Y-m-d'),
            'end' => now()->addDays(7)->format('Y-m-d'),
        ]))
        ->assertOk();

    $eventos = $response->json();
    $eventosIds = collect($eventos)->pluck('id')->toArray();

    expect($eventosIds)->toContain($eventoGlobal->id);
    expect($eventosIds)->toContain($eventoEquipo1->id);
    expect($eventosIds)->not->toContain($eventoEquipo2->id);
});

test('can create evento with valid data', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $data = [
        'titulo' => 'Evento de prueba',
        'descripcion' => 'Descripción del evento',
        'fecha_inicio' => now()->addDay()->format('Y-m-d'),
        'fecha_fin' => now()->addDay()->format('Y-m-d'),
        'hora_inicio' => '09:00',
        'hora_fin' => '17:00',
        'es_todo_el_dia' => false,
        'tipo' => 'general',
        'alcance' => 'equipo',
        'equipo_id' => $equipo->id,
    ];

    $this->actingAs($user)
        ->post(route('calendario.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('eventos_calendario', [
        'titulo' => 'Evento de prueba',
        'creado_por' => $user->id,
    ]);
});

test('can create all-day evento', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $data = [
        'titulo' => 'Evento todo el día',
        'fecha_inicio' => now()->addDay()->format('Y-m-d'),
        'fecha_fin' => now()->addDay()->format('Y-m-d'),
        'es_todo_el_dia' => true,
        'tipo' => 'general',
        'alcance' => 'equipo',
        'equipo_id' => $equipo->id,
    ];

    $this->actingAs($user)
        ->post(route('calendario.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('eventos_calendario', [
        'titulo' => 'Evento todo el día',
        'es_todo_el_dia' => true,
        'hora_inicio' => null,
        'hora_fin' => null,
    ]);
});

test('cannot create evento with fecha_fin before fecha_inicio', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $data = [
        'titulo' => 'Evento inválido',
        'fecha_inicio' => now()->addDay()->format('Y-m-d'),
        'fecha_fin' => now()->subDay()->format('Y-m-d'),
        'es_todo_el_dia' => true,
        'tipo' => 'general',
        'alcance' => 'equipo',
        'equipo_id' => $equipo->id,
    ];

    $this->actingAs($user)
        ->post(route('calendario.store'), $data)
        ->assertSessionHasErrors(['fecha_fin']);
});

test('cannot create evento with more than one year difference', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $data = [
        'titulo' => 'Evento inválido',
        'fecha_inicio' => now()->format('Y-m-d'),
        'fecha_fin' => now()->addYears(2)->format('Y-m-d'),
        'es_todo_el_dia' => true,
        'tipo' => 'general',
        'alcance' => 'equipo',
        'equipo_id' => $equipo->id,
    ];

    $this->actingAs($user)
        ->post(route('calendario.store'), $data)
        ->assertSessionHasErrors(['fecha_fin']);
});

test('equipista cannot create global evento', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $data = [
        'titulo' => 'Evento global',
        'fecha_inicio' => now()->addDay()->format('Y-m-d'),
        'fecha_fin' => now()->addDay()->format('Y-m-d'),
        'es_todo_el_dia' => true,
        'tipo' => 'general',
        'alcance' => 'global',
    ];

    $this->actingAs($user)
        ->post(route('calendario.store'), $data)
        ->assertSessionHasErrors(['alcance']);
});

test('admin can create global evento', function () {
    $admin = User::factory()->admin()->create();

    $data = [
        'titulo' => 'Evento global',
        'fecha_inicio' => now()->addDay()->format('Y-m-d'),
        'fecha_fin' => now()->addDay()->format('Y-m-d'),
        'es_todo_el_dia' => true,
        'tipo' => 'general',
        'alcance' => 'global',
    ];

    $this->actingAs($admin)
        ->post(route('calendario.store'), $data)
        ->assertRedirect();

    $this->assertDatabaseHas('eventos_calendario', [
        'titulo' => 'Evento global',
        'alcance' => 'global',
    ]);
});

test('can view evento details', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $evento = EventoCalendario::factory()
        ->global()
        ->create([
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $this->actingAs($user)
        ->getJson(route('calendario.show', $evento))
        ->assertOk()
        ->assertJson([
            'id' => $evento->id,
            'title' => $evento->titulo,
        ]);
});

test('can update own evento', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $evento = EventoCalendario::factory()
        ->todoElDia()
        ->deEquipo($equipo->id)
        ->create([
            'creado_por' => $user->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $data = [
        'titulo' => 'Evento actualizado',
        'fecha_inicio' => now()->addDay()->format('Y-m-d'),
        'fecha_fin' => now()->addDay()->format('Y-m-d'),
        'es_todo_el_dia' => true,
        'tipo' => $evento->tipo,
        'alcance' => $evento->alcance,
        'equipo_id' => $evento->equipo_id,
    ];

    $this->actingAs($user)
        ->patch(route('calendario.update', $evento), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $evento->refresh();

    expect($evento->titulo)->toBe('Evento actualizado');
});

test('cannot update evento created by another user', function () {
    $equipo = Equipo::factory()->create();
    $pareja1 = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $pareja2 = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user1 = $pareja1->usuarios->first();
    $user2 = $pareja2->usuarios->first();

    $evento = EventoCalendario::factory()
        ->todoElDia()
        ->create([
            'creado_por' => $user1->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $data = [
        'titulo' => 'Evento actualizado',
        'fecha_inicio' => now()->addDay()->format('Y-m-d'),
        'fecha_fin' => now()->addDay()->format('Y-m-d'),
        'es_todo_el_dia' => true,
        'tipo' => $evento->tipo,
        'alcance' => $evento->alcance,
        'equipo_id' => $evento->equipo_id,
    ];

    $this->actingAs($user2)
        ->patch(route('calendario.update', $evento), $data)
        ->assertSessionHasErrors(['error']);
});

test('mango can update any evento', function () {
    $mango = User::factory()->mango()->create();
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $evento = EventoCalendario::factory()
        ->todoElDia()
        ->deEquipo($equipo->id)
        ->create([
            'creado_por' => $user->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $data = [
        'titulo' => 'Evento actualizado por mango',
        'fecha_inicio' => now()->addDay()->format('Y-m-d'),
        'fecha_fin' => now()->addDay()->format('Y-m-d'),
        'es_todo_el_dia' => true,
        'tipo' => $evento->tipo,
        'alcance' => $evento->alcance,
        'equipo_id' => $evento->equipo_id,
    ];

    $this->actingAs($mango)
        ->patch(route('calendario.update', $evento), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $evento->refresh();

    expect($evento->titulo)->toBe('Evento actualizado por mango');
});

test('can delete own evento', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $evento = EventoCalendario::factory()
        ->create([
            'creado_por' => $user->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $this->actingAs($user)
        ->delete(route('calendario.destroy', $evento))
        ->assertRedirect();

    $this->assertDatabaseMissing('eventos_calendario', [
        'id' => $evento->id,
    ]);
});

test('cannot delete evento created by another user', function () {
    $equipo = Equipo::factory()->create();
    $pareja1 = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $pareja2 = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user1 = $pareja1->usuarios->first();
    $user2 = $pareja2->usuarios->first();

    $evento = EventoCalendario::factory()
        ->create([
            'creado_por' => $user1->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $this->actingAs($user2)
        ->delete(route('calendario.destroy', $evento))
        ->assertSessionHasErrors(['error']);

    $this->assertDatabaseHas('eventos_calendario', [
        'id' => $evento->id,
    ]);
});

test('can update evento fecha via drag and drop', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $evento = EventoCalendario::factory()
        ->todoElDia()
        ->create([
            'creado_por' => $user->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $nuevaFecha = now()->addDays(5)->format('Y-m-d');

    $this->actingAs($user)
        ->postJson(route('calendario.update-fecha', $evento), [
            'start' => $nuevaFecha,
            'end' => $nuevaFecha,
            'allDay' => true,
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $evento->refresh();

    expect($evento->fecha_inicio->format('Y-m-d'))->toBe($nuevaFecha);
    expect($evento->fecha_fin->format('Y-m-d'))->toBe($nuevaFecha);
});

test('can update evento fecha with time', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    $evento = EventoCalendario::factory()
        ->create([
            'creado_por' => $user->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
            'hora_inicio' => '09:00:00',
            'hora_fin' => '17:00:00',
            'es_todo_el_dia' => false,
        ]);

    $nuevaFecha = now()->addDays(5)->setTime(14, 0, 0)->utc()->toIso8601String();

    $this->actingAs($user)
        ->postJson(route('calendario.update-fecha', $evento), [
            'start' => $nuevaFecha,
            'end' => now()->addDays(5)->setTime(16, 0, 0)->utc()->toIso8601String(),
            'allDay' => false,
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $evento->refresh();
    expect($evento->fecha_inicio->format('Y-m-d'))->toBe(now()->addDays(5)->format('Y-m-d'));
    expect($evento->hora_inicio)->not->toBeNull();
});

test('cannot update evento fecha without permission', function () {
    $equipo = Equipo::factory()->create();
    $pareja1 = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $pareja2 = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user1 = $pareja1->usuarios->first();
    $user2 = $pareja2->usuarios->first();

    $evento = EventoCalendario::factory()
        ->create([
            'creado_por' => $user1->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $this->actingAs($user2)
        ->postJson(route('calendario.update-fecha', $evento), [
            'start' => now()->addDays(5)->format('Y-m-d'),
            'allDay' => true,
        ])
        ->assertForbidden();
});

test('can export calendario to ics', function () {
    $equipo = Equipo::factory()->create();
    $pareja = \App\Models\Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $user = $pareja->usuarios->first();

    EventoCalendario::factory()
        ->global()
        ->create([
            'titulo' => 'Evento de prueba',
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay(),
        ]);

    $response = $this->actingAs($user)
        ->get(route('calendario.exportar', [
            'start' => now()->format('Y-m-d'),
            'end' => now()->addDays(7)->format('Y-m-d'),
        ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    expect($response->getContent())->toContain('BEGIN:VCALENDAR');
    expect($response->getContent())->toContain('Evento de prueba');
});
