<?php

use App\Models\Equipo;
use App\Models\Pareja;
use App\Models\User;

test('guests cannot access equipos index', function () {
    $response = $this->get(route('equipos.index'));
    $response->assertRedirect();
    expect($response->getTargetUrl())->toMatch('/login|iniciar-sesion/');
});

test('equipistas cannot access equipos index', function () {
    $user = User::factory()->equipista()->create();

    $this->actingAs($user)
        ->get(route('equipos.index'))
        ->assertForbidden();
});

test('admin users can access equipos index', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('equipos.index'))
        ->assertOk();
});

test('mango users can access equipos index', function () {
    $user = User::factory()->mango()->create();

    $this->actingAs($user)
        ->get(route('equipos.index'))
        ->assertOk();
});

test('equipos index shows all equipos', function () {
    $admin = User::factory()->admin()->create();

    $equipo1 = Equipo::factory()->create(['numero' => 1]);
    $equipo2 = Equipo::factory()->create(['numero' => 2]);

    $this->actingAs($admin)
        ->get(route('equipos.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('equipos.data', 2)
        );
});

test('equipos index filters by numero', function () {
    $admin = User::factory()->admin()->create();

    $equipo1 = Equipo::factory()->create(['numero' => 1]);
    $equipo2 = Equipo::factory()->create(['numero' => 2]);

    $this->actingAs($admin)
        ->get(route('equipos.index', ['numero' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('equipos.data', 1)
            ->where('equipos.data.0.numero', 1)
        );
});

test('equipos index searches by consiliario', function () {
    $admin = User::factory()->admin()->create();

    $equipo1 = Equipo::factory()->create([
        'numero' => 1,
        'consiliario_nombre' => 'Padre Juan',
    ]);
    $equipo2 = Equipo::factory()->create([
        'numero' => 2,
        'consiliario_nombre' => 'Padre Pedro',
    ]);

    $this->actingAs($admin)
        ->get(route('equipos.index', ['buscar' => 'Juan']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('equipos.data', 1)
            ->where('equipos.data.0.id', $equipo1->id)
        );
});

test('guests cannot access equipos create', function () {
    $response = $this->get(route('equipos.create'));
    $response->assertRedirect();
    expect($response->getTargetUrl())->toMatch('/login|iniciar-sesion/');
});

test('equipistas cannot access equipos create', function () {
    $user = User::factory()->equipista()->create();

    $this->actingAs($user)
        ->get(route('equipos.create'))
        ->assertForbidden();
});

test('admin users can access equipos create', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('equipos.create'))
        ->assertOk();
});

test('can create equipo with valid data', function () {
    $admin = User::factory()->admin()->create();

    $data = [
        'numero' => 1,
        'consiliario_nombre' => 'Padre Juan',
    ];

    $this->actingAs($admin)
        ->post(route('equipos.store'), $data)
        ->assertRedirect(route('equipos.index'));

    $this->assertDatabaseHas('equipos', [
        'numero' => 1,
        'consiliario_nombre' => 'Padre Juan',
    ]);
});

test('cannot create equipo with duplicate numero', function () {
    $admin = User::factory()->admin()->create();
    Equipo::factory()->create(['numero' => 1]);

    $data = [
        'numero' => 1, // Número duplicado
        'consiliario_nombre' => 'Padre Juan',
    ];

    $this->actingAs($admin)
        ->post(route('equipos.store'), $data)
        ->assertSessionHasErrors(['numero']);
});

test('can create equipo with responsable', function () {
    $admin = User::factory()->admin()->create();
    $pareja = Pareja::factory()->conUsuarios()->create();
    $usuario = $pareja->usuarios->first();

    $data = [
        'numero' => 1,
        'responsable_id' => $usuario->id,
        'consiliario_nombre' => 'Padre Juan',
    ];

    $this->actingAs($admin)
        ->post(route('equipos.store'), $data)
        ->assertRedirect(route('equipos.index'));

    $equipo = Equipo::where('numero', 1)->first();
    expect($equipo)->not->toBeNull();
    expect($equipo->responsable_id)->toBe($usuario->id);

    // Verificar que ambos usuarios de la pareja fueron ascendidos a admin
    $pareja->refresh();
    $pareja->usuarios->each(function ($user) {
        expect($user->rol)->toBe('admin');
    });
});

test('can access equipo edit page', function () {
    $admin = User::factory()->admin()->create();
    $equipo = Equipo::factory()->create();

    $this->actingAs($admin)
        ->get(route('equipos.edit', $equipo))
        ->assertOk();
});

test('can update equipo', function () {
    $admin = User::factory()->admin()->create();
    $equipo = Equipo::factory()->create(['numero' => 1]);

    $data = [
        'numero' => 2,
        'consiliario_nombre' => 'Padre Pedro',
    ];

    $this->actingAs($admin)
        ->put(route('equipos.update', $equipo), $data)
        ->assertRedirect(route('equipos.index'));

    $equipo->refresh();
    expect($equipo->numero)->toBe(2);
    expect($equipo->consiliario_nombre)->toBe('Padre Pedro');
});

test('can access equipo show page', function () {
    $admin = User::factory()->admin()->create();
    $equipo = Equipo::factory()->create();

    $this->actingAs($admin)
        ->get(route('equipos.show', $equipo))
        ->assertOk();
});

test('equipo show displays parejas with scroll infinito', function () {
    $admin = User::factory()->admin()->create();
    $equipo = Equipo::factory()->create();
    $pareja1 = Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);
    $pareja2 = Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);

    $this->actingAs($admin)
        ->get(route('equipos.show', $equipo))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('parejas.data', 2)
        );
});

test('can asignar responsable to equipo', function () {
    $admin = User::factory()->admin()->create();
    $equipo = Equipo::factory()->create();
    $pareja = Pareja::factory()->conUsuarios()->create();
    $usuario = $pareja->usuarios->first();

    $data = [
        'responsable_id' => $usuario->id,
    ];

    $this->actingAs($admin)
        ->post(route('equipos.asignar-responsable', $equipo), $data)
        ->assertRedirect(route('equipos.show', $equipo));

    $equipo->refresh();
    expect($equipo->responsable_id)->toBe($usuario->id);

    // Verificar que ambos usuarios de la pareja fueron ascendidos a admin
    $pareja->refresh();
    $pareja->usuarios->each(function ($user) {
        expect($user->rol)->toBe('admin');
    });
});

test('asignar responsable degrades previous responsable', function () {
    $admin = User::factory()->admin()->create();
    $equipo = Equipo::factory()->create();

    // Crear primera pareja responsable
    $pareja1 = Pareja::factory()->conUsuarios()->create();
    $usuario1 = $pareja1->usuarios->first();
    $equipo->update(['responsable_id' => $usuario1->id]);
    $pareja1->usuarios()->update(['rol' => 'admin']);

    // Crear segunda pareja para nuevo responsable
    $pareja2 = Pareja::factory()->conUsuarios()->create();
    $usuario2 = $pareja2->usuarios->first();

    $data = [
        'responsable_id' => $usuario2->id,
    ];

    $this->actingAs($admin)
        ->post(route('equipos.asignar-responsable', $equipo), $data)
        ->assertRedirect(route('equipos.show', $equipo));

    $equipo->refresh();
    expect($equipo->responsable_id)->toBe($usuario2->id);

    // Verificar que la primera pareja fue degradada
    $pareja1->refresh();
    $pareja1->usuarios->each(function ($user) {
        expect($user->rol)->toBe('equipista');
    });

    // Verificar que la segunda pareja fue ascendida
    $pareja2->refresh();
    $pareja2->usuarios->each(function ($user) {
        expect($user->rol)->toBe('admin');
    });
});

test('can configurar consiliario', function () {
    $admin = User::factory()->admin()->create();
    $equipo = Equipo::factory()->create();

    $data = [
        'consiliario_nombre' => 'Padre Juan',
    ];

    $this->actingAs($admin)
        ->post(route('equipos.configurar-consiliario', $equipo), $data)
        ->assertRedirect(route('equipos.show', $equipo));

    $equipo->refresh();
    expect($equipo->consiliario_nombre)->toBe('Padre Juan');
});

test('cannot delete equipo with parejas', function () {
    $admin = User::factory()->admin()->create();
    $equipo = Equipo::factory()->create();
    Pareja::factory()->conUsuarios()->create(['equipo_id' => $equipo->id]);

    $this->actingAs($admin)
        ->delete(route('equipos.destroy', $equipo))
        ->assertRedirect(route('equipos.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('equipos', [
        'id' => $equipo->id,
    ]);
});

test('can delete equipo without parejas', function () {
    $admin = User::factory()->admin()->create();
    $equipo = Equipo::factory()->create();

    $this->actingAs($admin)
        ->delete(route('equipos.destroy', $equipo))
        ->assertRedirect(route('equipos.index'));

    $this->assertDatabaseMissing('equipos', [
        'id' => $equipo->id,
    ]);
});

test('delete equipo degrades responsable if exists', function () {
    $admin = User::factory()->admin()->create();
    $equipo = Equipo::factory()->create();
    $pareja = Pareja::factory()->conUsuarios()->create();
    $usuario = $pareja->usuarios->first();
    $equipo->update(['responsable_id' => $usuario->id]);
    $pareja->usuarios()->update(['rol' => 'admin']);

    $this->actingAs($admin)
        ->delete(route('equipos.destroy', $equipo))
        ->assertRedirect(route('equipos.index'));

    // Verificar que la pareja fue degradada
    $pareja->refresh();
    $pareja->usuarios->each(function ($user) {
        expect($user->rol)->toBe('equipista');
    });
});

test('cannot asign same responsable to multiple equipos', function () {
    $admin = User::factory()->admin()->create();
    $equipo1 = Equipo::factory()->create();
    $equipo2 = Equipo::factory()->create();
    $pareja = Pareja::factory()->conUsuarios()->create();
    $usuario = $pareja->usuarios->first();

    // Asignar responsable al primer equipo
    $equipo1->update(['responsable_id' => $usuario->id]);

    // Intentar asignar el mismo responsable al segundo equipo
    $data = [
        'responsable_id' => $usuario->id,
    ];

    $this->actingAs($admin)
        ->post(route('equipos.asignar-responsable', $equipo2), $data)
        ->assertSessionHasErrors(['responsable_id']);
});

test('equipistas cannot asign responsable', function () {
    $equipista = User::factory()->equipista()->create();
    $equipo = Equipo::factory()->create();
    $pareja = Pareja::factory()->conUsuarios()->create();
    $usuario = $pareja->usuarios->first();

    $data = [
        'responsable_id' => $usuario->id,
    ];

    $this->actingAs($equipista)
        ->post(route('equipos.asignar-responsable', $equipo), $data)
        ->assertForbidden();
});

test('equipistas cannot configurar consiliario', function () {
    $equipista = User::factory()->equipista()->create();
    $equipo = Equipo::factory()->create();

    $data = [
        'consiliario_nombre' => 'Padre Juan',
    ];

    $this->actingAs($equipista)
        ->post(route('equipos.configurar-consiliario', $equipo), $data)
        ->assertForbidden();
});
