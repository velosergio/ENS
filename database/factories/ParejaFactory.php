<?php

namespace Database\Factories;

use App\Models\Pareja;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pareja>
 */
class ParejaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha_acogida' => fake()->dateTimeBetween('-10 years', 'now'),
            'fecha_boda' => fake()->dateTimeBetween('-20 years', '-1 year'),
            'equipo_id' => null,
            'foto_path' => null,
            'foto_thumbnail_50' => null,
            'foto_thumbnail_100' => null,
            'foto_thumbnail_500' => null,
            'estado' => 'activo',
        ];
    }

    /**
     * Indicar que la pareja está activa.
     */
    public function activa(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activo',
        ]);
    }

    /**
     * Indicar que la pareja está retirada.
     */
    public function retirada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'retirado',
        ]);
    }

    /**
     * Crear la pareja con sus dos usuarios (él y ella).
     *
     * @param  array<string, mixed>  $userAttributes
     */
    public function conUsuarios(array $userAttributes = []): static
    {
        return $this->afterCreating(function (Pareja $pareja) use ($userAttributes) {
            // Crear usuario masculino
            User::factory()
                ->masculino()
                ->create(array_merge([
                    'pareja_id' => $pareja->id,
                ], $userAttributes));

            // Crear usuario femenino
            User::factory()
                ->femenino()
                ->create(array_merge([
                    'pareja_id' => $pareja->id,
                ], $userAttributes));
        });
    }
}
