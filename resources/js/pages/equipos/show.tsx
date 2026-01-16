import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { ArrowLeft, Edit, UserPlus, Users } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import {
    index as equiposIndex,
    edit as equiposEdit,
    show as equiposShow,
    asignarResponsable,
    configurarConsiliario,
} from '@/routes/equipos';
import { edit as parejasEdit } from '@/routes/parejas';
import { type BreadcrumbItem } from '@/types';

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

interface ParejaData {
    id: number;
    fecha_ingreso: string | null;
    estado: 'activo' | 'retirado';
    foto_thumbnail_50: string | null;
    el: {
        id: number;
        nombres: string | null;
        apellidos: string | null;
        email: string;
    } | null;
    ella: {
        id: number;
        nombres: string | null;
        apellidos: string | null;
        email: string;
    } | null;
}

interface PaginatedParejas {
    data: ParejaData[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface ParejaDisponible {
    id: number;
    nombre: string;
    el: {
        id: number;
        nombres: string | null;
        apellidos: string | null;
    } | null;
    ella: {
        id: number;
        nombres: string | null;
        apellidos: string | null;
    } | null;
}

interface EquipoData {
    id: number;
    numero: number;
    consiliario_nombre: string | null;
    pareja_responsable_nombre: string | null;
    pareja_responsable: ParejaResponsableData | null;
}

interface EquiposShowProps {
    equipo: EquipoData;
    parejas: PaginatedParejas;
    parejas_disponibles: ParejaDisponible[];
}

const breadcrumbs = (equipoId: number, equipoNumero: number): BreadcrumbItem[] => [
    {
        title: 'Equipos',
        href: equiposIndex().url,
    },
    {
        title: `Equipo ${equipoNumero}`,
        href: equiposShow({ equipo: equipoId }).url,
    },
];

export default function EquiposShow({
    equipo: equipoProp,
    parejas,
    parejas_disponibles,
}: EquiposShowProps) {
    const [parejaId, setParejaId] = useState(
        equipoProp.pareja_responsable?.id.toString() || 'none',
    );
    const [consiliarioNombre, setConsiliarioNombre] = useState(
        equipoProp.consiliario_nombre || '',
    );


    const handleAsignarResponsable = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(
            asignarResponsable({ equipo: equipoProp.id }).url,
            {
                pareja_id: parejaId === 'none' || !parejaId ? null : parseInt(parejaId, 10),
            },
            {
                preserveScroll: true,
            },
        );
    };

    const handleConfigurarConsiliario = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(
            configurarConsiliario({ equipo: equipoProp.id }).url,
            {
                consiliario_nombre: consiliarioNombre || null,
            },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(equipoProp.id, equipoProp.numero)}>
            <Head title={`Equipo ${equipoProp.numero}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={equiposIndex().url}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div className="flex-1 min-w-0">
                            <h1 className="text-xl sm:text-2xl font-bold text-foreground truncate">
                                Equipo {equipoProp.numero}
                            </h1>
                            <p className="mt-1 text-xs sm:text-sm text-muted-foreground">
                                Información y gestión del equipo
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" size="sm" className="w-full sm:w-auto" asChild>
                        <Link href={equiposEdit({ equipo: equipoProp.id }).url}>
                            <Edit className="mr-2 size-4" />
                            Editar
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Columna principal */}
                    <div className="lg:col-span-2 flex flex-col gap-6">
                        {/* Información General */}
                        <div className="rounded-lg border bg-card p-4 sm:p-6">
                            <h2 className="mb-4 text-base sm:text-lg font-semibold">
                                Información General
                            </h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label className="text-muted-foreground">
                                        Número del Equipo
                                    </Label>
                                    <p className="mt-1 text-lg font-semibold">
                                        {equipoProp.numero}
                                    </p>
                                </div>
                                {equipoProp.consiliario_nombre && (
                                    <div>
                                        <Label className="text-muted-foreground">
                                            Consiliario
                                        </Label>
                                        <p className="mt-1 text-lg">
                                            {equipoProp.consiliario_nombre}
                                        </p>
                                    </div>
                                )}
                                {equipoProp.pareja_responsable_nombre && (
                                    <div>
                                        <Label className="text-muted-foreground">
                                            Pareja Responsable
                                        </Label>
                                        <p className="mt-1 text-lg">
                                            {equipoProp.pareja_responsable_nombre}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Lista de Parejas */}
                        <div className="rounded-lg border bg-card p-4 sm:p-6">
                            <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <h2 className="text-base sm:text-lg font-semibold">
                                    Parejas del Equipo
                                </h2>
                                <Badge variant="secondary" className="w-fit">
                                    {parejas.total} pareja
                                    {parejas.total !== 1 ? 's' : ''}
                                </Badge>
                            </div>

                            <InfiniteScroll data="parejas">
                                <div className="flex flex-col gap-4">
                                    {parejas.data.length === 0 ? (
                                        <div className="rounded-lg border bg-muted/30 p-8 text-center">
                                            <p className="text-muted-foreground">
                                                No hay parejas asignadas a este
                                                equipo
                                            </p>
                                        </div>
                                    ) : (
                                        parejas.data.map((pareja) => {
                                            const nombreCompleto = `${pareja.el?.nombres || ''} ${pareja.el?.apellidos || ''} & ${pareja.ella?.nombres || ''} ${pareja.ella?.apellidos || ''}`.trim();

                                            return (
                                                <div
                                                    key={pareja.id}
                                                    className="flex flex-col gap-3 rounded-lg border bg-muted/30 p-3 sm:flex-row sm:items-center sm:justify-between sm:p-4"
                                                >
                                                    <div className="flex items-center gap-3 min-w-0 flex-1">
                                                        {pareja.foto_thumbnail_50 ? (
                                                            <img
                                                                src={
                                                                    pareja.foto_thumbnail_50
                                                                }
                                                                alt={
                                                                    nombreCompleto ||
                                                                    'Foto de pareja'
                                                                }
                                                                className="size-10 sm:size-12 flex-shrink-0 rounded-lg object-cover border"
                                                            />
                                                        ) : (
                                                            <div className="flex items-center justify-center size-10 sm:size-12 flex-shrink-0 rounded-lg bg-muted border">
                                                                <span className="text-lg sm:text-xl text-muted-foreground">
                                                                    👫
                                                                </span>
                                                            </div>
                                                        )}
                                                        <div className="min-w-0 flex-1">
                                                            <p className="font-medium truncate text-sm sm:text-base">
                                                                {nombreCompleto ||
                                                                    'Sin nombre'}
                                                            </p>
                                                            <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-2 text-xs sm:text-sm text-muted-foreground">
                                                                {pareja.el && (
                                                                    <span className="truncate">
                                                                        {pareja.el.email}
                                                                    </span>
                                                                )}
                                                                {pareja.el &&
                                                                    pareja.ella && (
                                                                        <span className="hidden sm:inline">
                                                                            •
                                                                        </span>
                                                                    )}
                                                                {pareja.ella && (
                                                                    <span className="truncate">
                                                                        {
                                                                            pareja.ella.email
                                                                        }
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="w-full sm:w-auto"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={parejasEdit({
                                                                pareja: pareja.id,
                                                            }).url}
                                                        >
                                                            <Edit className="mr-2 size-4" />
                                                            Editar
                                                        </Link>
                                                    </Button>
                                                </div>
                                            );
                                        })
                                    )}
                                </div>
                            </InfiniteScroll>
                        </div>
                    </div>

                    {/* Columna lateral - Configuraciones */}
                    <div className="flex flex-col gap-6">
                        {/* Asignar Pareja Responsable */}
                        <div className="rounded-lg border bg-card p-4 sm:p-6">
                            <h3 className="mb-4 flex items-center gap-2 text-base sm:text-lg font-semibold">
                                <UserPlus className="size-4 sm:size-5" />
                                Asignar Pareja Responsable
                            </h3>
                            <form
                                onSubmit={handleAsignarResponsable}
                                className="space-y-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="pareja_id">
                                        Seleccionar Pareja Responsable
                                    </Label>
                                    <Select
                                        value={parejaId || 'none'}
                                        onValueChange={setParejaId}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sin pareja responsable" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                Sin pareja responsable
                                            </SelectItem>
                                            {parejas_disponibles.map(
                                                (pareja) => (
                                                    <SelectItem
                                                        key={pareja.id}
                                                        value={pareja.id.toString()}
                                                    >
                                                        {pareja.nombre}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-xs text-muted-foreground">
                                        Al asignar una pareja responsable, ambos
                                        usuarios serán ascendidos automáticamente
                                        a admin
                                    </p>
                                </div>
                                <Button type="submit" className="w-full">
                                    Asignar Pareja Responsable
                                </Button>
                            </form>
                        </div>

                        {/* Padre Consiliario */}
                        <div className="rounded-lg border bg-card p-4 sm:p-6">
                            <h3 className="mb-4 flex items-center gap-2 text-base sm:text-lg font-semibold">
                                <Users className="size-4 sm:size-5" />
                                Padre Consiliario
                            </h3>
                            <form
                                onSubmit={handleConfigurarConsiliario}
                                className="space-y-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="consiliario_nombre">
                                        Nombre del Consiliario
                                    </Label>
                                    <Input
                                        id="consiliario_nombre"
                                        type="text"
                                        placeholder="Ingresa el nombre del consiliario"
                                        value={consiliarioNombre}
                                        onChange={(e) =>
                                            setConsiliarioNombre(
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <Button type="submit" className="w-full">
                                    Guardar Padre Consiliario
                                </Button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
