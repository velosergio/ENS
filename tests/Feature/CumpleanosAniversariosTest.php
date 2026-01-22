<?php

use App\Models\Equipo;
use App\Models\Pareja;
use App\Models\User;
use App\Services\CumpleanosAniversariosService;
use App\Services\CumpleanosService;

beforeEach(function () {
    // Usar un número único para evitar conflictos
    $numeroEquipo = fake()->unique()->numberBetween(100, 999);
    $this->equipo = Equipo::factory()->create(['numero' => $numeroEquipo]);
});

test('cumpleanos aniversarios service obtiene aniversarios del mes', function () {
    $pareja = Pareja::factory()->conUsuarios()->create([
        'fecha_boda' => now()->setYear(2010)->setMonth(6)->setDay(15),
        'fecha_acogida' => now()->setYear(2015)->setMonth(3)->setDay(20),
        'equipo_id' => $this->equipo->id,
        'estado' => 'activo',
    ]);

    $usuario = User::factory()->mango()->create();
    $service = new CumpleanosAniversariosService;

    // Obtener aniversarios de junio (mes de boda)
    $aniversarios = $service->obtenerAniversariosDelMes(6, 2024, $usuario, null);

    expect($aniversarios)->not->toBeEmpty();
    $aniversarioBoda = collect($aniversarios)->firstWhere('tipo', 'aniversario_boda');
    expect($aniversarioBoda)->not->toBeNull();
    expect($aniversarioBoda['años'])->toBe(14); // 2024 - 2010
});

test('cumpleanos aniversarios service obtiene aniversarios de acogida del mes', function () {
    $pareja = Pareja::factory()->conUsuarios()->create([
        'fecha_acogida' => now()->setYear(2015)->setMonth(3)->setDay(20),
        'equipo_id' => $this->equipo->id,
        'estado' => 'activo',
    ]);

    $usuario = User::factory()->mango()->create();
    $service = new CumpleanosAniversariosService;

    // Obtener aniversarios de marzo (mes de acogida)
    $aniversarios = $service->obtenerAniversariosDelMes(3, 2024, $usuario, null);

    expect($aniversarios)->not->toBeEmpty();
    $aniversarioAcogida = collect($aniversarios)->firstWhere('tipo', 'aniversario_acogida');
    expect($aniversarioAcogida)->not->toBeNull();
    expect($aniversarioAcogida['años'])->toBe(9); // 2024 - 2015
});

test('cumpleanos aniversarios service filtra por equipo cuando usuario no es mango', function () {
    $numeroEquipo1 = fake()->unique()->numberBetween(400, 499);
    $numeroEquipo2 = fake()->unique()->numberBetween(500, 599);
    $equipo1 = Equipo::factory()->create(['numero' => $numeroEquipo1]);
    $equipo2 = Equipo::factory()->create(['numero' => $numeroEquipo2]);

    $pareja1 = Pareja::factory()->conUsuarios()->create([
        'fecha_boda' => now()->setYear(2010)->setMonth(6)->setDay(15),
        'equipo_id' => $equipo1->id,
        'estado' => 'activo',
    ]);

    $pareja2 = Pareja::factory()->conUsuarios()->create([
        'fecha_boda' => now()->setYear(2010)->setMonth(6)->setDay(15),
        'equipo_id' => $equipo2->id,
        'estado' => 'activo',
    ]);

    // Usuario equipista del equipo 1
    $parejaUsuario = Pareja::factory()->conUsuarios()->create([
        'equipo_id' => $equipo1->id,
        'estado' => 'activo',
    ]);
    $usuarioEquipo1 = $parejaUsuario->usuarios()->first();

    $service = new CumpleanosAniversariosService;

    // Solo debería ver aniversarios del equipo 1
    $aniversarios = $service->obtenerAniversariosDelMes(6, 2024, $usuarioEquipo1, $equipo1->id);

    expect($aniversarios)->not->toBeEmpty();
    // Verificar que todos los aniversarios son del equipo correcto
    foreach ($aniversarios as $aniversario) {
        $parts = explode('_', $aniversario['id']);
        // Formato: aniversario_boda_{pareja_id}_{año}
        $parejaId = (int) $parts[2];
        $pareja = Pareja::find($parejaId);
        expect($pareja)->not->toBeNull();
        if ($pareja) {
            expect($pareja->equipo_id)->toBe($equipo1->id);
        }
    }
});

test('cumpleanos aniversarios service excluye parejas retiradas', function () {
    $añoActual = (int) now()->format('Y');
    $parejaActiva = Pareja::factory()->conUsuarios()->create([
        'fecha_boda' => now()->setYear(2010)->setMonth(6)->setDay(15),
        'equipo_id' => $this->equipo->id,
        'estado' => 'activo',
    ]);

    $parejaRetirada = Pareja::factory()->conUsuarios()->create([
        'fecha_boda' => now()->setYear(2010)->setMonth(6)->setDay(15),
        'equipo_id' => $this->equipo->id,
        'estado' => 'retirado',
    ]);

    $usuario = User::factory()->mango()->create();
    $service = new CumpleanosAniversariosService;

    $aniversarios = $service->obtenerAniversariosDelMes(6, $añoActual, $usuario, null);

    // Solo debería incluir la pareja activa
    $parejasIds = array_map(function ($aniversario) {
        $parts = explode('_', $aniversario['id']);
        // Formato: aniversario_boda_{pareja_id}_{año}
        return (int) $parts[2];
    }, $aniversarios);

    expect($parejasIds)->toContain($parejaActiva->id);
    expect($parejasIds)->not->toContain($parejaRetirada->id);
});

test('cumpleanos aniversarios service maneja años bisiestos en aniversarios', function () {
    $pareja = Pareja::factory()->conUsuarios()->create([
        'fecha_boda' => now()->setYear(2000)->setMonth(2)->setDay(29),
        'equipo_id' => $this->equipo->id,
        'estado' => 'activo',
    ]);

    $usuario = User::factory()->mango()->create();
    $service = new CumpleanosAniversariosService;

    // Probar con año no bisiesto (2025)
    $aniversarios = $service->obtenerAniversariosDelMes(2, 2025, $usuario, null);

    $aniversarioBoda = collect($aniversarios)->firstWhere('tipo', 'aniversario_boda');
    if ($aniversarioBoda) {
        $fecha = \Carbon\Carbon::parse($aniversarioBoda['fecha']);
        // En años no bisiestos, debería aparecer el 28 de febrero
        expect($fecha->format('m-d'))->toBe('02-28');
    }
});

test('cumpleanos aniversarios service obtiene proximos eventos', function () {
    $fechaBoda = now()->addDays(5);
    $pareja = Pareja::factory()->conUsuarios()->create([
        'fecha_boda' => $fechaBoda->copy()->setYear(2010),
        'equipo_id' => $this->equipo->id,
        'estado' => 'activo',
    ]);

    $usuario = User::factory()->mango()->create();
    $service = new CumpleanosAniversariosService;

    $eventos = $service->obtenerProximosEventos(7, $usuario, null);

    expect($eventos)->not->toBeEmpty();
    // Debería incluir el aniversario que está en 5 días
    $tieneAniversario = collect($eventos)->contains(function ($evento) {
        return isset($evento['tipo']) && $evento['tipo'] === 'aniversario_boda';
    });
    expect($tieneAniversario)->toBeTrue();
});

test('cumpleanos aniversarios controller index requiere autenticacion', function () {
    $response = $this->get('/cumpleanos-aniversarios');

    $response->assertRedirect('/iniciar-sesion');
});

test('cumpleanos aniversarios controller index muestra vista para usuario autenticado', function () {
    $user = User::factory()->mango()->create();

    $response = $this->actingAs($user)->get('/cumpleanos-aniversarios');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('cumpleanos-aniversarios/index')
        ->has('cumpleanos')
        ->has('aniversarios')
        ->has('mes')
        ->has('año')
    );
});

test('cumpleanos aniversarios controller index filtra por equipo para equipista', function () {
    $numeroEquipo = fake()->unique()->numberBetween(200, 299);
    $equipo = Equipo::factory()->create(['numero' => $numeroEquipo]);
    $pareja = Pareja::factory()->conUsuarios()->create([
        'equipo_id' => $equipo->id,
        'estado' => 'activo',
    ]);
    $user = $pareja->usuarios()->first();

    $response = $this->actingAs($user)->get('/cumpleanos-aniversarios');

    $response->assertSuccessful();
    // El equipo_id debería estar forzado al equipo del usuario
    $response->assertInertia(fn ($page) => $page
        ->where('equipo_id', $equipo->id)
    );
});

test('cumpleanos aniversarios controller proximos retorna json', function () {
    // Crear pareja con aniversario próximo
    $fechaBoda = now()->addDays(3);
    $pareja = Pareja::factory()->conUsuarios()->create([
        'fecha_boda' => $fechaBoda->copy()->setYear(2010),
        'equipo_id' => $this->equipo->id,
        'estado' => 'activo',
    ]);

    $user = User::factory()->mango()->create();

    $response = $this->actingAs($user)->get('/cumpleanos-aniversarios/proximos?dias=7');

    $response->assertSuccessful();
    $data = $response->json();
    
    // Verificar que es un array
    expect($data)->toBeArray();
    
    // Si hay eventos, verificar que tienen la estructura esperada
    if (! empty($data)) {
        foreach ($data as $evento) {
            expect($evento)->toHaveKey('id');
            expect($evento)->toHaveKey('tipo');
        }
    }
});

test('cumpleanos aniversarios controller oculta datos sensibles para equipista', function () {
    $numeroEquipo = fake()->unique()->numberBetween(300, 399);
    $equipo = Equipo::factory()->create(['numero' => $numeroEquipo]);
    $pareja = Pareja::factory()->conUsuarios()->create([
        'equipo_id' => $equipo->id,
        'estado' => 'activo',
    ]);
    $user = $pareja->usuarios()->first();

    $response = $this->actingAs($user)->get('/cumpleanos-aniversarios');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('puedeVerDatosSensibles', false)
    );
});

test('cumpleanos aniversarios controller muestra datos sensibles para mango', function () {
    $user = User::factory()->mango()->create();

    $response = $this->actingAs($user)->get('/cumpleanos-aniversarios');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('puedeVerDatosSensibles', true)
    );
});
