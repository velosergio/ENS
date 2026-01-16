import { useCallback } from 'react';

import { getUserFullName } from '@/lib/user-utils';
import { type User } from '@/types';

export function useInitials() {
    return useCallback((userOrName: User | string): string => {
        const fullName =
            typeof userOrName === 'string'
                ? userOrName.trim()
                : getUserFullName(userOrName);

        if (!fullName) {
            return '';
        }

        const names = fullName.split(' ').filter((n) => n.length > 0);

        if (names.length === 0) {
            return '';
        }

        if (names.length === 1) {
            return names[0].charAt(0).toUpperCase();
        }

        const firstInitial = names[0].charAt(0);
        const lastInitial = names[names.length - 1].charAt(0);

        return `${firstInitial}${lastInitial}`.toUpperCase();
    }, []);
}
