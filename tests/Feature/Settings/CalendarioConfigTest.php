<?php

use App\Models\ConfiguracionCalendario;
use App\Models\User;

test('guests cannot access calendario config', function () {
    $response = $this->get(route('calendario.configuracion.edit'));
    $response->assertRedirect();
});

test('equipista cannot access calendario config', function () {
    $user = User::factory()->equipista()->create();

    $this->actingAs($user)
        ->get(route('calendario.configuracion.edit'))
        ->assertForbidden();
});

test('admin can access calendario config', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('calendario.configuracion.edit'))
        ->assertOk();
});

test('mango can access calendario config', function () {
    $mango = User::factory()->mango()->create();

    $this->actingAs($mango)
        ->get(route('calendario.configuracion.edit'))
        ->assertOk();
});

test('admin can update calendario config', function () {
    $admin = User::factory()->admin()->create();
    $configuracion = ConfiguracionCalendario::where('tipo_evento', 'general')->first();

    $data = [
        'configuraciones' => [
            [
                'id' => $configuracion->id,
                'color' => '#ff0000',
                'icono' => 'Calendar',
            ],
        ],
    ];

    $this->actingAs($admin)
        ->patch(route('calendario.configuracion.update'), $data)
        ->assertRedirect();

    $configuracion->refresh();
    expect($configuracion->color)->toBe('#ff0000');
});

test('equipista cannot update calendario config', function () {
    $equipista = User::factory()->equipista()->create();
    $configuracion = ConfiguracionCalendario::where('tipo_evento', 'general')->first();

    $data = [
        'configuraciones' => [
            [
                'id' => $configuracion->id,
                'color' => '#ff0000',
                'icono' => 'Calendar',
            ],
        ],
    ];

    $this->actingAs($equipista)
        ->patch(route('calendario.configuracion.update'), $data)
        ->assertForbidden();
});
