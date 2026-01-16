<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Pareja;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewPareja implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        protected ImageService $imageService,
    ) {}

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
            'el_foto' => ['nullable', 'image', 'max:5120'], // 5MB max

            // Datos de ELLA
            'ella_nombres' => ['required', 'string', 'max:255'],
            'ella_apellidos' => ['required', 'string', 'max:255'],
            'ella_celular' => ['required', 'string', 'max:20'],
            'ella_fecha_nacimiento' => ['required', 'date', 'before:today'],
            'ella_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'ella_foto' => ['nullable', 'image', 'max:5120'], // 5MB max

            // Datos de la pareja
            'fecha_ingreso' => ['required', 'date', 'before_or_equal:today'],
            'equipo_id' => ['nullable', 'exists:equipos,id'],
            'pareja_foto' => ['nullable', 'image', 'max:5120'], // 5MB max
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            // Guardar imagen de la pareja y generar thumbnails
            $parejaImages = $this->imageService->saveImageFromFile(
                $input['pareja_foto'] ?? null,
                'parejas',
            );

            // Crear la pareja
            $pareja = Pareja::create([
                'fecha_ingreso' => $input['fecha_ingreso'],
                'equipo_id' => $input['equipo_id'] ?? null,
                'foto_path' => $parejaImages['original'],
                'foto_thumbnail_50' => $parejaImages['50'],
                'foto_thumbnail_100' => $parejaImages['100'],
                'foto_thumbnail_500' => $parejaImages['500'],
            ]);

            // Guardar imagen de ÉL y generar thumbnails
            $elImages = $this->imageService->saveImageFromFile(
                $input['el_foto'] ?? null,
                'users',
            );

            // Crear usuario ÉL
            $el = User::create([
                'nombres' => $input['el_nombres'],
                'apellidos' => $input['el_apellidos'],
                'celular' => $input['el_celular'],
                'fecha_nacimiento' => $input['el_fecha_nacimiento'],
                'sexo' => 'masculino',
                'email' => $input['el_email'],
                'foto_path' => $elImages['original'],
                'foto_thumbnail_50' => $elImages['50'],
                'foto_thumbnail_100' => $elImages['100'],
                'foto_thumbnail_500' => $elImages['500'],
                'password' => $input['password'],
                'pareja_id' => $pareja->id,
                'rol' => 'equipista', // Rol por defecto
            ]);

            // Guardar imagen de ELLA y generar thumbnails
            $ellaImages = $this->imageService->saveImageFromFile(
                $input['ella_foto'] ?? null,
                'users',
            );

            // Crear usuario ELLA
            $ella = User::create([
                'nombres' => $input['ella_nombres'],
                'apellidos' => $input['ella_apellidos'],
                'celular' => $input['ella_celular'],
                'fecha_nacimiento' => $input['ella_fecha_nacimiento'],
                'sexo' => 'femenino',
                'email' => $input['ella_email'],
                'foto_path' => $ellaImages['original'],
                'foto_thumbnail_50' => $ellaImages['50'],
                'foto_thumbnail_100' => $ellaImages['100'],
                'foto_thumbnail_500' => $ellaImages['500'],
                'password' => $input['password'],
                'pareja_id' => $pareja->id,
                'rol' => 'equipista', // Rol por defecto
            ]);

            // Retornar el usuario que iniciará sesión (podría ser cualquiera de los dos)
            return $el;
        });
    }
}
