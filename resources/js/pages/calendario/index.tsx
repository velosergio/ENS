import { Head, usePage } from '@inertiajs/react';

import Calendar from '@/components/calendar';
import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import { index as calendarioIndex } from '@/routes/calendario';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Calendario',
        href: calendarioIndex().url,
    },
];

interface Equipo {
    id: number;
    numero: number;
}

interface CalendarioIndexProps {
    equipos?: Equipo[];
}

export default function CalendarioIndex({ equipos = [] }: CalendarioIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const puedeGlobal = auth.user.rol === 'mango' || auth.user.rol === 'admin';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Calendario" />

            <h1 className="sr-only">Calendario</h1>

            <div className="flex flex-col space-y-6 p-6">
                <HeadingSmall
                    title="Calendario"
                    description="Visualiza y gestiona todos los eventos del movimiento"
                />

                <div className="rounded-lg border bg-card p-4 shadow-sm">
                    <Calendar equipos={equipos} puedeGlobal={puedeGlobal} />
                </div>
            </div>
        </AppLayout>
    );
}
