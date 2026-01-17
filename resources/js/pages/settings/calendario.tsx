import { Head, useForm, usePage } from '@inertiajs/react';
import {
    Calendar,
    BookOpen,
    Church,
    Users,
    type LucideIcon,
} from 'lucide-react';
import { useState } from 'react';

import CalendarioController from '@/actions/App/Http/Controllers/Settings/CalendarioController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/calendario/configuracion';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración de calendario',
        href: edit().url,
    },
];

interface ConfiguracionCalendario {
    id: number;
    tipo_evento: 'general' | 'formacion' | 'retiro_espiritual' | 'reunion_equipo';
    color: string;
    icono: string | null;
}

interface CalendarioProps {
    configuraciones: ConfiguracionCalendario[];
    status?: string;
}

// Iconos disponibles para elegir
const iconosDisponibles: Array<{ nombre: string; icono: LucideIcon; descripcion: string }> = [
    { nombre: 'Calendar', icono: Calendar, descripcion: 'Calendario' },
    { nombre: 'BookOpen', icono: BookOpen, descripcion: 'Libro abierto' },
    { nombre: 'Church', icono: Church, descripcion: 'Iglesia' },
    { nombre: 'Users', icono: Users, descripcion: 'Usuarios' },
];

// Mapeo de nombres de iconos a componentes
const iconosMap: Record<string, LucideIcon> = {
    Calendar,
    BookOpen,
    Church,
    Users,
};

const tipoEventoLabels: Record<string, string> = {
    general: 'Evento General',
    formacion: 'Formación',
    retiro_espiritual: 'Retiro Espiritual',
    reunion_equipo: 'Reunión de Equipo',
};

export default function Calendario({ configuraciones: configuracionesProp, status }: CalendarioProps) {
    const { flash } = usePage<SharedData>().props;
    const flashStatus = (flash as { status?: string })?.status;
    const displayStatus = status || flashStatus;

    const form = useForm({
        configuraciones: configuracionesProp.map((config) => ({
            id: config.id,
            color: config.color,
            icono: config.icono || '',
        })),
    });

    // Estado para mostrar/ocultar selector de iconos por tipo
    const [iconoSelectorAbierto, setIconoSelectorAbierto] = useState<number | null>(null);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(CalendarioController.update.url(), {
            preserveScroll: true,
            onSuccess: () => {
                setIconoSelectorAbierto(null);
            },
        });
    }

    function actualizarColor(index: number, color: string) {
        const nuevasConfiguraciones = [...form.data.configuraciones];
        nuevasConfiguraciones[index].color = color;
        form.setData('configuraciones', nuevasConfiguraciones);
    }

    function actualizarIcono(index: number, iconoNombre: string) {
        const nuevasConfiguraciones = [...form.data.configuraciones];
        nuevasConfiguraciones[index].icono = iconoNombre;
        form.setData('configuraciones', nuevasConfiguraciones);
        setIconoSelectorAbierto(null);
    }

    function obtenerIcono(tipoEvento: string): LucideIcon | null {
        const config = configuracionesProp.find((c) => c.tipo_evento === tipoEvento);
        if (!config || !config.icono) return null;
        return iconosMap[config.icono] || null;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración de calendario" />

            <h1 className="sr-only">Configuración de Calendario</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Configuración de calendario"
                        description="Personaliza los colores e iconos para cada tipo de evento del calendario"
                    />

                    {displayStatus && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-400">
                            {displayStatus}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-6">
                        {form.data.configuraciones.map((config, index) => {
                            const tipoOriginal = configuracionesProp[index].tipo_evento;
                            const IconoActual = obtenerIcono(tipoOriginal);

                            return (
                                <Card key={config.id}>
                                    <CardHeader>
                                        <div className="flex items-center gap-2">
                                            {IconoActual && (
                                                <IconoActual
                                                    className="h-5 w-5"
                                                    style={{ color: config.color }}
                                                />
                                            )}
                                            <CardTitle>
                                                {tipoEventoLabels[tipoOriginal]}
                                            </CardTitle>
                                        </div>
                                        <CardDescription>
                                            Configura el color e icono para eventos de tipo "{tipoEventoLabels[tipoOriginal]}"
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid gap-4 md:grid-cols-2">
                                            {/* Selector de Color */}
                                            <div className="space-y-2">
                                                <Label htmlFor={`color-${config.id}`}>
                                                    Color
                                                </Label>
                                                <div className="flex items-center gap-3">
                                                    <input
                                                        id={`color-${config.id}`}
                                                        type="color"
                                                        value={config.color}
                                                        onChange={(e) => actualizarColor(index, e.target.value)}
                                                        className="h-10 w-20 cursor-pointer rounded border border-input"
                                                    />
                                                    <input
                                                        type="text"
                                                        value={config.color}
                                                        onChange={(e) => actualizarColor(index, e.target.value)}
                                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                        placeholder="#3b82f6"
                                                        pattern="^#[0-9A-Fa-f]{6}$"
                                                    />
                                                </div>
                                                <InputError
                                                    className="mt-2"
                                                    message={form.errors[`configuraciones.${index}.color`]}
                                                />
                                            </div>

                                            {/* Selector de Icono */}
                                            <div className="space-y-2">
                                                <Label htmlFor={`icono-${config.id}`}>
                                                    Icono
                                                </Label>
                                                <div className="relative">
                                                    <Button
                                                        id={`icono-${config.id}`}
                                                        type="button"
                                                        variant="outline"
                                                        className="w-full justify-start"
                                                        onClick={() =>
                                                            setIconoSelectorAbierto(
                                                                iconoSelectorAbierto === index ? null : index,
                                                            )
                                                        }
                                                    >
                                                        {config.icono && iconosMap[config.icono] ? (
                                                            <div className="flex items-center gap-2">
                                                                {(() => {
                                                                    const IconComponent = iconosMap[config.icono!];
                                                                    return <IconComponent className="h-4 w-4" />;
                                                                })()}
                                                                <span>{config.icono}</span>
                                                            </div>
                                                        ) : (
                                                            'Seleccionar icono'
                                                        )}
                                                    </Button>

                                                    {iconoSelectorAbierto === index && (
                                                        <div className="absolute left-0 top-full z-10 mt-2 w-full rounded-md border bg-popover p-2 shadow-md">
                                                            <div className="grid grid-cols-2 gap-2">
                                                                {iconosDisponibles.map((iconoItem) => {
                                                                    const IconComponent = iconoItem.icono;
                                                                    return (
                                                                        <button
                                                                            key={iconoItem.nombre}
                                                                            type="button"
                                                                            onClick={() => actualizarIcono(index, iconoItem.nombre)}
                                                                            className={`flex items-center gap-2 rounded-md p-2 text-left transition-colors hover:bg-accent ${
                                                                                config.icono === iconoItem.nombre
                                                                                    ? 'bg-accent'
                                                                                    : ''
                                                                            }`}
                                                                        >
                                                                            <IconComponent className="h-4 w-4" />
                                                                            <span className="text-sm">
                                                                                {iconoItem.descripcion}
                                                                            </span>
                                                        </button>
                                                                    );
                                                                })}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                                <InputError
                                                    className="mt-2"
                                                    message={form.errors[`configuraciones.${index}.icono`]}
                                                />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}

                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                disabled={form.processing}
                            >
                                {form.processing ? 'Guardando...' : 'Guardar cambios'}
                            </Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
