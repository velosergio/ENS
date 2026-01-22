import { Head } from '@inertiajs/react';

import AgendaCard from '@/components/agenda-card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type TipoEventoCalendario } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel de control',
        href: dashboard().url,
    },
];

interface DashboardProps {
    pareja?: {
        id: number;
        fecha_acogida?: string;
        usuarios?: Array<{
            id: number;
            nombres?: string | null;
            name: string;
            sexo?: 'masculino' | 'femenino' | null;
        }>;
    } | null;
    eventosProximos?: Array<{
        id: string;
        titulo: string;
        fecha_inicio: string;
        fecha_fin?: string;
        allDay: boolean;
        tipo: TipoEventoCalendario;
        alcance: 'equipo' | 'global';
        color: string;
        icono?: string | null;
    }>;
}

export default function Dashboard({ pareja, eventosProximos = [] }: DashboardProps) {
    const usuarios = pareja?.usuarios ?? [];

    // Obtener nombres de ambos integrantes
    const elUsuario = usuarios.find((u) => u.sexo === 'masculino');
    const ellaUsuario = usuarios.find((u) => u.sexo === 'femenino');
    const nombreEl = elUsuario?.nombres ?? '';
    const nombreElla = ellaUsuario?.nombres ?? '';

    const saludo =
        nombreEl && nombreElla
            ? `¡Hola ${nombreEl} & ${nombreElla}!`
            : nombreEl
              ? `¡Hola ${nombreEl}!`
              : nombreElla
                ? `¡Hola ${nombreElla}!`
                : '¡Hola!';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel de control" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex flex-col items-center justify-center">
                    <h1 className="text-4xl font-bold text-foreground">
                        {saludo}
                    </h1>
                </div>
                
                <div className="mx-auto w-full max-w-4xl">
                    <AgendaCard eventos={eventosProximos} />
                </div>
            </div>
        </AppLayout>
    );
}
