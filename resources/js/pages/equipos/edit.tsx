import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import EquipoController from '@/actions/App/Http/Controllers/EquipoController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import {
    index as equiposIndex,
    edit as equiposEdit,
} from '@/routes/equipos';
import { type BreadcrumbItem } from '@/types';

interface EquipoData {
    id: number;
    numero: number;
    consiliario_nombre: string | null;
    responsable_id: number | null;
}

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

interface EquiposEditProps {
    equipo: EquipoData;
    usuarios_disponibles: UsuarioDisponible[];
}

const breadcrumbs = (equipoId: number): BreadcrumbItem[] => [
    {
        title: 'Equipos',
        href: equiposIndex().url,
    },
    {
        title: 'Editar Equipo',
        href: equiposEdit({ equipo: equipoId }).url,
    },
];

export default function EquiposEdit({
    equipo: equipoProp,
}: EquiposEditProps) {
    const form = useForm({
        numero: equipoProp.numero.toString(),
        consiliario_nombre: equipoProp.consiliario_nombre || '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.put(EquipoController.update({ equipo: equipoProp.id }).url, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs(equipoProp.id)}>
            <Head title="Editar Equipo" />

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
                            Editar Equipo {equipoProp.numero}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Actualiza los datos del equipo
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
                                    Actualizando...
                                </>
                            ) : (
                                'Actualizar Equipo'
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
