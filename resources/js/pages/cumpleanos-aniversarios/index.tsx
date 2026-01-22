import { Head, router } from '@inertiajs/react';
import { Cake, Heart, Users, Calendar, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index as cumpleanosAniversariosIndex } from '@/routes/cumpleanos-aniversarios';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cumpleaños y Aniversarios',
        href: cumpleanosAniversariosIndex().url,
    },
];

interface UsuarioData {
    id: number;
    nombres: string | null;
    apellidos: string | null;
    email?: string;
    cedula?: string | null;
    celular?: string;
    fecha_nacimiento: string;
    dia: number;
    mes: number;
    año_nacimiento: number;
    edad: number;
    fecha_cumpleanos: string;
    equipo_id?: number | null;
    equipo_numero?: number | null;
}

interface ParejaData {
    id: number;
    el: {
        id: number;
        nombres: string | null;
        apellidos: string | null;
        email?: string;
        cedula?: string | null;
        celular?: string;
    } | null;
    ella: {
        id: number;
        nombres: string | null;
        apellidos: string | null;
        email?: string;
        cedula?: string | null;
        celular?: string;
    } | null;
}

interface AniversarioData {
    id: string;
    titulo: string;
    fecha: string;
    tipo: 'aniversario_boda' | 'aniversario_acogida';
    años: number;
    pareja: ParejaData;
}

interface EquipoData {
    id: number;
    numero: number;
}

interface CumpleanosAniversariosIndexProps {
    cumpleanos: UsuarioData[];
    aniversarios: AniversarioData[];
    mes: number;
    año: number;
    equipo_id: number | null;
    equipos: EquipoData[];
    puedeVerDatosSensibles: boolean;
}

const meses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

export default function CumpleanosAniversariosIndex({
    cumpleanos,
    aniversarios,
    mes,
    año,
    equipo_id,
    equipos,
    puedeVerDatosSensibles,
}: CumpleanosAniversariosIndexProps) {
    const [mesActual, setMesActual] = useState(mes);
    const [añoActual, setAñoActual] = useState(año);
    const [equipoFiltro, setEquipoFiltro] = useState<string>(
        equipo_id ? equipo_id.toString() : 'all',
    );

    const cambiarMes = (direccion: 'anterior' | 'siguiente') => {
        let nuevoMes = mesActual;
        let nuevoAño = añoActual;

        if (direccion === 'anterior') {
            nuevoMes--;
            if (nuevoMes < 1) {
                nuevoMes = 12;
                nuevoAño--;
            }
        } else {
            nuevoMes++;
            if (nuevoMes > 12) {
                nuevoMes = 1;
                nuevoAño++;
            }
        }

        setMesActual(nuevoMes);
        setAñoActual(nuevoAño);

        router.get(cumpleanosAniversariosIndex().url, {
            mes: nuevoMes,
            año: nuevoAño,
            equipo_id: equipoFiltro === 'all' ? undefined : equipoFiltro,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const cambiarEquipo = (valor: string) => {
        setEquipoFiltro(valor);
        router.get(cumpleanosAniversariosIndex().url, {
            mes: mesActual,
            año: añoActual,
            equipo_id: valor === 'all' ? undefined : valor,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const formatearFecha = (fecha: string): string => {
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES', {
            day: 'numeric',
            month: 'long',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cumpleaños y Aniversarios" />
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Cumpleaños y Aniversarios
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Eventos del mes de {meses[mesActual - 1]} {añoActual}
                        </p>
                    </div>

                    {/* Controles de navegación y filtros */}
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                        {/* Navegación de mes */}
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="icon"
                                onClick={() => cambiarMes('anterior')}
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <div className="min-w-[120px] text-center text-sm font-medium">
                                {meses[mesActual - 1]} {añoActual}
                            </div>
                            <Button
                                variant="outline"
                                size="icon"
                                onClick={() => cambiarMes('siguiente')}
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>

                        {/* Filtro por equipo (solo si es mango/admin) */}
                        {equipos.length > 0 && (
                            <Select
                                value={equipoFiltro}
                                onValueChange={cambiarEquipo}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todos los equipos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los equipos</SelectItem>
                                    {equipos.map((equipo) => (
                                        <SelectItem
                                            key={equipo.id}
                                            value={equipo.id.toString()}
                                        >
                                            Equipo {equipo.numero}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                    </div>
                </div>

                {/* Sección de Cumpleaños */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Cake className="h-5 w-5" />
                            Cumpleaños
                        </CardTitle>
                        <CardDescription>
                            {cumpleanos.length} cumpleaños en {meses[mesActual - 1]}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {cumpleanos.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No hay cumpleaños este mes.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {cumpleanos.map((cumpleano) => (
                                    <div
                                        key={cumpleano.id}
                                        className="flex items-center justify-between rounded-lg border p-4"
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-pink-100 dark:bg-pink-900/20">
                                                <Cake className="h-6 w-6 text-pink-600 dark:text-pink-400" />
                                            </div>
                                            <div>
                                                <div className="font-medium">
                                                    {cumpleano.nombre}
                                                </div>
                                                <div className="text-sm text-muted-foreground">
                                                    {formatearFecha(cumpleano.fecha_cumpleanos)} - {cumpleano.edad} años
                                                </div>
                                                {cumpleano.equipo_numero && (
                                                    <Badge variant="outline" className="mt-1">
                                                        Equipo {cumpleano.equipo_numero}
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                        {puedeVerDatosSensibles && (
                                            <div className="text-right text-sm text-muted-foreground">
                                                {cumpleano.email && <div>{cumpleano.email}</div>}
                                                {cumpleano.celular && <div>{cumpleano.celular}</div>}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Sección de Aniversarios */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Heart className="h-5 w-5" />
                            Aniversarios
                        </CardTitle>
                        <CardDescription>
                            {aniversarios.length} aniversarios en {meses[mesActual - 1]}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {aniversarios.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No hay aniversarios este mes.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {/* Aniversarios de Boda */}
                                {aniversarios.filter(a => a.tipo === 'aniversario_boda').length > 0 && (
                                    <div>
                                        <h3 className="mb-3 text-sm font-semibold text-muted-foreground">
                                            Aniversarios de Boda
                                        </h3>
                                        <div className="space-y-3">
                                            {aniversarios
                                                .filter(a => a.tipo === 'aniversario_boda')
                                                .map((aniversario) => {
                                                    const nombreEl = aniversario.pareja.el
                                                        ? `${aniversario.pareja.el.nombres || ''} ${aniversario.pareja.el.apellidos || ''}`.trim()
                                                        : '';
                                                    const nombreElla = aniversario.pareja.ella
                                                        ? `${aniversario.pareja.ella.nombres || ''} ${aniversario.pareja.ella.apellidos || ''}`.trim()
                                                        : '';
                                                    const nombrePareja = `${nombreEl} & ${nombreElla}`.trim() || 'Pareja sin nombre';

                                                    return (
                                                        <div
                                                            key={aniversario.id}
                                                            className="flex items-center justify-between rounded-lg border p-4"
                                                        >
                                                            <div className="flex items-center gap-4">
                                                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/20">
                                                                    <Heart className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                                                                </div>
                                                                <div>
                                                                    <div className="font-medium">
                                                                        {nombrePareja}
                                                                    </div>
                                                                    <div className="text-sm text-muted-foreground">
                                                                        {formatearFecha(aniversario.fecha)} - {aniversario.años} años de casados
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            {puedeVerDatosSensibles && (
                                                                <div className="text-right text-sm text-muted-foreground">
                                                                    {aniversario.pareja.el?.email && (
                                                                        <div>{aniversario.pareja.el.email}</div>
                                                                    )}
                                                                    {aniversario.pareja.ella?.email && (
                                                                        <div>{aniversario.pareja.ella.email}</div>
                                                                    )}
                                                                </div>
                                                            )}
                                                        </div>
                                                    );
                                                })}
                                        </div>
                                    </div>
                                )}

                                {/* Aniversarios de Acogida */}
                                {aniversarios.filter(a => a.tipo === 'aniversario_acogida').length > 0 && (
                                    <div>
                                        <h3 className="mb-3 text-sm font-semibold text-muted-foreground">
                                            Aniversarios de Acogida
                                        </h3>
                                        <div className="space-y-3">
                                            {aniversarios
                                                .filter(a => a.tipo === 'aniversario_acogida')
                                                .map((aniversario) => {
                                                    const nombreEl = aniversario.pareja.el
                                                        ? `${aniversario.pareja.el.nombres || ''} ${aniversario.pareja.el.apellidos || ''}`.trim()
                                                        : '';
                                                    const nombreElla = aniversario.pareja.ella
                                                        ? `${aniversario.pareja.ella.nombres || ''} ${aniversario.pareja.ella.apellidos || ''}`.trim()
                                                        : '';
                                                    const nombrePareja = `${nombreEl} & ${nombreElla}`.trim() || 'Pareja sin nombre';

                                                    return (
                                                        <div
                                                            key={aniversario.id}
                                                            className="flex items-center justify-between rounded-lg border p-4"
                                                        >
                                                            <div className="flex items-center gap-4">
                                                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/20">
                                                                    <Users className="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                                                                </div>
                                                                <div>
                                                                    <div className="font-medium">
                                                                        {nombrePareja}
                                                                    </div>
                                                                    <div className="text-sm text-muted-foreground">
                                                                        {formatearFecha(aniversario.fecha)} - {aniversario.años} años en el movimiento
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            {puedeVerDatosSensibles && (
                                                                <div className="text-right text-sm text-muted-foreground">
                                                                    {aniversario.pareja.el?.email && (
                                                                        <div>{aniversario.pareja.el.email}</div>
                                                                    )}
                                                                    {aniversario.pareja.ella?.email && (
                                                                        <div>{aniversario.pareja.ella.email}</div>
                                                                    )}
                                                                </div>
                                                            )}
                                                        </div>
                                                    );
                                                })}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
