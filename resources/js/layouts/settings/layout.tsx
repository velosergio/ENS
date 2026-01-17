import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useActiveUrl } from '@/hooks/use-active-url';
import { useCan } from '@/lib/permissions';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editCalendario } from '@/routes/calendario/configuracion';
import { edit as editPareja } from '@/routes/pareja';
import { edit } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { urlIsActive } = useActiveUrl();
    const can = useCan('calendario', 'configurar');

    const sidebarNavItems: NavItem[] = [
        {
            title: 'Perfil',
            href: edit(),
            icon: null,
        },
        {
            title: 'Pareja',
            href: editPareja(),
            icon: null,
        },
        {
            title: 'Contraseña',
            href: editPassword(),
            icon: null,
        },
        {
            title: 'Autenticación de Dos Factores',
            href: show(),
            icon: null,
        },
        {
            title: 'Apariencia',
            href: editAppearance(),
            icon: null,
        },
        // Agregar Calendario solo si tiene permiso de configurar
        ...(can
            ? [
                  {
                      title: 'Calendario',
                      href: editCalendario(),
                      icon: null,
                  } as NavItem,
              ]
            : []),
    ];

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    return (
        <div className="px-4 py-6">
            <Heading
                title="Configuración"
                description="Gestiona la configuración de tu perfil y cuenta"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label="Configuración"
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': urlIsActive(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
