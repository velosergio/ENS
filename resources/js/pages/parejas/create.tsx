import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Info } from 'lucide-react';
import { useEffect, useState } from 'react';

import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { index as parejasIndex, store as parejasStore } from '@/routes/parejas';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Parejas',
        href: parejasIndex().url,
    },
    {
        title: 'Crear Pareja',
        href: '#',
    },
];

export default function ParejasCreate() {
    const [elFotoPreview, setElFotoPreview] = useState<string | null>(null);
    const [ellaFotoPreview, setEllaFotoPreview] = useState<string | null>(null);
    const [parejaFotoPreview, setParejaFotoPreview] = useState<string | null>(null);
    const [elFotoBase64, setElFotoBase64] = useState<string | null>(null);
    const [ellaFotoBase64, setEllaFotoBase64] = useState<string | null>(null);
    const [parejaFotoBase64, setParejaFotoBase64] = useState<string | null>(null);

    const handleFotoChange = (
        e: React.ChangeEvent<HTMLInputElement>,
        tipo: 'el' | 'ella' | 'pareja',
    ) => {
        const file = e.target.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onloadend = () => {
                const base64 = reader.result as string;
                if (tipo === 'el') {
                    setElFotoPreview(base64);
                    setElFotoBase64(base64);
                } else if (tipo === 'ella') {
                    setEllaFotoPreview(base64);
                    setEllaFotoBase64(base64);
                } else {
                    setParejaFotoPreview(base64);
                    setParejaFotoBase64(base64);
                }
            };
            reader.readAsDataURL(file);
        }
    };

    const formatDateForInput = (
        dateString: string | null | undefined,
    ): string => {
        if (!dateString) return '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString;
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toISOString().split('T')[0];
    };

    const today = new Date().toISOString().split('T')[0];

    const form = useForm({
        el_nombres: '',
        el_apellidos: '',
        el_celular: '',
        el_fecha_nacimiento: '',
        el_email: '',
        el_foto_base64: '',
        ella_nombres: '',
        ella_apellidos: '',
        ella_celular: '',
        ella_fecha_nacimiento: '',
        ella_email: '',
        ella_foto_base64: '',
        fecha_ingreso: '',
        numero_equipo: '',
        pareja_foto_base64: '',
        password: '',
        password_confirmation: '',
    });

    // Sincronizar fotos base64 con el form data cuando cambian
    useEffect(() => {
        form.setData('el_foto_base64', elFotoBase64 || '');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [elFotoBase64]);

    useEffect(() => {
        form.setData('ella_foto_base64', ellaFotoBase64 || '');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ellaFotoBase64]);

    useEffect(() => {
        form.setData('pareja_foto_base64', parejaFotoBase64 || '');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [parejaFotoBase64]);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(parejasStore().url, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Pareja" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <a href={parejasIndex().url}>
                            <ArrowLeft className="size-4" />
                        </a>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Crear Nueva Pareja
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Completa los datos de ambos integrantes de la pareja
                        </p>
                    </div>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-6">

                    <div className="flex flex-col gap-6">
                        {/* Datos de ÉL */}
                        <div className="flex flex-col gap-4 rounded-lg border bg-card p-6">
                            <h2 className="text-lg font-semibold">
                                Datos de ÉL
                            </h2>
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="el_nombres">Nombres</Label>
                                    <Input
                                        id="el_nombres"
                                        type="text"
                                        required
                                        name="el_nombres"
                                        placeholder="Ingresa los nombres"
                                        value={form.data.el_nombres || ''}
                                        onChange={(e) =>
                                            form.setData(
                                                'el_nombres',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.el_nombres}
                                    />
                                </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="el_apellidos">
                                                    Apellidos
                                                </Label>
                                                <Input
                                                    id="el_apellidos"
                                                    type="text"
                                                    required
                                                    name="el_apellidos"
                                                    placeholder="Ingresa los apellidos"
                                                    value={
                                                        form.data.el_apellidos || ''
                                                    }
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'el_apellidos',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.el_apellidos
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="el_celular">
                                                    Celular
                                                </Label>
                                                <Input
                                                    id="el_celular"
                                                    type="tel"
                                                    required
                                                    name="el_celular"
                                                    placeholder="Ingresa el número de celular"
                                                    value={form.data.el_celular || ''}
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'el_celular',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={form.errors.el_celular}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="el_fecha_nacimiento">
                                                    Fecha de Nacimiento
                                                </Label>
                                                <Input
                                                    id="el_fecha_nacimiento"
                                                    type="date"
                                                    required
                                                    name="el_fecha_nacimiento"
                                                    max={today}
                                                    value={
                                                        form.data.el_fecha_nacimiento
                                                            ? formatDateForInput(
                                                                  form.data.el_fecha_nacimiento,
                                                              )
                                                            : ''
                                                    }
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'el_fecha_nacimiento',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.el_fecha_nacimiento
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="el_email">
                                                    Correo Electrónico
                                                </Label>
                                                <Input
                                                    id="el_email"
                                                    type="email"
                                                    required
                                                    name="el_email"
                                                    placeholder="email@example.com"
                                                    value={form.data.el_email || ''}
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'el_email',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={form.errors.el_email}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="el_foto">
                                                    Foto (Opcional)
                                                </Label>
                                                <Input
                                                    id="el_foto"
                                                    type="file"
                                                    accept="image/*"
                                                    name="el_foto"
                                                    onChange={(e) =>
                                                        handleFotoChange(
                                                            e,
                                                            'el',
                                                        )
                                                    }
                                                />
                                                {elFotoPreview && (
                                                    <img
                                                        src={elFotoPreview}
                                                        alt="Preview"
                                                        className="h-24 w-24 rounded-full object-cover"
                                                    />
                                                )}
                                                <InputError
                                                    message={
                                                        form.errors.el_foto_base64
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <Separator />

                                    {/* Datos de ELLA */}
                                    <div className="flex flex-col gap-4 rounded-lg border bg-card p-6">
                                        <h2 className="text-lg font-semibold">
                                            Datos de ELLA
                                        </h2>
                                        <div className="grid gap-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="ella_nombres">
                                                    Nombres
                                                </Label>
                                                <Input
                                                    id="ella_nombres"
                                                    type="text"
                                                    required
                                                    name="ella_nombres"
                                                    placeholder="Ingresa los nombres"
                                                    value={
                                                        form.data.ella_nombres || ''
                                                    }
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'ella_nombres',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.ella_nombres
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="ella_apellidos">
                                                    Apellidos
                                                </Label>
                                                <Input
                                                    id="ella_apellidos"
                                                    type="text"
                                                    required
                                                    name="ella_apellidos"
                                                    placeholder="Ingresa los apellidos"
                                                    value={
                                                        form.data.ella_apellidos || ''
                                                    }
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'ella_apellidos',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.ella_apellidos
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="ella_celular">
                                                    Celular
                                                </Label>
                                                <Input
                                                    id="ella_celular"
                                                    type="tel"
                                                    required
                                                    name="ella_celular"
                                                    placeholder="Ingresa el número de celular"
                                                    value={
                                                        form.data.ella_celular || ''
                                                    }
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'ella_celular',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.ella_celular
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="ella_fecha_nacimiento">
                                                    Fecha de Nacimiento
                                                </Label>
                                                <Input
                                                    id="ella_fecha_nacimiento"
                                                    type="date"
                                                    required
                                                    name="ella_fecha_nacimiento"
                                                    max={today}
                                                    value={
                                                        form.data.ella_fecha_nacimiento
                                                            ? formatDateForInput(
                                                                  form.data.ella_fecha_nacimiento,
                                                              )
                                                            : ''
                                                    }
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'ella_fecha_nacimiento',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.ella_fecha_nacimiento
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="ella_email">
                                                    Correo Electrónico
                                                </Label>
                                                <Input
                                                    id="ella_email"
                                                    type="email"
                                                    required
                                                    name="ella_email"
                                                    placeholder="email@example.com"
                                                    value={form.data.ella_email || ''}
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'ella_email',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={form.errors.ella_email}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="ella_foto">
                                                    Foto (Opcional)
                                                </Label>
                                                <Input
                                                    id="ella_foto"
                                                    type="file"
                                                    accept="image/*"
                                                    name="ella_foto"
                                                    onChange={(e) =>
                                                        handleFotoChange(
                                                            e,
                                                            'ella',
                                                        )
                                                    }
                                                />
                                                {ellaFotoPreview && (
                                                    <img
                                                        src={ellaFotoPreview}
                                                        alt="Preview"
                                                        className="h-24 w-24 rounded-full object-cover"
                                                    />
                                                )}
                                                <InputError
                                                    message={
                                                        form.errors.ella_foto_base64
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <Separator />

                                    {/* Datos de la Pareja */}
                                    <div className="flex flex-col gap-4 rounded-lg border bg-card p-6">
                                        <h2 className="text-lg font-semibold">
                                            Datos de la Pareja
                                        </h2>
                                        <div className="grid gap-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="fecha_ingreso">
                                                    Fecha de Acogida al
                                                    Movimiento
                                                </Label>
                                                <Input
                                                    id="fecha_ingreso"
                                                    type="date"
                                                    required
                                                    name="fecha_ingreso"
                                                    max={today}
                                                    value={
                                                        form.data.fecha_ingreso
                                                            ? formatDateForInput(
                                                                  form.data.fecha_ingreso,
                                                              )
                                                            : ''
                                                    }
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'fecha_ingreso',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.fecha_ingreso
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="numero_equipo">
                                                    Número del Equipo{' '}
                                                    <span className="text-destructive">
                                                        *
                                                    </span>
                                                </Label>
                                                <Input
                                                    id="numero_equipo"
                                                    type="number"
                                                    required
                                                    min="1"
                                                    name="numero_equipo"
                                                    placeholder="Ingrese el número del equipo"
                                                    value={
                                                        form.data.numero_equipo || ''
                                                    }
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'numero_equipo',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.numero_equipo
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="pareja_foto">
                                                    Foto de la Pareja (Opcional)
                                                </Label>
                                                <Input
                                                    id="pareja_foto"
                                                    type="file"
                                                    accept="image/*"
                                                    name="pareja_foto"
                                                    onChange={(e) =>
                                                        handleFotoChange(
                                                            e,
                                                            'pareja',
                                                        )
                                                    }
                                                />
                                                {parejaFotoPreview && (
                                                    <img
                                                        src={parejaFotoPreview}
                                                        alt="Preview"
                                                        className="h-24 w-24 rounded-full object-cover"
                                                    />
                                                )}
                                                <InputError
                                                    message={
                                                        form.errors.pareja_foto_base64
                                                    }
                                                />
                                            </div>

                                            <Alert className="bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-900">
                                                <Info className="size-4 text-blue-600 dark:text-blue-400" />
                                                <AlertDescription className="text-blue-800 dark:text-blue-200">
                                                    <p className="mb-1 font-medium">
                                                        Información sobre la
                                                        contraseña
                                                    </p>
                                                    <ul className="list-inside list-disc space-y-1 text-sm">
                                                        <li>
                                                            Esta contraseña es
                                                            compartida por la
                                                            pareja.
                                                        </li>
                                                        <li>
                                                            Ambos integrantes
                                                            pueden iniciar sesión
                                                            con cualquiera de los
                                                            dos correos
                                                            electrónicos
                                                            registrado usando esta
                                                            misma contraseña.
                                                        </li>
                                                    </ul>
                                                </AlertDescription>
                                            </Alert>

                                            <div className="grid gap-2">
                                                <Label htmlFor="password">
                                                    Contraseña
                                                </Label>
                                                <Input
                                                    id="password"
                                                    type="password"
                                                    required
                                                    name="password"
                                                    placeholder="Contraseña"
                                                    value={form.data.password || ''}
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'password',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={form.errors.password}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="password_confirmation">
                                                    Confirmar Contraseña
                                                </Label>
                                                <Input
                                                    id="password_confirmation"
                                                    type="password"
                                                    required
                                                    name="password_confirmation"
                                                    placeholder="Confirmar contraseña"
                                                    value={
                                                        form.data.password_confirmation ||
                                                        ''
                                                    }
                                                    onChange={(e) =>
                                                        form.setData(
                                                            'password_confirmation',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        form.errors.password_confirmation
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex flex-col gap-4 sm:flex-row sm:justify-end">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            asChild
                                        >
                                            <a href={parejasIndex().url}>
                                                Cancelar
                                            </a>
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={form.processing}
                                        >
                                            {form.processing && <Spinner />}
                                            Crear Pareja
                                        </Button>
                                    </div>

                                    {form.recentlySuccessful && (
                                        <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/20 dark:text-green-200">
                                            Pareja creada exitosamente.
                                        </div>
                                    )}
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
