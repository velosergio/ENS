<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventoCalendario>
 */
class EventoCalendarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fechaInicio = \Carbon\Carbon::parse(fake()->dateTimeBetween('now', '+6 months'));
        // Asegurar que fecha_fin sea >= fecha_inicio (máximo 1 mes después)
        $fechaFinMax = $fechaInicio->copy()->addMonth();
        // Si fechaInicio es mayor que fechaFinMax, usar fechaInicio + 1 día
        if ($fechaInicio->greaterThanOrEqualTo($fechaFinMax)) {
            $fechaFin = $fechaInicio->copy()->addDay();
        } else {
            $fechaFin = \Carbon\Carbon::parse(fake()->dateTimeBetween($fechaInicio->toDateTime(), $fechaFinMax->toDateTime()));
        }
        $esTodoElDia = fake()->boolean(50);

        return [
            'titulo' => fake()->sentence(4),
            'descripcion' => fake()->optional()->paragraph(),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'hora_inicio' => $esTodoElDia ? null : fake()->time('H:i'),
            'hora_fin' => $esTodoElDia ? null : fake()->time('H:i', '+2 hours'),
            'es_todo_el_dia' => $esTodoElDia,
            'tipo' => fake()->randomElement(['general', 'formacion', 'retiro_espiritual', 'reunion_equipo']),
            'alcance' => fake()->randomElement(['equipo', 'global']),
            'equipo_id' => null, // Se asigna en estados específicos
            'creado_por' => User::factory(),
            'color' => fake()->hexColor(),
            'icono' => fake()->optional()->randomElement(['Calendar', 'Users', 'BookOpen', 'Church']),
        ];
    }

    /**
     * Evento de tipo general.
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'general',
            'color' => '#3b82f6',
            'icono' => 'Calendar',
        ]);
    }

    /**
     * Evento de tipo formación.
     */
    public function formacion(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'formacion',
            'color' => '#10b981',
            'icono' => 'BookOpen',
        ]);
    }

    /**
     * Evento de tipo retiro espiritual.
     */
    public function retiroEspiritual(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'retiro_espiritual',
            'color' => '#8b5cf6',
            'icono' => 'Church',
        ]);
    }

    /**
     * Evento de tipo reunión de equipo.
     */
    public function reunionEquipo(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'reunion_equipo',
            'color' => '#f59e0b',
            'icono' => 'Users',
        ]);
    }

    /**
     * Evento con alcance global.
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'alcance' => 'global',
            'equipo_id' => null,
        ]);
    }

    /**
     * Evento con alcance de equipo.
     */
    public function deEquipo(?int $equipoId = null): static
    {
        return $this->state(function (array $attributes) use ($equipoId) {
            $equipoIdFinal = $equipoId ?? Equipo::factory()->create()->id;

            return [
                'alcance' => 'equipo',
                'equipo_id' => $equipoIdFinal,
            ];
        });
    }

    /**
     * Evento de todo el día.
     */
    public function todoElDia(): static
    {
        return $this->state(fn (array $attributes) => [
            'es_todo_el_dia' => true,
            'hora_inicio' => null,
            'hora_fin' => null,
        ]);
    }
}
