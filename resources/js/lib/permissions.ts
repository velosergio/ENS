import { usePage } from '@inertiajs/react';

import { type SharedData } from '@/types';

/**
 * Obtener los permisos del usuario autenticado.
 */
export function usePermissions() {
    const { auth } = usePage<SharedData>().props;

    /**
     * Verificar si el usuario tiene un permiso específico en un módulo.
     */
    const hasPermission = (module: string, action: string): boolean => {
        if (!auth.user) {
            return false;
        }

        // Mango tiene todos los permisos
        if (auth.user.rol === 'mango') {
            return true;
        }

        const modulePermissions = auth.permissions[module] ?? [];
        return modulePermissions.includes(action);
    };

    /**
     * Verificar si el usuario tiene alguno de los permisos especificados.
     */
    const hasAnyPermission = (module: string, actions: string[]): boolean => {
        return actions.some((action) => hasPermission(module, action));
    };

    /**
     * Verificar si el usuario tiene todos los permisos especificados.
     */
    const hasAllPermissions = (module: string, actions: string[]): boolean => {
        return actions.every((action) => hasPermission(module, action));
    };

    /**
     * Verificar si el usuario tiene un rol específico.
     */
    const hasRole = (role: 'mango' | 'admin' | 'equipista'): boolean => {
        if (!auth.user) {
            return false;
        }

        // Mango tiene acceso a todo
        if (auth.user.rol === 'mango') {
            return true;
        }

        // Admin tiene acceso a admin y equipista
        if (role === 'admin') {
            return auth.user.rol === 'admin';
        }

        if (role === 'equipista') {
            return auth.user.rol === 'admin' || auth.user.rol === 'equipista';
        }

        // role === 'mango' - ya se verifica arriba con el early return
        return false;
    };

    /**
     * Verificar si el usuario es mango.
     */
    const isMango = (): boolean => {
        return hasRole('mango');
    };

    /**
     * Verificar si el usuario es admin.
     */
    const isAdmin = (): boolean => {
        return hasRole('admin') || isMango();
    };

    /**
     * Verificar si el usuario es equipista.
     */
    const isEquipista = (): boolean => {
        return hasRole('equipista') || isAdmin();
    };

    return {
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        hasRole,
        isMango,
        isAdmin,
        isEquipista,
        permissions: auth.permissions,
        user: auth.user,
    };
}

/**
 * Hook para verificar permisos (versión simplificada).
 * Debe usarse dentro de un componente React.
 */
export function useCan(module: string, action: string): boolean {
    const { auth } = usePage<SharedData>().props;

    if (!auth.user) {
        return false;
    }

    // Mango tiene todos los permisos
    if (auth.user.rol === 'mango') {
        return true;
    }

    const modulePermissions = auth.permissions[module] ?? [];
    return modulePermissions.includes(action);
}
