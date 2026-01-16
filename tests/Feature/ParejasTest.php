<?php

use App\Models\Pareja;
use App\Models\User;

test('guests cannot access parejas index', function () {
    $response = $this->get(route('parejas.index'));
    $response->assertRedirect();
    expect($response->getTargetUrl())->toMatch('/login|iniciar-sesion/');
});

test('equipistas cannot access parejas index', function () {
    $user = User::factory()->equipista()->create();

    $this->actingAs($user)
        ->get(route('parejas.index'))
        ->assertForbidden();
});

test('admin users can access parejas index', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('parejas.index'))
        ->assertOk();
});

test('mango users can access parejas index', function () {
    $user = User::factory()->mango()->create();

    $this->actingAs($user)
        ->get(route('parejas.index'))
        ->assertOk();
});

test('parejas index excludes parejas with mango users', function () {
    $admin = User::factory()->admin()->create();

    // Crear pareja normal
    $parejaNormal = Pareja::factory()
        ->conUsuarios()
        ->create();

    // Crear pareja con usuario mango (no debe aparecer)
    $parejaMango = Pareja::factory()->create();
    User::factory()->masculino()->mango()->create(['pareja_id' => $parejaMango->id]);
    User::factory()->femenino()->create(['pareja_id' => $parejaMango->id]);

    $response = $this->actingAs($admin)
        ->get(route('parejas.index'))
        ->assertOk();

    $parejas = $response->getOriginalContent()->getData()['page']['props']['parejas']['data'];
    $parejaIds = collect($parejas)->pluck('id')->toArray();

    expect($parejaIds)->toContain($parejaNormal->id);
    expect($parejaIds)->not->toContain($parejaMango->id);
});

test('parejas index filters by estado', function () {
    $admin = User::factory()->admin()->create();

    $parejaActiva = Pareja::factory()
        ->activa()
        ->conUsuarios()
        ->create();

    $parejaRetirada = Pareja::factory()
        ->retirada()
        ->conUsuarios()
        ->create();

    // Por defecto solo muestra activas
    $this->actingAs($admin)
        ->get(route('parejas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('parejas.data', 1)
            ->where('parejas.data.0.id', $parejaActiva->id)
        );

    // Filtrar por retiradas
    $this->actingAs($admin)
        ->get(route('parejas.index', ['estado' => 'retirado']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('parejas.data', 1)
            ->where('parejas.data.0.id', $parejaRetirada->id)
        );
});

test('parejas index filters by numero_equipo', function () {
    $admin = User::factory()->admin()->create();

    $pareja1 = Pareja::factory()
        ->state(['numero_equipo' => 10])
        ->conUsuarios()
        ->create();

    $pareja2 = Pareja::factory()
        ->state(['numero_equipo' => 20])
        ->conUsuarios()
        ->create();

    $this->actingAs($admin)
        ->get(route('parejas.index', ['numero_equipo' => 10]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('parejas.data', 1)
            ->where('parejas.data.0.numero_equipo', 10)
        );
});

test('parejas index searches by nombres', function () {
    $admin = User::factory()->admin()->create();

    $pareja = Pareja::factory()
        ->conUsuarios([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ])
        ->create();

    $this->actingAs($admin)
        ->get(route('parejas.index', ['buscar' => 'Juan']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('parejas.data', 1)
            ->where('parejas.data.0.id', $pareja->id)
        );
});

test('parejas index searches by email', function () {
    $admin = User::factory()->admin()->create();

    $pareja = Pareja::factory()->create();
    User::factory()->masculino()->create([
        'pareja_id' => $pareja->id,
        'email' => 'juan.perez@example.com',
    ]);
    User::factory()->femenino()->create(['pareja_id' => $pareja->id]);

    $this->actingAs($admin)
        ->get(route('parejas.index', ['buscar' => 'juan.perez@example.com']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('parejas.data', 1)
            ->where('parejas.data.0.id', $pareja->id)
        );
});

test('guests cannot access parejas create', function () {
    $response = $this->get(route('parejas.create'));
    $response->assertRedirect();
    expect($response->getTargetUrl())->toMatch('/login|iniciar-sesion/');
});

test('equipistas cannot access parejas create', function () {
    $user = User::factory()->equipista()->create();

    $this->actingAs($user)
        ->get(route('parejas.create'))
        ->assertForbidden();
});

test('admin users can access parejas create', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('parejas.create'))
        ->assertOk();
});

test('can create pareja with valid data', function () {
    $admin = User::factory()->admin()->create();

    $data = [
        'el_nombres' => 'Juan',
        'el_apellidos' => 'Pérez',
        'el_celular' => '1234567890',
        'el_fecha_nacimiento' => '1990-01-01',
        'el_email' => 'juan@example.com',
        'el_foto_base64' => '',
        'ella_nombres' => 'María',
        'ella_apellidos' => 'González',
        'ella_celular' => '0987654321',
        'ella_fecha_nacimiento' => '1992-05-15',
        'ella_email' => 'maria@example.com',
        'ella_foto_base64' => '',
        'fecha_ingreso' => '2024-01-01',
        'numero_equipo' => 5,
        'pareja_foto_base64' => '',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ];

    $this->actingAs($admin)
        ->post(route('parejas.store'), $data)
        ->assertRedirect(route('parejas.index'));

    $this->assertDatabaseHas('parejas', [
        'numero_equipo' => 5,
        'estado' => 'activo',
    ]);

    $pareja = Pareja::where('numero_equipo', 5)->first();
    expect($pareja)->not->toBeNull();

    // Verificar que se crearon los dos usuarios
    expect($pareja->usuarios)->toHaveCount(2);
    expect($pareja->el()->email)->toBe('juan@example.com');
    expect($pareja->ella()->email)->toBe('maria@example.com');
});

test('cannot create pareja with duplicate emails', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'juan@example.com']);

    $data = [
        'el_nombres' => 'Juan',
        'el_apellidos' => 'Pérez',
        'el_celular' => '1234567890',
        'el_fecha_nacimiento' => '1990-01-01',
        'el_email' => 'juan@example.com', // Email duplicado
        'el_foto_base64' => '',
        'ella_nombres' => 'María',
        'ella_apellidos' => 'González',
        'ella_celular' => '0987654321',
        'ella_fecha_nacimiento' => '1992-05-15',
        'ella_email' => 'maria@example.com',
        'ella_foto_base64' => '',
        'fecha_ingreso' => '2024-01-01',
        'numero_equipo' => 5,
        'pareja_foto_base64' => '',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ];

    $this->actingAs($admin)
        ->post(route('parejas.store'), $data)
        ->assertSessionHasErrors(['el_email']);
});

test('can access pareja edit page', function () {
    $admin = User::factory()->admin()->create();
    $pareja = Pareja::factory()->conUsuarios()->create();

    $this->actingAs($admin)
        ->get(route('parejas.edit', $pareja))
        ->assertOk();
});

test('can update pareja', function () {
    $admin = User::factory()->admin()->create();
    $pareja = Pareja::factory()->conUsuarios()->create();

    $el = $pareja->el();
    $ella = $pareja->ella();

    $data = [
        'fecha_ingreso' => '2024-02-01',
        'numero_equipo' => 10,
        'estado' => 'activo',
        'pareja_foto_base64' => '',
        'el_id' => $el->id,
        'el_nombres' => 'Juan Actualizado',
        'el_apellidos' => 'Pérez',
        'el_celular' => '1234567890',
        'el_fecha_nacimiento' => '1990-01-01',
        'el_email' => $el->email,
        'el_foto_base64' => '',
        'ella_id' => $ella->id,
        'ella_nombres' => 'María',
        'ella_apellidos' => 'González Actualizada',
        'ella_celular' => '0987654321',
        'ella_fecha_nacimiento' => '1992-05-15',
        'ella_email' => $ella->email,
        'ella_foto_base64' => '',
    ];

    $this->actingAs($admin)
        ->patch(route('parejas.update', $pareja), $data)
        ->assertRedirect(route('parejas.index'));

    $pareja->refresh();
    expect($pareja->numero_equipo)->toBe(10);
    expect($pareja->el()->nombres)->toBe('Juan Actualizado');
    expect($pareja->ella()->apellidos)->toBe('González Actualizada');
});

test('admin can retirar pareja', function () {
    $admin = User::factory()->admin()->create();
    $pareja = Pareja::factory()->activa()->conUsuarios()->create();

    $this->actingAs($admin)
        ->post(route('parejas.retirar', $pareja))
        ->assertRedirect(route('parejas.index'));

    $pareja->refresh();
    expect($pareja->estado)->toBe('retirado');
});

test('admin can reactivar pareja', function () {
    $admin = User::factory()->admin()->create();
    $pareja = Pareja::factory()->retirada()->conUsuarios()->create();

    $this->actingAs($admin)
        ->post(route('parejas.reactivar', $pareja))
        ->assertRedirect(route('parejas.index'));

    $pareja->refresh();
    expect($pareja->estado)->toBe('activo');
});

test('equipista cannot retirar pareja', function () {
    $equipista = User::factory()->equipista()->create();
    $pareja = Pareja::factory()->activa()->conUsuarios()->create();

    $this->actingAs($equipista)
        ->post(route('parejas.retirar', $pareja))
        ->assertForbidden();
});

test('equipista cannot reactivar pareja', function () {
    $equipista = User::factory()->equipista()->create();
    $pareja = Pareja::factory()->retirada()->conUsuarios()->create();

    $this->actingAs($equipista)
        ->post(route('parejas.reactivar', $pareja))
        ->assertForbidden();
});

test('retirar pareja logs out user if they belong to that pareja', function () {
    $pareja = Pareja::factory()->activa()->conUsuarios()->create();
    $user = $pareja->usuarios->first();
    $admin = User::factory()->admin()->create();

    // Actuar como el usuario de la pareja
    $this->actingAs($user);

    // Retirar pareja como admin (en otro contexto)
    $this->actingAs($admin)
        ->post(route('parejas.retirar', $pareja))
        ->assertRedirect(route('parejas.index'));

    $pareja->refresh();
    expect($pareja->estado)->toBe('retirado');

    // El usuario de la pareja retirada debería ser deslogueado por el middleware
    // cuando intente acceder a cualquier ruta
    $response = $this->actingAs($user->fresh())
        ->get(route('dashboard'));
    $response->assertRedirect();
    expect($response->getTargetUrl())->toMatch('/login|iniciar-sesion/');
});
