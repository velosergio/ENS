<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Pareja;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewPareja implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validar y crear una nueva pareja con sus dos usuarios.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            // Datos de ÉL
            'el_nombres' => ['required', 'string', 'max:255'],
            'el_apellidos' => ['required', 'string', 'max:255'],
            'el_celular' => ['required', 'string', 'max:20'],
            'el_fecha_nacimiento' => ['required', 'date', 'before:today'],
            'el_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'el_foto_base64' => ['nullable', 'string'],

            // Datos de ELLA
            'ella_nombres' => ['required', 'string', 'max:255'],
            'ella_apellidos' => ['required', 'string', 'max:255'],
            'ella_celular' => ['required', 'string', 'max:20'],
            'ella_fecha_nacimiento' => ['required', 'date', 'before:today'],
            'ella_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'ella_foto_base64' => ['nullable', 'string'],

            // Datos de la pareja
            'fecha_ingreso' => ['required', 'date', 'before_or_equal:today'],
            'numero_equipo' => ['nullable', 'integer', 'min:0'],
            'pareja_foto_base64' => ['nullable', 'string'],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            // Crear la pareja
            $pareja = Pareja::create([
                'fecha_ingreso' => $input['fecha_ingreso'],
                'numero_equipo' => isset($input['numero_equipo']) && $input['numero_equipo'] !== '' ? (int) $input['numero_equipo'] : null,
                'foto_base64' => $input['pareja_foto_base64'] ?? null,
            ]);

            // Crear usuario ÉL
            $el = User::create([
                'nombres' => $input['el_nombres'],
                'apellidos' => $input['el_apellidos'],
                'celular' => $input['el_celular'],
                'fecha_nacimiento' => $input['el_fecha_nacimiento'],
                'sexo' => 'masculino',
                'email' => $input['el_email'],
                'foto_base64' => $input['el_foto_base64'] ?? null,
                'password' => $input['password'],
                'pareja_id' => $pareja->id,
                'rol' => 'equipista', // Rol por defecto
            ]);

            // Crear usuario ELLA
            $ella = User::create([
                'nombres' => $input['ella_nombres'],
                'apellidos' => $input['ella_apellidos'],
                'celular' => $input['ella_celular'],
                'fecha_nacimiento' => $input['ella_fecha_nacimiento'],
                'sexo' => 'femenino',
                'email' => $input['ella_email'],
                'foto_base64' => $input['ella_foto_base64'] ?? null,
                'password' => $input['password'],
                'pareja_id' => $pareja->id,
                'rol' => 'equipista', // Rol por defecto
            ]);

            // Retornar el usuario que iniciará sesión (podría ser cualquiera de los dos)
            return $el;
        });
    }
}
