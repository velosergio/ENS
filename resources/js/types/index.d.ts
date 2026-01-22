import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
    permissions: Record<string, string[]>;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    nombres: string | null;
    apellidos: string | null;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    rol: 'mango' | 'admin' | 'equipista';
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export type TipoEventoCalendario = 'general' | 'formacion' | 'retiro_espiritual' | 'reunion_equipo' | 'cumpleanos' | 'aniversario_boda' | 'aniversario_acogida';

export type AlcanceEvento = 'equipo' | 'global';

export interface EventoCalendario {
    id: number;
    titulo: string;
    descripcion: string | null;
    fecha_inicio: string;
    fecha_fin: string;
    hora_inicio: string | null;
    hora_fin: string | null;
    es_todo_el_dia: boolean;
    tipo: TipoEventoCalendario;
    alcance: AlcanceEvento;
    equipo_id: number | null;
    creado_por: number;
    color: string;
    icono: string | null;
    created_at: string;
    updated_at: string;
    // Relaciones (opcionales, solo cuando se cargan)
    creado_por_user?: User;
    equipo?: {
        id: number;
        numero: number;
        consiliario_nombre: string | null;
    };
}
