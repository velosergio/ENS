<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipo>
 */
class EquipoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->unique()->numberBetween(1, 20),
            'responsable_id' => null,
            'consiliario_nombre' => fake()->optional()->name(),
        ];
    }

    /**
     * Asignar un responsable al equipo.
     */
    public function conResponsable(?int $userId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'responsable_id' => $userId,
        ]);
    }

    /**
     * Asignar un consiliario al equipo.
     */
    public function conConsiliario(?string $nombre = null): static
    {
        return $this->state(fn (array $attributes) => [
            'consiliario_nombre' => $nombre ?? fake()->name(),
        ]);
    }
}
