<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    /**
     * Definición de permisos por módulo y rol.
     *
     * Estructura: [
     *     'modulo' => [
     *         'accion' => ['roles' => ['mango', 'admin', 'equipista'], ...]
     *     ]
     * ]
     *
     * @var array<string, array<string, array<string, array<int, string>>>>
     */
    protected array $permissions = [
        // Módulo: Dashboard
        'dashboard' => [
            'view' => ['roles' => ['mango', 'admin', 'equipista']],
        ],

        // Módulo: Perfil
        'profile' => [
            'view' => ['roles' => ['mango', 'admin', 'equipista']],
            'update' => ['roles' => ['mango', 'admin', 'equipista']],
            'delete' => ['roles' => ['mango', 'admin', 'equipista']],
        ],

        // Módulo: Pareja
        'pareja' => [
            'view' => ['roles' => ['mango', 'admin', 'equipista']],
            'update' => ['roles' => ['mango', 'admin', 'equipista']],
            'retirar' => ['roles' => ['mango', 'admin', 'equipista']],
            'reactivar' => ['roles' => ['mango', 'admin']], // Solo mango y admin pueden reactivar
        ],

        // Módulo: Usuarios (Gestión de usuarios del sistema)
        'users' => [
            'view' => ['roles' => ['mango', 'admin']],
            'create' => ['roles' => ['mango', 'admin']],
            'update' => ['roles' => ['mango', 'admin']],
            'delete' => ['roles' => ['mango', 'admin']],
            'assign-role' => ['roles' => ['mango', 'admin']],
        ],

        // Módulo: Parejas (Gestión de parejas del sistema)
        'parejas' => [
            'view' => ['roles' => ['mango', 'admin']],
            'create' => ['roles' => ['mango', 'admin']],
            'update' => ['roles' => ['mango', 'admin']],
            'delete' => ['roles' => ['mango', 'admin']],
            'reactivar' => ['roles' => ['mango', 'admin']],
        ],

        // Módulo: Equipos
        'equipos' => [
            'view' => ['roles' => ['mango', 'admin']],
            'create' => ['roles' => ['mango', 'admin']],
            'update' => ['roles' => ['mango', 'admin']],
            'delete' => ['roles' => ['mango', 'admin']],
            'asignar-responsable' => ['roles' => ['mango', 'admin']],
            'configurar-consiliario' => ['roles' => ['mango', 'admin']],
        ],

        // Módulo: Calendario
        'calendario' => [
            'view' => ['roles' => ['mango', 'admin', 'equipista']],
            'create' => ['roles' => ['mango', 'admin', 'equipista']],
            'update' => ['roles' => ['mango', 'admin', 'equipista']], // Lógica especial: solo sus eventos o mango/admin todos
            'delete' => ['roles' => ['mango', 'admin', 'equipista']], // Lógica especial: solo sus eventos o mango/admin todos
            'configurar' => ['roles' => ['mango', 'admin']], // Configurar colores e iconos
        ],

        // Módulo: Configuración del Sistema
        'system' => [
            'view' => ['roles' => ['mango']],
            'update' => ['roles' => ['mango']],
        ],
    ];

    /**
     * Verificar si un usuario tiene un permiso específico en un módulo.
     */
    public function hasPermission(User $user, string $module, string $action): bool
    {
        // Mango tiene todos los permisos
        if ($user->esMango()) {
            return true;
        }

        // Verificar si el módulo existe
        if (! isset($this->permissions[$module])) {
            return false;
        }

        // Verificar si la acción existe en el módulo
        if (! isset($this->permissions[$module][$action])) {
            return false;
        }

        // Verificar si el rol del usuario tiene el permiso
        $allowedRoles = $this->permissions[$module][$action]['roles'] ?? [];

        return in_array($user->rol, $allowedRoles);
    }

    /**
     * Obtener todos los permisos de un usuario por módulo.
     *
     * @return array<string, array<int, string>>
     */
    public function getUserPermissions(User $user): array
    {
        // Mango tiene todos los permisos
        if ($user->esMango()) {
            $allPermissions = [];
            foreach ($this->permissions as $module => $actions) {
                $allPermissions[$module] = array_keys($actions);
            }

            return $allPermissions;
        }

        $userPermissions = [];

        foreach ($this->permissions as $module => $actions) {
            $modulePermissions = [];
            foreach ($actions as $action => $config) {
                if (in_array($user->rol, $config['roles'] ?? [])) {
                    $modulePermissions[] = $action;
                }
            }
            if (! empty($modulePermissions)) {
                $userPermissions[$module] = $modulePermissions;
            }
        }

        return $userPermissions;
    }

    /**
     * Obtener la configuración de permisos (para administración).
     *
     * @return array<string, array<string, array<string, array<int, string>>>>
     */
    public function getPermissionsConfig(): array
    {
        return $this->permissions;
    }

    /**
     * Agregar un nuevo permiso dinámicamente.
     */
    public function addPermission(string $module, string $action, array $roles): void
    {
        if (! isset($this->permissions[$module])) {
            $this->permissions[$module] = [];
        }

        $this->permissions[$module][$action] = ['roles' => $roles];
    }
}
