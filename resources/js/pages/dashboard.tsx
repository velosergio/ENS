import { Head } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel de control',
        href: dashboard().url,
    },
];

interface DashboardProps {
    pareja?: {
        id: number;
        fecha_ingreso?: string;
        usuarios?: Array<{
            id: number;
            nombres?: string | null;
            name: string;
            sexo?: 'masculino' | 'femenino' | null;
        }>;
    } | null;
}

export default function Dashboard({ pareja }: DashboardProps) {
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
            <div className="flex h-full flex-1 flex-col items-center justify-center p-6">
                <h1 className="text-4xl font-bold text-foreground">
                    {saludo}
                </h1>
            </div>
        </AppLayout>
    );
}
