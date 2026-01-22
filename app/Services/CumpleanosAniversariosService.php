<?php

namespace App\Services;

use App\Models\Pareja;
use App\Models\User;
use Carbon\Carbon;

class CumpleanosAniversariosService
{
    /**
     * Obtener la zona horaria configurada o usar la predeterminada.
     */
    protected function getTimezone(): string
    {
        return config('app.timezone', 'America/Bogota');
    }

    /**
     * Obtener aniversarios del mes especificado (boda y acogida).
     *
     * @param  int  $mes  Mes (1-12)
     * @param  int  $año  Año
     * @param  \App\Models\User  $usuario  Usuario que realiza la consulta
     * @param  int|null  $equipoId  ID del equipo para filtrar (opcional)
     * @return array<int, array<string, mixed>>
     */
    public function obtenerAniversariosDelMes(int $mes, int $año, User $usuario, ?int $equipoId = null): array
    {
        $aniversarios = [];

        // Obtener parejas activas
        $query = Pareja::query()
            ->where('estado', 'activo');

        // Filtrar por equipo si no es mango
        if (! $usuario->esMango() && $equipoId) {
            $query->where('equipo_id', $equipoId);
        }

        $parejas = $query->get();

        // Calcular años para el rango (considerar años bisiestos)
        $añoInicio = $año;
        $añoFin = $año;

        // Iterar sobre cada año del rango
        for ($añoActual = $añoInicio; $añoActual <= $añoFin; $añoActual++) {
            foreach ($parejas as $pareja) {
                // Aniversario de boda
                if ($pareja->fecha_boda) {
                    $diaBoda = (int) $pareja->fecha_boda->format('d');
                    $mesBoda = (int) $pareja->fecha_boda->format('m');

                    // Manejar 29 de febrero en años no bisiestos
                    if ($diaBoda === 29 && $mesBoda === 2 && ! checkdate(2, 29, $añoActual)) {
                        $diaBoda = 28;
                    }

                    if ($mesBoda === $mes) {
                        try {
                            $fechaAniversario = Carbon::create($añoActual, $mesBoda, $diaBoda, 0, 0, 0, $this->getTimezone());
                            $el = $pareja->el();
                            $ella = $pareja->ella();
                            $nombreEl = $el ? trim(($el->nombres ?? '').' '.($el->apellidos ?? '')) : '';
                            $nombreElla = $ella ? trim(($ella->nombres ?? '').' '.($ella->apellidos ?? '')) : '';
                            $nombrePareja = trim($nombreEl.' & '.$nombreElla) ?: 'Pareja sin nombre';

                            $aniversarios[] = [
                                'id' => 'aniversario_boda_'.$pareja->id.'_'.$añoActual,
                                'titulo' => 'Aniversario de Boda: '.$nombrePareja,
                                'fecha' => $fechaAniversario->format('Y-m-d'),
                                'tipo' => 'aniversario_boda',
                                'años' => $añoActual - ((int) $pareja->fecha_boda->format('Y')),
                                'pareja' => [
                                    'id' => $pareja->id,
                                    'el' => $el ? [
                                        'id' => $el->id,
                                        'nombres' => $el->nombres,
                                        'apellidos' => $el->apellidos,
                                        'email' => $el->email,
                                        'cedula' => $el->cedula,
                                        'celular' => $el->celular,
                                    ] : null,
                                    'ella' => $ella ? [
                                        'id' => $ella->id,
                                        'nombres' => $ella->nombres,
                                        'apellidos' => $ella->apellidos,
                                        'email' => $ella->email,
                                        'cedula' => $ella->cedula,
                                        'celular' => $ella->celular,
                                    ] : null,
                                ],
                            ];
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }

                // Aniversario de acogida
                if ($pareja->fecha_acogida) {
                    $diaAcogida = (int) $pareja->fecha_acogida->format('d');
                    $mesAcogida = (int) $pareja->fecha_acogida->format('m');

                    // Manejar 29 de febrero en años no bisiestos
                    if ($diaAcogida === 29 && $mesAcogida === 2 && ! checkdate(2, 29, $añoActual)) {
                        $diaAcogida = 28;
                    }

                    if ($mesAcogida === $mes) {
                        try {
                            $fechaAniversario = Carbon::create($añoActual, $mesAcogida, $diaAcogida, 0, 0, 0, $this->getTimezone());
                            $el = $pareja->el();
                            $ella = $pareja->ella();
                            $nombreEl = $el ? trim(($el->nombres ?? '').' '.($el->apellidos ?? '')) : '';
                            $nombreElla = $ella ? trim(($ella->nombres ?? '').' '.($ella->apellidos ?? '')) : '';
                            $nombrePareja = trim($nombreEl.' & '.$nombreElla) ?: 'Pareja sin nombre';

                            $aniversarios[] = [
                                'id' => 'aniversario_acogida_'.$pareja->id.'_'.$añoActual,
                                'titulo' => 'Aniversario de Acogida: '.$nombrePareja,
                                'fecha' => $fechaAniversario->format('Y-m-d'),
                                'tipo' => 'aniversario_acogida',
                                'años' => $añoActual - ((int) $pareja->fecha_acogida->format('Y')),
                                'pareja' => [
                                    'id' => $pareja->id,
                                    'el' => $el ? [
                                        'id' => $el->id,
                                        'nombres' => $el->nombres,
                                        'apellidos' => $el->apellidos,
                                        'email' => $el->email,
                                        'cedula' => $el->cedula,
                                        'celular' => $el->celular,
                                    ] : null,
                                    'ella' => $ella ? [
                                        'id' => $ella->id,
                                        'nombres' => $ella->nombres,
                                        'apellidos' => $ella->apellidos,
                                        'email' => $ella->email,
                                        'cedula' => $ella->cedula,
                                        'celular' => $ella->celular,
                                    ] : null,
                                ],
                            ];
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }
        }

        // Ordenar por fecha
        usort($aniversarios, function ($a, $b) {
            return strcmp($a['fecha'], $b['fecha']);
        });

        return $aniversarios;
    }

    /**
     * Obtener próximos aniversarios en los próximos N días.
     *
     * @param  int  $dias  Número de días a buscar
     * @param  \App\Models\User  $usuario  Usuario que realiza la consulta
     * @param  int|null  $equipoId  ID del equipo para filtrar (opcional)
     * @return array<int, array<string, mixed>>
     */
    public function obtenerProximosAniversarios(int $dias, User $usuario, ?int $equipoId = null): array
    {
        $hoy = Carbon::now($this->getTimezone())->startOfDay();
        $fechaFin = $hoy->copy()->addDays($dias);

        $aniversarios = [];

        // Obtener parejas activas
        $query = Pareja::query()
            ->where('estado', 'activo');

        // Filtrar por equipo si no es mango
        if (! $usuario->esMango() && $equipoId) {
            $query->where('equipo_id', $equipoId);
        }

        $parejas = $query->get();

        // Calcular años para el rango
        $añoInicio = (int) $hoy->format('Y');
        $añoFin = (int) $fechaFin->format('Y');

        // Iterar sobre cada año del rango
        for ($año = $añoInicio; $año <= $añoFin; $año++) {
            foreach ($parejas as $pareja) {
                // Aniversario de boda
                if ($pareja->fecha_boda) {
                    $diaBoda = (int) $pareja->fecha_boda->format('d');
                    $mesBoda = (int) $pareja->fecha_boda->format('m');

                    // Manejar 29 de febrero en años no bisiestos
                    if ($diaBoda === 29 && $mesBoda === 2 && ! checkdate(2, 29, $año)) {
                        $diaBoda = 28;
                    }

                    try {
                        $fechaAniversario = Carbon::create($año, $mesBoda, $diaBoda, 0, 0, 0, $this->getTimezone());

                        // Verificar si está en el rango
                        if ($fechaAniversario->gte($hoy) && $fechaAniversario->lte($fechaFin)) {
                            $el = $pareja->el();
                            $ella = $pareja->ella();
                            $nombreEl = $el ? trim(($el->nombres ?? '').' '.($el->apellidos ?? '')) : '';
                            $nombreElla = $ella ? trim(($ella->nombres ?? '').' '.($ella->apellidos ?? '')) : '';
                            $nombrePareja = trim($nombreEl.' & '.$nombreElla) ?: 'Pareja sin nombre';

                            $aniversarios[] = [
                                'id' => 'aniversario_boda_'.$pareja->id.'_'.$año,
                                'titulo' => 'Aniversario de Boda: '.$nombrePareja,
                                'fecha' => $fechaAniversario->format('Y-m-d'),
                                'fecha_cumpleanos' => $fechaAniversario->format('Y-m-d'),
                                'start' => $fechaAniversario->format('Y-m-d'),
                                'tipo' => 'aniversario_boda',
                                'años' => $año - ((int) $pareja->fecha_boda->format('Y')),
                                'dias_restantes' => $hoy->diffInDays($fechaAniversario, false),
                                'pareja' => [
                                    'id' => $pareja->id,
                                    'el' => $el ? [
                                        'id' => $el->id,
                                        'nombres' => $el->nombres,
                                        'apellidos' => $el->apellidos,
                                        'email' => $el->email,
                                        'cedula' => $el->cedula,
                                        'celular' => $el->celular,
                                    ] : null,
                                    'ella' => $ella ? [
                                        'id' => $ella->id,
                                        'nombres' => $ella->nombres,
                                        'apellidos' => $ella->apellidos,
                                        'email' => $ella->email,
                                        'cedula' => $ella->cedula,
                                        'celular' => $ella->celular,
                                    ] : null,
                                ],
                            ];
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                // Aniversario de acogida
                if ($pareja->fecha_acogida) {
                    $diaAcogida = (int) $pareja->fecha_acogida->format('d');
                    $mesAcogida = (int) $pareja->fecha_acogida->format('m');

                    // Manejar 29 de febrero en años no bisiestos
                    if ($diaAcogida === 29 && $mesAcogida === 2 && ! checkdate(2, 29, $año)) {
                        $diaAcogida = 28;
                    }

                    try {
                        $fechaAniversario = Carbon::create($año, $mesAcogida, $diaAcogida, 0, 0, 0, $this->getTimezone());

                        // Verificar si está en el rango
                        if ($fechaAniversario->gte($hoy) && $fechaAniversario->lte($fechaFin)) {
                            $el = $pareja->el();
                            $ella = $pareja->ella();
                            $nombreEl = $el ? trim(($el->nombres ?? '').' '.($el->apellidos ?? '')) : '';
                            $nombreElla = $ella ? trim(($ella->nombres ?? '').' '.($ella->apellidos ?? '')) : '';
                            $nombrePareja = trim($nombreEl.' & '.$nombreElla) ?: 'Pareja sin nombre';

                            $aniversarios[] = [
                                'id' => 'aniversario_acogida_'.$pareja->id.'_'.$año,
                                'titulo' => 'Aniversario de Acogida: '.$nombrePareja,
                                'fecha' => $fechaAniversario->format('Y-m-d'),
                                'fecha_cumpleanos' => $fechaAniversario->format('Y-m-d'),
                                'start' => $fechaAniversario->format('Y-m-d'),
                                'tipo' => 'aniversario_acogida',
                                'años' => $año - ((int) $pareja->fecha_acogida->format('Y')),
                                'dias_restantes' => $hoy->diffInDays($fechaAniversario, false),
                                'pareja' => [
                                    'id' => $pareja->id,
                                    'el' => $el ? [
                                        'id' => $el->id,
                                        'nombres' => $el->nombres,
                                        'apellidos' => $el->apellidos,
                                        'email' => $el->email,
                                        'cedula' => $el->cedula,
                                        'celular' => $el->celular,
                                    ] : null,
                                    'ella' => $ella ? [
                                        'id' => $ella->id,
                                        'nombres' => $ella->nombres,
                                        'apellidos' => $ella->apellidos,
                                        'email' => $ella->email,
                                        'cedula' => $ella->cedula,
                                        'celular' => $ella->celular,
                                    ] : null,
                                ],
                            ];
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        // Ordenar por fecha
        usort($aniversarios, function ($a, $b) {
            return strcmp($a['fecha'], $b['fecha']);
        });

        return $aniversarios;
    }

    /**
     * Obtener próximos eventos (cumpleaños y aniversarios) en los próximos N días.
     *
     * @param  int  $dias  Número de días a buscar
     * @param  \App\Models\User  $usuario  Usuario que realiza la consulta
     * @param  int|null  $equipoId  ID del equipo para filtrar (opcional)
     * @return array<int, array<string, mixed>>
     */
    public function obtenerProximosEventos(int $dias, User $usuario, ?int $equipoId = null): array
    {
        $cumpleanosService = new CumpleanosService;
        $cumpleanos = $cumpleanosService->obtenerProximosCumpleanos($dias, $usuario, $equipoId);
        $aniversarios = $this->obtenerProximosAniversarios($dias, $usuario, $equipoId);

        // Combinar y ordenar por fecha
        $eventos = array_merge($cumpleanos, $aniversarios);
        usort($eventos, function ($a, $b) {
            $fechaA = $a['start'] ?? $a['fecha'] ?? '';
            $fechaB = $b['start'] ?? $b['fecha'] ?? '';

            return strcmp($fechaA, $fechaB);
        });

        return $eventos;
    }
}
