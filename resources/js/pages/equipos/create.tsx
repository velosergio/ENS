import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import EquipoController from '@/actions/App/Http/Controllers/EquipoController';
import InputError from '@/components/input-error';
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
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { index as equiposIndex } from '@/routes/equipos';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Equipos',
        href: equiposIndex().url,
    },
    {
        title: 'Crear Equipo',
        href: '#',
    },
];

interface UsuarioDisponible {
    id: number;
    nombres: string | null;
    apellidos: string | null;
    email: string;
    pareja: {
        id: number;
        el: {
            nombres: string | null;
            apellidos: string | null;
        } | null;
        ella: {
            nombres: string | null;
            apellidos: string | null;
        } | null;
    } | null;
}

interface EquiposCreateProps {
    usuarios_disponibles: UsuarioDisponible[];
}

export default function EquiposCreate({
    usuarios_disponibles,
}: EquiposCreateProps) {
    const form = useForm({
        numero: '',
        responsable_id: null as number | null,
        consiliario_nombre: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(EquipoController.store().url, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Equipo" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <a href={equiposIndex().url}>
                            <ArrowLeft className="size-4" />
                        </a>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Crear Nuevo Equipo
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Completa los datos del equipo
                        </p>
                    </div>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <div className="flex flex-col gap-4 rounded-lg border bg-card p-6">
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="numero">
                                    Número del Equipo *
                                </Label>
                                <Input
                                    id="numero"
                                    type="number"
                                    required
                                    min="1"
                                    name="numero"
                                    placeholder="Ingresa el número del equipo"
                                    value={form.data.numero}
                                    onChange={(e) =>
                                        form.setData('numero', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.numero} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="responsable_id">
                                    Responsable
                                </Label>
                                <Select
                                    value={
                                        form.data.responsable_id !== null
                                            ? form.data.responsable_id.toString()
                                            : 'none'
                                    }
                                    onValueChange={(value) =>
                                        form.setData(
                                            'responsable_id',
                                            value === 'none' ? null : parseInt(value, 10),
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar responsable (opcional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Sin responsable
                                        </SelectItem>
                                        {usuarios_disponibles.map((usuario) => {
                                            const nombreCompleto = `${usuario.nombres || ''} ${usuario.apellidos || ''}`.trim();
                                            const parejaNombre = usuario.pareja
                                                ? `${usuario.pareja.el?.nombres || ''} ${usuario.pareja.el?.apellidos || ''} & ${usuario.pareja.ella?.nombres || ''} ${usuario.pareja.ella?.apellidos || ''}`.trim()
                                                : null;

                                            return (
                                                <SelectItem
                                                    key={usuario.id}
                                                    value={usuario.id.toString()}
                                                >
                                                    {nombreCompleto || usuario.email}
                                                    {parejaNombre &&
                                                        ` (${parejaNombre})`}
                                                </SelectItem>
                                            );
                                        })}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={form.errors.responsable_id}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Al asignar un responsable, la pareja será
                                    ascendida automáticamente a admin
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="consiliario_nombre">
                                    Consiliario
                                </Label>
                                <Input
                                    id="consiliario_nombre"
                                    type="text"
                                    name="consiliario_nombre"
                                    placeholder="Ingresa el nombre del consiliario (opcional)"
                                    value={form.data.consiliario_nombre}
                                    onChange={(e) =>
                                        form.setData(
                                            'consiliario_nombre',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.consiliario_nombre}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Botones */}
                    <div className="flex flex-col-reverse gap-4 sm:flex-row sm:justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            asChild
                            disabled={form.processing}
                        >
                            <a href={equiposIndex().url}>Cancelar</a>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? (
                                <>
                                    <Spinner className="mr-2 size-4" />
                                    Creando...
                                </>
                            ) : (
                                'Crear Equipo'
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
