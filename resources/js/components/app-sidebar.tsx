import { Link, usePage } from '@inertiajs/react';
import { Heart, LayoutGrid, UsersRound } from 'lucide-react';

import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as equiposIndex } from '@/routes/equipos';
import { index as parejasIndex } from '@/routes/parejas';
import { type NavItem, type SharedData } from '@/types';

import AppLogo from './app-logo';

export function AppSidebar() {
    const page = usePage<SharedData>();
    const { auth } = page.props;

    const mainNavItems: NavItem[] = [
        {
            title: 'Panel de control',
            href: dashboard(),
            icon: LayoutGrid,
        },
        // Mostrar Parejas y Equipos solo si el usuario tiene rol mango o admin
        ...(auth.user && (auth.user.rol === 'mango' || auth.user.rol === 'admin')
            ? [
                  {
                      title: 'Parejas',
                      href: parejasIndex(),
                      icon: Heart,
                  } as NavItem,
                  {
                      title: 'Equipos',
                      href: equiposIndex(),
                      icon: UsersRound,
                  } as NavItem,
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
