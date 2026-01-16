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
            'equipo_id' => ['nullable', 'exists:equipos,id'],
            'pareja_foto_base64' => ['nullable', 'string'],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            // Generar thumbnails para la foto de la pareja
            $parejaThumbnails = $this->imageService->generateThumbnails(
                $input['pareja_foto_base64'] ?? null,
            );

            // Crear la pareja
            $pareja = Pareja::create([
                'fecha_ingreso' => $input['fecha_ingreso'],
                'equipo_id' => $input['equipo_id'] ?? null,
                'foto_base64' => $input['pareja_foto_base64'] ?? null,
                'foto_thumbnail_50' => $parejaThumbnails['50'],
                'foto_thumbnail_100' => $parejaThumbnails['100'],
                'foto_thumbnail_500' => $parejaThumbnails['500'],
            ]);

            // Generar thumbnails para la foto de ÉL
            $elThumbnails = $this->imageService->generateThumbnails(
                $input['el_foto_base64'] ?? null,
            );

            // Crear usuario ÉL
            $el = User::create([
                'nombres' => $input['el_nombres'],
                'apellidos' => $input['el_apellidos'],
                'celular' => $input['el_celular'],
                'fecha_nacimiento' => $input['el_fecha_nacimiento'],
                'sexo' => 'masculino',
                'email' => $input['el_email'],
                'foto_base64' => $input['el_foto_base64'] ?? null,
                'foto_thumbnail_50' => $elThumbnails['50'],
                'foto_thumbnail_100' => $elThumbnails['100'],
                'foto_thumbnail_500' => $elThumbnails['500'],
                'password' => $input['password'],
                'pareja_id' => $pareja->id,
                'rol' => 'equipista', // Rol por defecto
            ]);

            // Generar thumbnails para la foto de ELLA
            $ellaThumbnails = $this->imageService->generateThumbnails(
                $input['ella_foto_base64'] ?? null,
            );

            // Crear usuario ELLA
            $ella = User::create([
                'nombres' => $input['ella_nombres'],
                'apellidos' => $input['ella_apellidos'],
                'celular' => $input['ella_celular'],
                'fecha_nacimiento' => $input['ella_fecha_nacimiento'],
                'sexo' => 'femenino',
                'email' => $input['ella_email'],
                'foto_base64' => $input['ella_foto_base64'] ?? null,
                'foto_thumbnail_50' => $ellaThumbnails['50'],
                'foto_thumbnail_100' => $ellaThumbnails['100'],
                'foto_thumbnail_500' => $ellaThumbnails['500'],
                'password' => $input['password'],
                'pareja_id' => $pareja->id,
                'rol' => 'equipista', // Rol por defecto
            ]);

            // Retornar el usuario que iniciará sesión (podría ser cualquiera de los dos)
            return $el;
        });
    }
}
