import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronUp,
    Edit,
    Eye,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import {
    index as equiposIndex,
    create as equiposCreate,
    edit as equiposEdit,
    show as equiposShow,
    destroy as equiposDestroy,
} from '@/routes/equipos';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Equipos',
        href: equiposIndex().url,
    },
];

interface ResponsableData {
    id: number;
    nombres: string | null;
    apellidos: string | null;
    email: string;
}

interface ParejaResponsableData {
    id: number;
    el: {
        nombres: string | null;
        apellidos: string | null;
    } | null;
    ella: {
        nombres: string | null;
        apellidos: string | null;
    } | null;
}

interface EquipoData {
    id: number;
    numero: number;
    consiliario_nombre: string | null;
    responsable: ResponsableData | null;
    pareja_responsable: ParejaResponsableData | null;
    total_parejas: number;
    total_usuarios: number;
}

interface PaginatedEquipos {
    data: EquipoData[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface EquiposIndexProps {
    equipos: PaginatedEquipos;
    filters: {
        buscar?: string;
        numero?: string;
        responsable_id?: string;
    };
}

export default function EquiposIndex({
    equipos,
    filters: initialFilters,
}: EquiposIndexProps) {
    const [buscar, setBuscar] = useState(initialFilters.buscar || '');
    const [numero, setNumero] = useState(initialFilters.numero || '');
    const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());

    // Debounce para búsqueda
    useEffect(() => {
        const timer = setTimeout(() => {
            if (buscar !== initialFilters.buscar) {
                router.get(
                    equiposIndex().url,
                    {
                        buscar: buscar || undefined,
                        numero: numero || undefined,
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    },
                );
            }
        }, 300);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [buscar, numero]);

    // Filtrar cuando cambian los filtros
    const handleFilterChange = useCallback(() => {
        router.get(
            equiposIndex().url,
            {
                buscar: buscar || undefined,
                numero: numero || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    }, [buscar, numero]);

    useEffect(() => {
        if (numero !== initialFilters.numero) {
            handleFilterChange();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [numero, handleFilterChange]);

    const toggleExpand = (id: number) => {
        setExpandedIds((prev) => {
            const newSet = new Set(prev);
            if (newSet.has(id)) {
                newSet.delete(id);
            } else {
                newSet.add(id);
            }
            return newSet;
        });
    };

    const handleDelete = (equipo: EquipoData) => {
        if (
            confirm(
                `¿Está seguro de eliminar el equipo ${equipo.numero}? Esta acción no se puede deshacer.`,
            )
        ) {
            router.delete(equiposDestroy({ equipo: equipo.id }).url);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Equipos" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header con título y botón crear */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Gestión de Equipos
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Administra los equipos del movimiento ENS
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={equiposCreate().url}>
                            <Plus className="mr-2 size-4" />
                            Crear Equipo
                        </Link>
                    </Button>
                </div>

                {/* Filtros */}
                <div className="flex flex-col gap-4 rounded-lg border bg-card p-4 sm:flex-row">
                    <div className="flex-1">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Buscar por número, consiliario o responsable..."
                                value={buscar}
                                onChange={(e) => setBuscar(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                    </div>

                    <Input
                        type="number"
                        placeholder="Número de equipo"
                        value={numero}
                        onChange={(e) => setNumero(e.target.value)}
                        className="w-full sm:w-[180px]"
                    />
                </div>

                {/* Lista de equipos */}
                <InfiniteScroll data="equipos">
                    <div className="flex flex-col gap-4">
                        {equipos.data.length === 0 ? (
                            <div className="rounded-lg border bg-card p-12 text-center">
                                <p className="text-muted-foreground">
                                    No se encontraron equipos con los filtros
                                    aplicados.
                                </p>
                            </div>
                        ) : (
                            equipos.data.map((equipo) => {
                                const isExpanded = expandedIds.has(equipo.id);
                                const nombreResponsable = equipo.responsable
                                    ? `${equipo.responsable.nombres || ''} ${equipo.responsable.apellidos || ''}`.trim()
                                    : null;
                                const nombreParejaResponsable = equipo
                                    .pareja_responsable
                                    ? `${equipo.pareja_responsable.el?.nombres || ''} ${equipo.pareja_responsable.el?.apellidos || ''} & ${equipo.pareja_responsable.ella?.nombres || ''} ${equipo.pareja_responsable.ella?.apellidos || ''}`.trim()
                                    : null;

                                return (
                                    <Collapsible
                                        key={equipo.id}
                                        open={isExpanded}
                                        onOpenChange={() =>
                                            toggleExpand(equipo.id)
                                        }
                                    >
                                        <div className="rounded-lg border bg-card">
                                            <CollapsibleTrigger asChild>
                                                <div className="flex w-full cursor-pointer items-center justify-between p-4 hover:bg-muted/50 transition-colors">
                                                    <div className="flex flex-1 items-center gap-4">
                                                        <div className="flex-shrink-0 flex items-center justify-center size-16 rounded-lg bg-primary/10 border border-primary/20">
                                                            <span className="text-2xl font-bold text-primary">
                                                                {equipo.numero}
                                                            </span>
                                                        </div>
                                                        <div className="flex flex-1 flex-col gap-1">
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-semibold text-foreground">
                                                                    Equipo{' '}
                                                                    {equipo.numero}
                                                                </span>
                                                            </div>
                                                            <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                                                                {equipo
                                                                    .consiliario_nombre && (
                                                                    <span>
                                                                        Consiliario:{' '}
                                                                        {
                                                                            equipo.consiliario_nombre
                                                                        }
                                                                    </span>
                                                                )}
                                                                {nombreParejaResponsable && (
                                                                    <span>
                                                                        Responsable:{' '}
                                                                        {
                                                                            nombreParejaResponsable
                                                                        }
                                                                    </span>
                                                                )}
                                                                <span>
                                                                    {equipo.total_parejas}{' '}
                                                                    pareja
                                                                    {equipo.total_parejas !==
                                                                    1
                                                                        ? 's'
                                                                        : ''}
                                                                </span>
                                                                <span>
                                                                    {equipo.total_usuarios}{' '}
                                                                    usuario
                                                                    {equipo.total_usuarios !==
                                                                    1
                                                                        ? 's'
                                                                        : ''}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            {isExpanded ? (
                                                                <ChevronUp className="size-4" />
                                                            ) : (
                                                                <ChevronDown className="size-4" />
                                                            )}
                                                        </Button>
                                                    </div>
                                                </div>
                                            </CollapsibleTrigger>

                                            <CollapsibleContent>
                                                <div className="border-t bg-muted/30 p-4">
                                                    <div className="grid gap-6 sm:grid-cols-2">
                                                        {/* Información del Equipo */}
                                                        <div className="rounded-lg border bg-card p-4">
                                                            <h3 className="mb-3 font-semibold text-foreground">
                                                                Información del
                                                                Equipo
                                                            </h3>
                                                            <div className="space-y-2 text-sm">
                                                                <div>
                                                                    <span className="font-medium text-muted-foreground">
                                                                        Número:
                                                                    </span>{' '}
                                                                    {equipo.numero}
                                                                </div>
                                                                {equipo
                                                                    .consiliario_nombre && (
                                                                    <div>
                                                                        <span className="font-medium text-muted-foreground">
                                                                            Consiliario:
                                                                        </span>{' '}
                                                                        {
                                                                            equipo.consiliario_nombre
                                                                        }
                                                                    </div>
                                                                )}
                                                                <div>
                                                                    <span className="font-medium text-muted-foreground">
                                                                        Total
                                                                        Parejas:
                                                                    </span>{' '}
                                                                    {
                                                                        equipo.total_parejas
                                                                    }
                                                                </div>
                                                                <div>
                                                                    <span className="font-medium text-muted-foreground">
                                                                        Total
                                                                        Usuarios:
                                                                    </span>{' '}
                                                                    {
                                                                        equipo.total_usuarios
                                                                    }
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {/* Información del Responsable */}
                                                        {equipo.responsable && (
                                                            <div className="rounded-lg border bg-card p-4">
                                                                <h3 className="mb-3 font-semibold text-foreground">
                                                                    Responsable
                                                                </h3>
                                                                <div className="space-y-2 text-sm">
                                                                    <div>
                                                                        <span className="font-medium text-muted-foreground">
                                                                            Nombre:
                                                                        </span>{' '}
                                                                        {nombreResponsable ||
                                                                            'N/A'}
                                                                    </div>
                                                                    <div>
                                                                        <span className="font-medium text-muted-foreground">
                                                                            Email:
                                                                        </span>{' '}
                                                                        {
                                                                            equipo.responsable.email
                                                                        }
                                                                    </div>
                                                                    {equipo
                                                                        .pareja_responsable && (
                                                                        <div>
                                                                            <span className="font-medium text-muted-foreground">
                                                                                Pareja:
                                                                            </span>{' '}
                                                                            {nombreParejaResponsable}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>

                                                    {/* Acciones */}
                                                    <div className="mt-4 flex flex-wrap gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={equiposShow({
                                                                    equipo: equipo.id,
                                                                }).url}
                                                            >
                                                                <Eye className="mr-2 size-4" />
                                                                Ver Detalle
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={equiposEdit({
                                                                    equipo: equipo.id,
                                                                }).url}
                                                            >
                                                                <Edit className="mr-2 size-4" />
                                                                Editar
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                handleDelete(
                                                                    equipo,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="mr-2 size-4" />
                                                            Eliminar
                                                        </Button>
                                                    </div>
                                                </div>
                                            </CollapsibleContent>
                                        </div>
                                    </Collapsible>
                                );
                            })
                        )}
                    </div>
                </InfiniteScroll>
            </div>
        </AppLayout>
    );
}
