import { Link, router } from '@inertiajs/react';
import { LogOut, Settings } from 'lucide-react';

import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import { type User } from '@/types';

interface UserMenuContentProps {
    user: User;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    // Obtener nombre del usuario actual y su pareja
    const nombreUsuarioActual = user?.nombres ?? '';

    const pareja = (user as { pareja?: { usuarios?: Array<{ id: number; nombres?: string | null }> } | null })?.pareja;
    const usuarios = pareja?.usuarios ?? [];
    const parejaUsuario = usuarios.find((u) => u.id !== user.id);
    const nombrePareja = parejaUsuario?.nombres ?? '';

    const nombresPareja =
        nombreUsuarioActual && nombrePareja
            ? `${nombreUsuarioActual} & ${nombrePareja}`
            : nombreUsuarioActual || nombrePareja || '';

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex flex-col gap-1 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                    {nombresPareja && (
                        <span className="truncate text-xs text-muted-foreground text-left">
                            {nombresPareja}
                        </span>
                    )}
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full cursor-pointer"
                        href={edit()}
                        prefetch
                        onClick={cleanup}
                    >
                        <Settings className="mr-2" />
                        Configuración
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full cursor-pointer"
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    Cerrar sesión
                </Link>
            </DropdownMenuItem>
        </>
    );
}
