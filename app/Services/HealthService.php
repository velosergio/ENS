<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class HealthService
{
    public function __construct() {}

    /**
     * Verificar extensiones PHP críticas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function verificarExtensionesPHP(): array
    {
        // Verificar primero qué extensión PDO está instalada
        $pdoMysqlInstalada = extension_loaded('pdo_mysql');
        $pdoMariadbInstalada = extension_loaded('pdo_mariadb');

        // Determinar qué extensión PDO mostrar
        // pdo_mysql funciona para MySQL y MariaDB, pdo_mariadb es opcional
        $extensiones = [
            // Mostrar pdo_mysql si está instalada, o pdo_mariadb si solo esa está instalada
            $pdoMysqlInstalada ? 'pdo_mysql' : ($pdoMariadbInstalada ? 'pdo_mariadb' : 'pdo_mysql'),
            'json',
            'mbstring',
            'fileinfo',
            'curl',
            'openssl',
        ];

        // Si ambas están instaladas, mostrar ambas pero marcar pdo_mariadb como opcional
        if ($pdoMysqlInstalada && $pdoMariadbInstalada) {
            $extensiones = [
                'pdo_mysql',
                'pdo_mariadb',
                'json',
                'mbstring',
                'fileinfo',
                'curl',
                'openssl',
            ];
        }

        $resultado = [];

        foreach ($extensiones as $extension) {
            $instalada = extension_loaded($extension);
            $version = null;
            $opcional = false;

            // Marcar pdo_mariadb como opcional si pdo_mysql también está instalada
            if ($extension === 'pdo_mariadb' && $pdoMysqlInstalada) {
                $opcional = true;
            }

            if ($instalada) {
                // Intentar obtener versión si está disponible
                if (function_exists($extension.'_version')) {
                    $versionRaw = call_user_func($extension.'_version');

                    // curl_version() retorna un array, extraer solo la versión
                    if ($extension === 'curl' && is_array($versionRaw)) {
                        $version = $versionRaw['version'] ?? null;
                    } else {
                        $version = is_string($versionRaw) ? $versionRaw : (is_scalar($versionRaw) ? (string) $versionRaw : null);
                    }
                } elseif ($extension === 'pdo_mysql' || $extension === 'pdo_mariadb') {
                    try {
                        $pdo = DB::connection()->getPdo();
                        $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
                    } catch (\Exception $e) {
                        // Ignorar si no hay conexión
                    }
                }
            }

            $resultado[] = [
                'nombre' => $extension.($opcional ? ' (opcional)' : ''),
                'instalada' => $instalada,
                'version' => $version,
                'opcional' => $opcional,
            ];
        }

        return $resultado;
    }

    /**
     * Verificar estado de la base de datos.
     *
     * @return array<string, mixed>
     */
    public function verificarBaseDatos(): array
    {
        try {
            $pdo = DB::connection()->getPdo();
            $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $driver = DB::connection()->getDriverName();

            // Determinar tipo
            $tipo = match ($driver) {
                'mysql' => 'MySQL',
                'mariadb' => 'MariaDB',
                default => ucfirst($driver),
            };

            return [
                'conectada' => true,
                'tipo' => $tipo,
                'version' => $version,
                'mensaje' => 'Conexión exitosa',
            ];
        } catch (\Exception $e) {
            return [
                'conectada' => false,
                'tipo' => null,
                'version' => null,
                'mensaje' => 'Error de conexión: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Obtener versiones de dependencias principales.
     *
     * @return array<string, mixed>
     */
    public function obtenerVersiones(): array
    {
        $versiones = [
            'php' => phpversion(),
            'laravel' => app()->version(),
            'paquetes' => [],
        ];

        // Intentar obtener versiones de paquetes clave usando composer.lock
        $composerLockPath = base_path('composer.lock');

        if (file_exists($composerLockPath)) {
            $composerLock = json_decode(file_get_contents($composerLockPath), true);

            if (isset($composerLock['packages'])) {
                $paquetesClave = [
                    'inertiajs/inertia-laravel',
                    'laravel/fortify',
                    'laravel/framework',
                ];

                foreach ($composerLock['packages'] as $paquete) {
                    if (in_array($paquete['name'], $paquetesClave)) {
                        $versiones['paquetes'][$paquete['name']] = $paquete['version'] ?? 'unknown';
                    }
                }
            }
        }

        return $versiones;
    }

    /**
     * Verificar estado completo del sistema.
     *
     * @return array<string, mixed>
     */
    public function verificarEstado(): array
    {
        return [
            'extensiones_php' => $this->verificarExtensionesPHP(),
            'base_datos' => $this->verificarBaseDatos(),
            'versiones' => $this->obtenerVersiones(),
        ];
    }
}
