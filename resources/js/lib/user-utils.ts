import { type User } from '@/types';

/**
 * Obtiene el nombre completo del usuario a partir de nombres y apellidos.
 *
 * @param user - Usuario con nombres y apellidos
 * @returns Nombre completo o string vacío si no hay datos
 */
export function getUserFullName(user: User | null | undefined): string {
    if (!user) {
        return '';
    }

    const nombres = (user.nombres ?? '').trim();
    const apellidos = (user.apellidos ?? '').trim();

    if (nombres && apellidos) {
        return `${nombres} ${apellidos}`;
    }

    return nombres || apellidos || '';
}
