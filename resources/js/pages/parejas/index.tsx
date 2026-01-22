import { Head, InfiniteScroll, router } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronUp,
    Edit,
    Plus,
    RotateCcw,
    Search,
    Trash2,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import {
    index as parejasIndex,
    create as parejasCreate,
    edit as parejasEdit,
    retirar as parejasRetirar,
    reactivar as parejasReactivar,
} from '@/routes/parejas';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Parejas',
        href: parejasIndex().url,
    },
];

interface UsuarioData {
    id: number;
    nombres: string | null;
    apellidos: string | null;
    email: string;
    celular: string | null;
    fecha_nacimiento: string | null;
}

interface ParejaData {
    id: number;
    equipo_id: number | null;
    equipo: {
        id: number;
        numero: number;
    } | null;
    fecha_acogida: string | null;
    estado: 'activo' | 'retirado';
    foto_thumbnail_50: string | null;
    el: UsuarioData | null;
    ella: UsuarioData | null;
}

interface PaginatedParejas {
    data: ParejaData[];
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

interface EquipoData {
    id: number;
    numero: number;
}

interface ParejasIndexProps {
    parejas: PaginatedParejas;
    filters: {
        buscar?: string;
        estado?: string;
        equipo_id?: string;
    };
    equipos: EquipoData[];
}

export default function ParejasIndex({
    parejas,
    filters: initialFilters,
    equipos,
}: ParejasIndexProps) {
    const [buscar, setBuscar] = useState(initialFilters.buscar || '');
    const [estado, setEstado] = useState(initialFilters.estado || 'todos');
    const [equipoId, setEquipoId] = useState(
        initialFilters.equipo_id || 'all',
    );
    const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());

    // Debounce para búsqueda
    useEffect(() => {
        const timer = setTimeout(() => {
            if (buscar !== initialFilters.buscar) {
                router.get(
                    parejasIndex().url,
                    {
                        buscar: buscar || undefined,
                        estado: estado === 'todos' ? undefined : estado,
                        equipo_id: equipoId === 'all' ? undefined : equipoId,
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
    }, [buscar, estado, equipoId]);

    // Filtrar cuando cambian los filtros
    const handleFilterChange = useCallback(() => {
        router.get(
            parejasIndex().url,
            {
                buscar: buscar || undefined,
                estado: estado === 'todos' ? undefined : estado,
                equipo_id: equipoId === 'all' ? undefined : equipoId,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    }, [buscar, estado, equipoId]);

    useEffect(() => {
        if (estado !== initialFilters.estado) {
            handleFilterChange();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [estado, handleFilterChange]);

    useEffect(() => {
        const currentEquipoId = equipoId === 'all' ? undefined : equipoId;
        if (currentEquipoId !== initialFilters.equipo_id) {
            handleFilterChange();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [equipoId, handleFilterChange]);

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

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Parejas" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header con título y botón crear */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Gestión de Parejas
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Administra las parejas del movimiento ENS
                        </p>
                    </div>
                    <Button asChild>
                        <a href={parejasCreate().url}>
                            <Plus className="mr-2 size-4" />
                            Crear Pareja
                        </a>
                    </Button>
                </div>

                {/* Filtros */}
                <div className="flex flex-col gap-4 rounded-lg border bg-card p-4 sm:flex-row">
                    <div className="flex-1">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Buscar por nombre, email o equipo..."
                                value={buscar}
                                onChange={(e) => setBuscar(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                    </div>

                    <Select value={estado} onValueChange={setEstado}>
                        <SelectTrigger className="w-full sm:w-[180px]">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todos">Todos</SelectItem>
                            <SelectItem value="activo">Activo</SelectItem>
                            <SelectItem value="retirado">Retirado</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        value={equipoId}
                        onValueChange={setEquipoId}
                    >
                        <SelectTrigger className="w-full sm:w-[180px]">
                            <SelectValue placeholder="Equipo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            {equipos.map((equipo: EquipoData) => (
                                <SelectItem
                                    key={equipo.id}
                                    value={equipo.id.toString()}
                                >
                                    Equipo {equipo.numero}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Lista de parejas */}
                <InfiniteScroll data="parejas">
                    <div className="flex flex-col gap-4">
                        {parejas.data.length === 0 ? (
                            <div className="rounded-lg border bg-card p-12 text-center">
                                <p className="text-muted-foreground">
                                    No se encontraron parejas con los filtros
                                    aplicados.
                                </p>
                            </div>
                        ) : (
                            parejas.data.map((pareja) => {
                            const isExpanded = expandedIds.has(pareja.id);
                            const nombreCompleto = `${
                                pareja.el?.nombres || ''
                            } ${
                                pareja.el?.apellidos || ''
                            } & ${pareja.ella?.nombres || ''} ${
                                pareja.ella?.apellidos || ''
                            }`.trim();

                            return (
                                <Collapsible
                                    key={pareja.id}
                                    open={isExpanded}
                                    onOpenChange={() => toggleExpand(pareja.id)}
                                >
                                    <div className="rounded-lg border bg-card">
                                        <CollapsibleTrigger asChild>
                                            <div className="flex w-full cursor-pointer items-center justify-between p-4 hover:bg-muted/50 transition-colors">
                                                <div className="flex flex-1 items-center gap-4">
                                                    {/* Foto de la pareja - usando thumbnail de 50x50 */}
                                                    {pareja.foto_thumbnail_50 ? (
                                                        <div className="flex-shrink-0">
                                                            <img
                                                                src={pareja.foto_thumbnail_50}
                                                                alt={nombreCompleto || 'Foto de pareja'}
                                                                className="size-16 rounded-lg object-cover border"
                                                            />
                                                        </div>
                                                    ) : (
                                                        <div className="flex-shrink-0 flex items-center justify-center size-16 rounded-lg bg-muted border">
                                                            <span className="text-2xl text-muted-foreground">
                                                                👫
                                                            </span>
                                                        </div>
                                                    )}
                                                    <div className="flex flex-1 flex-col gap-1">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-semibold text-foreground">
                                                                {nombreCompleto ||
                                                                    'Sin nombre'}
                                                            </span>
                                                            <Badge
                                                                variant={
                                                                    pareja.estado ===
                                                                    'activo'
                                                                        ? 'default'
                                                                        : 'secondary'
                                                                }
                                                            >
                                                                {
                                                                    pareja.estado
                                                                }
                                                            </Badge>
                                                        </div>
                                                        <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                                                            {pareja.equipo && (
                                                                <span>
                                                                    Equipo:{' '}
                                                                    {
                                                                        pareja.equipo.numero
                                                                    }
                                                                </span>
                                                            )}
                                                            {pareja.fecha_acogida && (
                                                                <span>
                                                                    Acogida:{' '}
                                                                    {formatDate(
                                                                        pareja.fecha_acogida,
                                                                    )}
                                                                </span>
                                                            )}
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
                                                    {/* Información de ÉL */}
                                                    {pareja.el && (
                                                        <div className="rounded-lg border bg-card p-4">
                                                            <h3 className="mb-3 font-semibold text-foreground">
                                                                ÉL
                                                            </h3>
                                                            <div className="space-y-2 text-sm">
                                                                <div>
                                                                    <span className="font-medium text-muted-foreground">
                                                                        Nombre:
                                                                    </span>{' '}
                                                                    {`${pareja.el.nombres || ''} ${pareja.el.apellidos || ''}`.trim() ||
                                                                        'N/A'}
                                                                </div>
                                                                <div>
                                                                    <span className="font-medium text-muted-foreground">
                                                                        Email:
                                                                    </span>{' '}
                                                                    {
                                                                        pareja.el.email
                                                                    }
                                                                </div>
                                                                {pareja.el.celular && (
                                                                    <div>
                                                                        <span className="font-medium text-muted-foreground">
                                                                            Celular:
                                                                        </span>{' '}
                                                                        {
                                                                            pareja.el.celular
                                                                        }
                                                                    </div>
                                                                )}
                                                                {pareja.el.fecha_nacimiento && (
                                                                    <div>
                                                                        <span className="font-medium text-muted-foreground">
                                                                            Fecha de
                                                                            nacimiento:
                                                                        </span>{' '}
                                                                        {formatDate(
                                                                            pareja.el.fecha_nacimiento,
                                                                        )}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {/* Información de ELLA */}
                                                    {pareja.ella && (
                                                        <div className="rounded-lg border bg-card p-4">
                                                            <h3 className="mb-3 font-semibold text-foreground">
                                                                ELLA
                                                            </h3>
                                                            <div className="space-y-2 text-sm">
                                                                <div>
                                                                    <span className="font-medium text-muted-foreground">
                                                                        Nombre:
                                                                    </span>{' '}
                                                                    {`${pareja.ella.nombres || ''} ${pareja.ella.apellidos || ''}`.trim() ||
                                                                        'N/A'}
                                                                </div>
                                                                <div>
                                                                    <span className="font-medium text-muted-foreground">
                                                                        Email:
                                                                    </span>{' '}
                                                                    {
                                                                        pareja.ella.email
                                                                    }
                                                                </div>
                                                                {pareja.ella.celular && (
                                                                    <div>
                                                                        <span className="font-medium text-muted-foreground">
                                                                            Celular:
                                                                        </span>{' '}
                                                                        {
                                                                            pareja.ella.celular
                                                                        }
                                                                    </div>
                                                                )}
                                                                {pareja.ella.fecha_nacimiento && (
                                                                    <div>
                                                                        <span className="font-medium text-muted-foreground">
                                                                            Fecha de
                                                                            nacimiento:
                                                                        </span>{' '}
                                                                        {formatDate(
                                                                            pareja.ella.fecha_nacimiento,
                                                                        )}
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
                                                        <a
                                                            href={parejasEdit({
                                                                pareja: pareja.id,
                                                            }).url}
                                                        >
                                                            <Edit className="mr-2 size-4" />
                                                            Editar
                                                        </a>
                                                    </Button>
                                                    {pareja.estado ===
                                                    'activo' ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => {
                                                                if (
                                                                    confirm(
                                                                        '¿Está seguro de retirar esta pareja?',
                                                                    )
                                                                ) {
                                                                    router.post(
                                                                        parejasRetirar({
                                                                            pareja: pareja.id,
                                                                        }).url,
                                                                    );
                                                                }
                                                            }}
                                                        >
                                                            <Trash2 className="mr-2 size-4" />
                                                            Retirar
                                                        </Button>
                                                    ) : (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => {
                                                                router.post(
                                                                    parejasReactivar({
                                                                        pareja: pareja.id,
                                                                    }).url,
                                                                );
                                                            }}
                                                        >
                                                            <RotateCcw className="mr-2 size-4" />
                                                            Reactivar
                                                        </Button>
                                                    )}
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
