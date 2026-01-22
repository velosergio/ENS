<?php

namespace App\Services;

use App\Models\Pareja;
use App\Models\User;
use Carbon\Carbon;

class CumpleanosService
{
    /**
     * Obtener la zona horaria configurada o usar la predeterminada.
     */
    protected function getTimezone(): string
    {
        return config('app.timezone', 'America/Bogota');
    }
    /**
     * Obtener cumpleaños de usuarios en un rango de fechas y formatearlos para FullCalendar.
     *
     * @param  string  $fechaInicio  Fecha inicio en formato YYYY-MM-DD
     * @param  string  $fechaFin  Fecha fin en formato YYYY-MM-DD
     * @param  array<string, array{color: string, icono: string|null}>  $configuracion  Configuración de color e icono para cumpleaños
     * @return array<int, array<string, mixed>> Array de eventos formateados para FullCalendar
     */
    public function obtenerCumpleanosEnRango(string $fechaInicio, string $fechaFin, array $configuracion = []): array
    {
        $cumpleanos = [];

        // Obtener usuarios con fecha_nacimiento y filtrar por parejas activas
        $usuarios = User::query()
            ->whereNotNull('fecha_nacimiento')
            ->whereHas('pareja', function ($query) {
                $query->where('estado', 'activo');
            })
            ->with('pareja')
            ->get();

        $inicio = Carbon::parse($fechaInicio)->setTimezone($this->getTimezone());
        $fin = Carbon::parse($fechaFin)->setTimezone($this->getTimezone());

        // Calcular años para el rango (considerar años bisiestos)
        $añoInicio = (int) $inicio->format('Y');
        $añoFin = (int) $fin->format('Y');

        // Iterar sobre cada año del rango
        for ($año = $añoInicio; $año <= $añoFin; $año++) {
            foreach ($usuarios as $usuario) {
                if (! $usuario->fecha_nacimiento) {
                    continue;
                }

                // Obtener día y mes del cumpleaños
                $diaNacimiento = (int) $usuario->fecha_nacimiento->format('d');
                $mesNacimiento = (int) $usuario->fecha_nacimiento->format('m');

                // Manejar cumpleaños del 29 de febrero en años no bisiestos
                // En años no bisiestos, mostrar el cumpleaños el 28 de febrero
                if ($diaNacimiento === 29 && $mesNacimiento === 2 && ! checkdate(2, 29, $año)) {
                    $diaNacimiento = 28;
                }

                // Crear fecha del cumpleaños en el año actual
                try {
                    $fechaCumpleanos = Carbon::create($año, $mesNacimiento, $diaNacimiento, 0, 0, 0, $this->getTimezone());
                } catch (\Exception $e) {
                    // Si no se puede crear la fecha (casos edge), saltar este usuario
                    continue;
                }

                // Verificar si el cumpleaños está en el rango
                if ($fechaCumpleanos->gte($inicio) && $fechaCumpleanos->lte($fin)) {
                    $nombreCompleto = trim(($usuario->nombres ?? '').' '.($usuario->apellidos ?? ''));

                    if (empty($nombreCompleto)) {
                        $nombreCompleto = $usuario->email;
                    }

                    $color = $configuracion['color'] ?? '#ec4899'; // Rosa por defecto
                    $icono = $configuracion['icono'] ?? 'Cake';

                    $cumpleanos[] = [
                        'id' => 'cumpleanos_'.$usuario->id.'_'.$fechaCumpleanos->format('Y'),
                        'title' => 'Cumpleaños de '.$nombreCompleto,
                        'start' => $fechaCumpleanos->format('Y-m-d'),
                        'end' => $fechaCumpleanos->copy()->addDay()->format('Y-m-d'), // FullCalendar requiere fecha exclusiva para todo el día
                        'allDay' => true,
                        'backgroundColor' => $color,
                        'borderColor' => $color,
                        'textColor' => '#ffffff',
                        'extendedProps' => [
                            'tipo' => 'cumpleanos',
                            'alcance' => 'global',
                            'equipo_id' => null,
                            'icono' => $icono,
                            'usuario' => [
                                'id' => $usuario->id,
                                'nombres' => $usuario->nombres,
                                'apellidos' => $usuario->apellidos,
                                'email' => $usuario->email,
                                'fecha_nacimiento' => $usuario->fecha_nacimiento->format('Y-m-d'),
                                'año_nacimiento' => (int) $usuario->fecha_nacimiento->format('Y'),
                            ],
                            'año_cumpleanos' => $año,
                            'edad' => $año - ((int) $usuario->fecha_nacimiento->format('Y')),
                        ],
                        'puede_editar' => false, // Los cumpleaños no son editables
                        'puede_eliminar' => false, // Los cumpleaños no son eliminables
                    ];
                }
            }
        }

        return $cumpleanos;
    }

    /**
     * Obtener cumpleaños del mes especificado.
     *
     * @param  int  $mes  Mes (1-12)
     * @param  int  $año  Año
     * @param  User  $usuario  Usuario que realiza la consulta
     * @param  int|null  $equipoId  ID del equipo para filtrar (opcional)
     * @return array<int, array<string, mixed>> Array de cumpleaños formateados
     */
    public function obtenerCumpleanosDelMes(int $mes, int $año, User $usuario, ?int $equipoId = null): array
    {
        // Obtener rango de fechas del mes
        $fechaInicio = Carbon::create($año, $mes, 1, 0, 0, 0, $this->getTimezone())->startOfMonth();
        $fechaFin = Carbon::create($año, $mes, 1, 0, 0, 0, $this->getTimezone())->endOfMonth();

        $query = User::query()
            ->whereNotNull('fecha_nacimiento')
            ->whereHas('pareja', function ($q) {
                $q->where('estado', 'activo');
            })
            ->with('pareja.equipo');

        // Filtrar por equipo si no es mango
        if (! $usuario->esMango()) {
            $equipoUsuario = $usuario->equipo()?->id;
            if ($equipoUsuario) {
                $query->whereHas('pareja', function ($q) use ($equipoUsuario) {
                    $q->where('equipo_id', $equipoUsuario);
                });
            } else {
                // Si no tiene equipo, retornar array vacío
                return [];
            }
        } elseif ($equipoId) {
            // Si es mango y se especifica un equipo, filtrar por ese equipo
            $query->whereHas('pareja', function ($q) use ($equipoId) {
                $q->where('equipo_id', $equipoId);
            });
        }

        $usuarios = $query->get();
        $cumpleanos = [];

        foreach ($usuarios as $usuarioData) {
            if (! $usuarioData->fecha_nacimiento) {
                continue;
            }

            $diaNacimiento = (int) $usuarioData->fecha_nacimiento->format('d');
            $mesNacimiento = (int) $usuarioData->fecha_nacimiento->format('m');

            // Manejar 29 de febrero en años no bisiestos
            if ($diaNacimiento === 29 && $mesNacimiento === 2 && ! checkdate(2, 29, $año)) {
                $diaNacimiento = 28;
            }

            // Solo incluir si el mes coincide
            if ($mesNacimiento !== $mes) {
                continue;
            }

            try {
                $fechaCumpleanos = Carbon::create($año, $mesNacimiento, $diaNacimiento, 0, 0, 0, $this->getTimezone());
            } catch (\Exception $e) {
                continue;
            }

            $nombreCompleto = trim(($usuarioData->nombres ?? '').' '.($usuarioData->apellidos ?? ''));

            if (empty($nombreCompleto)) {
                $nombreCompleto = $usuarioData->email;
            }

            $cumpleanos[] = [
                'id' => $usuarioData->id,
                'usuario_id' => $usuarioData->id,
                'nombre' => $nombreCompleto,
                'nombres' => $usuarioData->nombres,
                'apellidos' => $usuarioData->apellidos,
                'email' => $usuarioData->email,
                'celular' => $usuarioData->celular,
                'fecha_nacimiento' => $usuarioData->fecha_nacimiento->format('Y-m-d'),
                'dia' => $diaNacimiento,
                'mes' => $mesNacimiento,
                'año_nacimiento' => (int) $usuarioData->fecha_nacimiento->format('Y'),
                'edad' => $año - ((int) $usuarioData->fecha_nacimiento->format('Y')),
                'fecha_cumpleanos' => $fechaCumpleanos->format('Y-m-d'),
                'equipo_id' => $usuarioData->pareja?->equipo_id,
                'equipo_numero' => $usuarioData->pareja?->equipo?->numero,
            ];
        }

        // Ordenar por día del mes
        usort($cumpleanos, function ($a, $b) {
            return $a['dia'] <=> $b['dia'];
        });

        return $cumpleanos;
    }

    /**
     * Obtener próximos cumpleaños en los próximos N días.
     *
     * @param  int  $dias  Número de días a buscar hacia adelante
     * @param  User  $usuario  Usuario que realiza la consulta
     * @param  int|null  $equipoId  ID del equipo para filtrar (opcional)
     * @return array<int, array<string, mixed>> Array de cumpleaños formateados
     */
    public function obtenerProximosCumpleanos(int $dias, User $usuario, ?int $equipoId = null): array
    {
        $hoy = Carbon::now($this->getTimezone())->startOfDay();
        $fechaFin = $hoy->copy()->addDays($dias);

        $query = User::query()
            ->whereNotNull('fecha_nacimiento')
            ->whereHas('pareja', function ($q) {
                $q->where('estado', 'activo');
            })
            ->with('pareja.equipo');

        // Filtrar por equipo si no es mango
        if (! $usuario->esMango()) {
            $equipoUsuario = $usuario->equipo()?->id;
            if ($equipoUsuario) {
                $query->whereHas('pareja', function ($q) use ($equipoUsuario) {
                    $q->where('equipo_id', $equipoUsuario);
                });
            } else {
                return [];
            }
        } elseif ($equipoId) {
            $query->whereHas('pareja', function ($q) use ($equipoId) {
                $q->where('equipo_id', $equipoId);
            });
        }

        $usuarios = $query->get();
        $cumpleanos = [];
        $añoActual = (int) $hoy->format('Y');
        $añoSiguiente = $añoActual + 1;

        // Buscar en el año actual y el siguiente (por si el rango cruza año nuevo)
        foreach ([$añoActual, $añoSiguiente] as $año) {
            foreach ($usuarios as $usuarioData) {
                if (! $usuarioData->fecha_nacimiento) {
                    continue;
                }

                $diaNacimiento = (int) $usuarioData->fecha_nacimiento->format('d');
                $mesNacimiento = (int) $usuarioData->fecha_nacimiento->format('m');

                // Manejar 29 de febrero
                if ($diaNacimiento === 29 && $mesNacimiento === 2 && ! checkdate(2, 29, $año)) {
                    $diaNacimiento = 28;
                }

                try {
                    $fechaCumpleanos = Carbon::create($año, $mesNacimiento, $diaNacimiento, 0, 0, 0, $this->getTimezone());
                } catch (\Exception $e) {
                    continue;
                }

                // Verificar si está en el rango (incluir hoy)
                if ($fechaCumpleanos->gte($hoy) && $fechaCumpleanos->lte($fechaFin)) {
                    $nombreCompleto = trim(($usuarioData->nombres ?? '').' '.($usuarioData->apellidos ?? ''));

                    if (empty($nombreCompleto)) {
                        $nombreCompleto = $usuarioData->email;
                    }

                    $cumpleanos[] = [
                        'id' => 'cumpleanos_'.$usuarioData->id.'_'.$año,
                        'usuario_id' => $usuarioData->id,
                        'nombre' => $nombreCompleto,
                        'nombres' => $usuarioData->nombres,
                        'apellidos' => $usuarioData->apellidos,
                        'email' => $usuarioData->email,
                        'celular' => $usuarioData->celular,
                        'fecha_nacimiento' => $usuarioData->fecha_nacimiento->format('Y-m-d'),
                        'fecha_cumpleanos' => $fechaCumpleanos->format('Y-m-d'),
                        'edad' => $año - ((int) $usuarioData->fecha_nacimiento->format('Y')),
                        'dias_restantes' => $hoy->diffInDays($fechaCumpleanos, false),
                        'equipo_id' => $usuarioData->pareja?->equipo_id,
                        'equipo_numero' => $usuarioData->pareja?->equipo?->numero,
                        'tipo' => 'cumpleanos',
                    ];
                }
            }
        }

        // Ordenar por fecha (cronológico)
        usort($cumpleanos, function ($a, $b) {
            return strcmp($a['fecha_cumpleanos'], $b['fecha_cumpleanos']);
        });

        return $cumpleanos;
    }
}
