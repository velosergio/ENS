<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new couples can register', function () {
    $response = $this->post(route('register.store'), [
        // Datos de ÉL
        'el_nombres' => 'Juan',
        'el_apellidos' => 'Pérez',
        'el_celular' => '1234567890',
        'el_fecha_nacimiento' => '1990-01-01',
        'el_email' => 'juan@example.com',

        // Datos de ELLA
        'ella_nombres' => 'María',
        'ella_apellidos' => 'García',
        'ella_celular' => '0987654321',
        'ella_fecha_nacimiento' => '1992-05-15',
        'ella_email' => 'maria@example.com',

        // Datos de la pareja
        'fecha_acogida' => '2024-01-01',
        'numero_equipo' => 1,

        // Contraseña (compartida)
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
