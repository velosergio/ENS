<?php

namespace Database\Seeders;

use App\Models\Equipo;
use App\Models\EventoCalendario;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventosSeeder extends Seeder
{
    /**
     * Límite máximo de eventos por día.
     */
    protected const MAX_EVENTOS_POR_DIA = 4;

    /**
     * Crear eventos de formación, retiros espirituales y reuniones de equipo.
     */
    public function run(): void
    {
        $this->command->info('Creando eventos de calendario...');

        // Obtener usuarios y equipos existentes
        $usuarios = User::all();
        $equipos = Equipo::all();

        if ($usuarios->isEmpty()) {
            $this->command->warn('No hay usuarios en la base de datos. Creando un usuario mango...');
            $usuarioMango = User::factory()->mango()->create([
                'email' => 'mango@example.com',
                'nombres' => 'Usuario',
                'apellidos' => 'Mango',
            ]);
            $usuarios = collect([$usuarioMango]);
        }

        if ($equipos->isEmpty()) {
            $this->command->warn('No hay equipos en la base de datos. Creando 5 equipos...');
            $equipos = Equipo::factory(5)->create();
        }

        $creador = $usuarios->first();

        // Eventos de Formación
        $this->command->info('Creando eventos de formación...');
        $this->crearEventosFormacion($creador, $equipos);

        // Retiros Espirituales
        $this->command->info('Creando retiros espirituales...');
        $this->crearRetirosEspirituales($creador, $equipos);

        // Reuniones de Equipo
        $this->command->info('Creando reuniones de equipo...');
        $this->crearReunionesEquipo($creador, $equipos);

        $this->command->info('¡Eventos creados exitosamente!');
    }

    /**
     * Verificar si se puede crear un evento en la fecha especificada.
     * Retorna true si hay menos de MAX_EVENTOS_POR_DIA eventos ese día.
     */
    protected function puedeCrearEventoEnFecha(Carbon $fecha): bool
    {
        $fechaInicio = $fecha->copy()->startOfDay();
        $fechaFin = $fecha->copy()->endOfDay();

        $eventosExistentes = EventoCalendario::where(function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
                ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                    $q->where('fecha_inicio', '<=', $fechaInicio)
                        ->where('fecha_fin', '>=', $fechaFin);
                });
        })->count();

        return $eventosExistentes < self::MAX_EVENTOS_POR_DIA;
    }

    /**
     * Buscar una fecha alternativa cercana donde se pueda crear el evento.
     */
    protected function buscarFechaAlternativa(Carbon $fechaOriginal, int $intentosMaximos = 30): ?Carbon
    {
        $fecha = $fechaOriginal->copy();
        $intentos = 0;

        while ($intentos < $intentosMaximos) {
            if ($this->puedeCrearEventoEnFecha($fecha)) {
                return $fecha;
            }

            // Intentar el día siguiente
            $fecha->addDay();
            $intentos++;
        }

        return null;
    }

    /**
     * Crear eventos de formación.
     */
    protected function crearEventosFormacion(User $creador, $equipos): void
    {
        $eventosFormacion = [
            [
                'titulo' => 'Taller de Comunicación en Pareja',
                'descripcion' => 'Taller práctico sobre técnicas de comunicación efectiva en la relación de pareja.',
                'fecha_inicio' => Carbon::now()->addWeeks(2)->next(Carbon::FRIDAY)->setTime(18, 0),
                'fecha_fin' => Carbon::now()->addWeeks(2)->next(Carbon::FRIDAY)->setTime(21, 0),
                'alcance' => 'global',
            ],
            [
                'titulo' => 'Curso de Liderazgo Cristiano',
                'descripcion' => 'Formación en principios bíblicos de liderazgo y servicio.',
                'fecha_inicio' => Carbon::now()->addMonths(1)->startOfMonth()->next(Carbon::SATURDAY)->setTime(9, 0),
                'fecha_fin' => Carbon::now()->addMonths(1)->startOfMonth()->next(Carbon::SATURDAY)->setTime(17, 0),
                'alcance' => 'global',
            ],
            [
                'titulo' => 'Seminario de Finanzas Familiares',
                'descripcion' => 'Aprende a manejar las finanzas familiares desde una perspectiva cristiana.',
                'fecha_inicio' => Carbon::now()->addMonths(2)->startOfMonth()->addDays(9)->setTime(19, 0),
                'fecha_fin' => Carbon::now()->addMonths(2)->startOfMonth()->addDays(9)->setTime(21, 30),
                'alcance' => 'global',
            ],
            [
                'titulo' => 'Taller de Resolución de Conflictos',
                'descripcion' => 'Herramientas prácticas para resolver conflictos en la pareja y familia.',
                'fecha_inicio' => Carbon::now()->addWeeks(3)->next(Carbon::WEDNESDAY)->setTime(18, 30),
                'fecha_fin' => Carbon::now()->addWeeks(3)->next(Carbon::WEDNESDAY)->setTime(20, 30),
                'alcance' => 'equipo',
                'equipo_id' => $equipos->random()->id,
            ],
            [
                'titulo' => 'Formación en Valores Cristianos',
                'descripcion' => 'Sesión formativa sobre valores y principios cristianos para la familia.',
                'fecha_inicio' => Carbon::now()->addMonths(1)->startOfMonth()->addDays(14)->setTime(10, 0),
                'fecha_fin' => Carbon::now()->addMonths(1)->startOfMonth()->addDays(14)->setTime(13, 0),
                'alcance' => 'equipo',
                'equipo_id' => $equipos->random()->id,
            ],
        ];

        foreach ($eventosFormacion as $evento) {
            $fechaInicio = $evento['fecha_inicio'];

            // Verificar si se puede crear en la fecha original
            if (! $this->puedeCrearEventoEnFecha($fechaInicio)) {
                // Buscar fecha alternativa
                $fechaAlternativa = $this->buscarFechaAlternativa($fechaInicio);
                if ($fechaAlternativa) {
                    $fechaInicio = $fechaAlternativa->copy()->setTime(
                        $evento['fecha_inicio']->hour,
                        $evento['fecha_inicio']->minute
                    );
                    $this->command->warn("Evento '{$evento['titulo']}' movido a {$fechaInicio->format('Y-m-d')} (fecha original ocupada)");
                } else {
                    $this->command->warn("No se pudo crear el evento '{$evento['titulo']}' - no hay fechas disponibles cercanas");

                    continue;
                }
            }

            EventoCalendario::factory()
                ->formacion()
                ->create([
                    'titulo' => $evento['titulo'],
                    'descripcion' => $evento['descripcion'],
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaInicio->copy()->addHours(3),
                    'hora_inicio' => $fechaInicio->format('H:i'),
                    'hora_fin' => $fechaInicio->copy()->addHours(3)->format('H:i'),
                    'es_todo_el_dia' => false,
                    'alcance' => $evento['alcance'],
                    'equipo_id' => $evento['alcance'] === 'equipo' ? $evento['equipo_id'] : null,
                    'creado_por' => $creador->id,
                ]);
        }
    }

    /**
     * Crear retiros espirituales.
     */
    protected function crearRetirosEspirituales(User $creador, $equipos): void
    {
        $retiros = [
            [
                'titulo' => 'Retiro de Parejas - Renovación Espiritual',
                'descripcion' => 'Retiro de fin de semana para parejas. Tiempo de reflexión, oración y renovación espiritual.',
                'fecha_inicio' => Carbon::now()->addMonths(1)->next(Carbon::FRIDAY)->setTime(18, 0),
                'fecha_fin' => Carbon::now()->addMonths(1)->next(Carbon::SUNDAY)->setTime(14, 0),
                'alcance' => 'global',
            ],
            [
                'titulo' => 'Retiro de Oración y Ayuno',
                'descripcion' => 'Día de oración, ayuno y búsqueda de Dios para toda la comunidad.',
                'fecha_inicio' => Carbon::now()->addWeeks(4)->startOfWeek(Carbon::MONDAY)->setTime(0, 0),
                'fecha_fin' => Carbon::now()->addWeeks(4)->startOfWeek(Carbon::MONDAY)->setTime(23, 59),
                'alcance' => 'global',
            ],
            [
                'titulo' => 'Retiro de Cuaresma',
                'descripcion' => 'Retiro especial de Cuaresma para preparar nuestros corazones para la Pascua.',
                'fecha_inicio' => Carbon::now()->addMonths(2)->startOfMonth()->addDays(14)->setTime(8, 0),
                'fecha_fin' => Carbon::now()->addMonths(2)->startOfMonth()->addDays(15)->setTime(18, 0),
                'alcance' => 'global',
            ],
            [
                'titulo' => 'Retiro de Equipos',
                'descripcion' => 'Retiro espiritual para fortalecer la comunión y el servicio en equipo.',
                'fecha_inicio' => Carbon::now()->addMonths(3)->next(Carbon::SATURDAY)->setTime(9, 0),
                'fecha_fin' => Carbon::now()->addMonths(3)->next(Carbon::SATURDAY)->setTime(17, 0),
                'alcance' => 'equipo',
                'equipo_id' => $equipos->random()->id,
            ],
        ];

        foreach ($retiros as $retiro) {
            $fechaInicio = $retiro['fecha_inicio'];
            $fechaFin = $retiro['fecha_fin'];
            $esTodoElDia = $fechaInicio->format('H:i') === '00:00' && $fechaFin->format('H:i') === '23:59';

            // Para eventos de varios días, verificar cada día
            $diasRetiro = $fechaInicio->copy()->startOfDay()->diffInDays($fechaFin->copy()->endOfDay()) + 1;
            $puedeCrear = true;
            $fechaVerificar = $fechaInicio->copy()->startOfDay();

            for ($dia = 0; $dia < $diasRetiro; $dia++) {
                if (! $this->puedeCrearEventoEnFecha($fechaVerificar->copy()->addDays($dia))) {
                    $puedeCrear = false;
                    break;
                }
            }

            if (! $puedeCrear) {
                // Buscar fecha alternativa para el inicio del retiro
                $fechaAlternativa = $this->buscarFechaAlternativa($fechaInicio);
                if ($fechaAlternativa) {
                    $diferenciaDias = $fechaInicio->diffInDays($fechaFin);
                    $fechaInicio = $fechaAlternativa->copy()->setTime(
                        $retiro['fecha_inicio']->hour,
                        $retiro['fecha_inicio']->minute
                    );
                    $fechaFin = $fechaInicio->copy()->addDays($diferenciaDias)->setTime(
                        $retiro['fecha_fin']->hour,
                        $retiro['fecha_fin']->minute
                    );
                    $this->command->warn("Retiro '{$retiro['titulo']}' movido a {$fechaInicio->format('Y-m-d')} (fecha original ocupada)");
                } else {
                    $this->command->warn("No se pudo crear el retiro '{$retiro['titulo']}' - no hay fechas disponibles cercanas");

                    continue;
                }
            }

            EventoCalendario::factory()
                ->retiroEspiritual()
                ->create([
                    'titulo' => $retiro['titulo'],
                    'descripcion' => $retiro['descripcion'],
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'hora_inicio' => $esTodoElDia ? null : $fechaInicio->format('H:i'),
                    'hora_fin' => $esTodoElDia ? null : $fechaFin->format('H:i'),
                    'es_todo_el_dia' => $esTodoElDia,
                    'alcance' => $retiro['alcance'],
                    'equipo_id' => $retiro['alcance'] === 'equipo' ? $retiro['equipo_id'] : null,
                    'creado_por' => $creador->id,
                ]);
        }
    }

    /**
     * Crear reuniones de equipo.
     */
    protected function crearReunionesEquipo(User $creador, $equipos): void
    {
        // Crear reuniones mensuales para cada equipo
        foreach ($equipos as $equipo) {
            // Reunión mensual regular (primer sábado de cada mes)
            for ($mes = 0; $mes < 6; $mes++) {
                $fechaReunion = Carbon::now()
                    ->addMonths($mes)
                    ->startOfMonth()
                    ->next(Carbon::SATURDAY)
                    ->setTime(19, 0);

                // Verificar si se puede crear en la fecha original
                if (! $this->puedeCrearEventoEnFecha($fechaReunion)) {
                    // Buscar fecha alternativa (siguiente sábado)
                    $fechaAlternativa = $this->buscarFechaAlternativa($fechaReunion);
                    if ($fechaAlternativa) {
                        $fechaReunion = $fechaAlternativa->copy()->setTime(19, 0);
                        $this->command->warn("Reunión mensual Equipo {$equipo->numero} movida a {$fechaReunion->format('Y-m-d')} (fecha original ocupada)");
                    } else {
                        $this->command->warn("No se pudo crear la reunión mensual del Equipo {$equipo->numero} - no hay fechas disponibles");

                        continue;
                    }
                }

                EventoCalendario::factory()
                    ->reunionEquipo()
                    ->deEquipo($equipo->id)
                    ->create([
                        'titulo' => "Reunión Mensual - Equipo {$equipo->numero}",
                        'descripcion' => "Reunión mensual del equipo {$equipo->numero} para compartir, orar y planificar actividades.",
                        'fecha_inicio' => $fechaReunion,
                        'fecha_fin' => $fechaReunion->copy()->addHours(2),
                        'hora_inicio' => '19:00',
                        'hora_fin' => '21:00',
                        'es_todo_el_dia' => false,
                        'creado_por' => $creador->id,
                    ]);
            }

            // Reunión especial de planificación (una vez cada 3 meses)
            for ($trimestre = 0; $trimestre < 2; $trimestre++) {
                $fechaPlanificacion = Carbon::now()
                    ->addMonths($trimestre * 3)
                    ->startOfMonth()
                    ->next(Carbon::SATURDAY)
                    ->addDays(7) // Segundo sábado
                    ->setTime(9, 0);

                // Verificar si se puede crear en la fecha original
                if (! $this->puedeCrearEventoEnFecha($fechaPlanificacion)) {
                    // Buscar fecha alternativa (siguiente sábado)
                    $fechaAlternativa = $this->buscarFechaAlternativa($fechaPlanificacion);
                    if ($fechaAlternativa) {
                        $fechaPlanificacion = $fechaAlternativa->copy()->setTime(9, 0);
                        $this->command->warn("Reunión de planificación Equipo {$equipo->numero} movida a {$fechaPlanificacion->format('Y-m-d')} (fecha original ocupada)");
                    } else {
                        $this->command->warn("No se pudo crear la reunión de planificación del Equipo {$equipo->numero} - no hay fechas disponibles");

                        continue;
                    }
                }

                EventoCalendario::factory()
                    ->reunionEquipo()
                    ->deEquipo($equipo->id)
                    ->create([
                        'titulo' => "Reunión de Planificación - Equipo {$equipo->numero}",
                        'descripcion' => "Reunión trimestral de planificación y evaluación del equipo {$equipo->numero}.",
                        'fecha_inicio' => $fechaPlanificacion,
                        'fecha_fin' => $fechaPlanificacion->copy()->addHours(3),
                        'hora_inicio' => '09:00',
                        'hora_fin' => '12:00',
                        'es_todo_el_dia' => false,
                        'creado_por' => $creador->id,
                    ]);
            }
        }
    }
}
