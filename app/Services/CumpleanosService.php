<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class CumpleanosService
{
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

        // Obtener usuarios con fecha_nacimiento
        $usuarios = User::query()
            ->whereNotNull('fecha_nacimiento')
            ->with('pareja')
            ->get();

        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);

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
                    $fechaCumpleanos = Carbon::create($año, $mesNacimiento, $diaNacimiento);
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
}
