import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import ParejaController from '@/actions/App/Http/Controllers/ParejaController';
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
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { edit as parejasEdit, index as parejasIndex } from '@/routes/parejas';
import { type BreadcrumbItem } from '@/types';

interface UsuarioData {
    id: number;
    nombres: string | null;
    apellidos: string | null;
    cedula: string | null;
    email: string;
    celular: string | null;
    fecha_nacimiento: string | null;
    foto_url: string | null;
    foto_thumbnail_50: string | null;
}

interface EquipoData {
    id: number;
    numero: number;
}

interface ParejaData {
    id: number;
    fecha_acogida: string | null;
    fecha_boda: string | null;
    equipo_id: number | null;
    equipo: EquipoData | null;
    pareja_foto_url: string | null;
    foto_thumbnail_50: string | null;
    estado: 'activo' | 'retirado';
    el: UsuarioData | null;
    ella: UsuarioData | null;
}

interface ParejasEditProps {
    pareja: ParejaData;
    equipos: EquipoData[];
}

const breadcrumbs = (parejaId: number): BreadcrumbItem[] => [
    {
        title: 'Parejas',
        href: parejasIndex().url,
    },
    {
        title: 'Editar Pareja',
        href: parejasEdit({ pareja: parejaId }).url,
    },
];

export default function ParejasEdit({
    pareja: parejaProp,
    equipos,
}: ParejasEditProps) {
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

    // Estados para las fotos: preview puede ser URL (del backend) o blob URL (nueva subida)
    const [elFotoPreview, setElFotoPreview] = useState<string | null>(
        parejaProp.el?.foto_url || null,
    );
    const [ellaFotoPreview, setEllaFotoPreview] = useState<string | null>(
        parejaProp.ella?.foto_url || null,
    );
    const [parejaFotoPreview, setParejaFotoPreview] = useState<string | null>(
        parejaProp.pareja_foto_url || null,
    );

    const elFileInputRef = useRef<HTMLInputElement>(null);
    const ellaFileInputRef = useRef<HTMLInputElement>(null);
    const parejaFileInputRef = useRef<HTMLInputElement>(null);

    const handleFotoChange = (
        e: React.ChangeEvent<HTMLInputElement>,
        tipo: 'el' | 'ella' | 'pareja',
    ) => {
        const file = e.target.files?.[0];
        if (file) {
            // Crear preview con URL del objeto
            const previewUrl = URL.createObjectURL(file);
            
            if (tipo === 'el') {
                setElFotoPreview(previewUrl);
                form.setData('el_foto', file);
            } else if (tipo === 'ella') {
                setEllaFotoPreview(previewUrl);
                form.setData('ella_foto', file);
            } else {
                setParejaFotoPreview(previewUrl);
                form.setData('pareja_foto', file);
            }
        }
    };

    const form = useForm({
        // Pareja
        fecha_acogida: parejaProp.fecha_acogida || '',
        fecha_boda: parejaProp.fecha_boda || '',
        equipo_id: parejaProp.equipo_id ?? null,
        estado: parejaProp.estado,
        pareja_foto: null as File | null,
        // ÉL
        el_id: parejaProp.el?.id.toString() || '',
        el_nombres: parejaProp.el?.nombres || '',
        el_apellidos: parejaProp.el?.apellidos || '',
        el_cedula: parejaProp.el?.cedula || '',
        el_email: parejaProp.el?.email || '',
        el_celular: parejaProp.el?.celular || '',
        el_fecha_nacimiento: parejaProp.el?.fecha_nacimiento || '',
        el_foto: null as File | null,
        // ELLA
        ella_id: parejaProp.ella?.id.toString() || '',
        ella_nombres: parejaProp.ella?.nombres || '',
        ella_apellidos: parejaProp.ella?.apellidos || '',
        ella_cedula: parejaProp.ella?.cedula || '',
        ella_email: parejaProp.ella?.email || '',
        ella_celular: parejaProp.ella?.celular || '',
        ella_fecha_nacimiento: parejaProp.ella?.fecha_nacimiento || '',
        ella_foto: null as File | null,
    });

    // Limpiar URLs de objetos cuando el componente se desmonte o cambien las imágenes
    useEffect(() => {
        return () => {
            if (elFotoPreview && elFotoPreview.startsWith('blob:')) {
                URL.revokeObjectURL(elFotoPreview);
            }
            if (ellaFotoPreview && ellaFotoPreview.startsWith('blob:')) {
                URL.revokeObjectURL(ellaFotoPreview);
            }
            if (parejaFotoPreview && parejaFotoPreview.startsWith('blob:')) {
                URL.revokeObjectURL(parejaFotoPreview);
            }
        };
    }, [elFotoPreview, ellaFotoPreview, parejaFotoPreview]);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(ParejaController.update.url({ pareja: parejaProp.id }), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                // Redirección manejada en el backend
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs(parejaProp.id)}>
            <Head title="Editar Pareja" />

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
                            Editar Pareja
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Actualiza la información de la pareja y sus
                            integrantes
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
                                <input
                                    type="hidden"
                                    name="el_id"
                                    value={form.data.el_id}
                                />

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
                                        value={form.data.el_apellidos || ''}
                                        onChange={(e) =>
                                            form.setData(
                                                'el_apellidos',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.el_apellidos}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="el_cedula">
                                        Cédula (Opcional)
                                    </Label>
                                    <Input
                                        id="el_cedula"
                                        type="text"
                                        name="el_cedula"
                                        placeholder="Ingresa el número de cédula"
                                        value={form.data.el_cedula || ''}
                                        onChange={(e) =>
                                            form.setData(
                                                'el_cedula',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.el_cedula}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="el_celular">Celular</Label>
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
                                        message={form.errors.el_fecha_nacimiento}
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
                                    <input
                                        ref={elFileInputRef}
                                        id="el_foto"
                                        type="file"
                                        accept="image/*"
                                        name="el_foto"
                                        onChange={(e) =>
                                            handleFotoChange(e, 'el')
                                        }
                                        className="hidden"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            elFileInputRef.current?.click()
                                        }
                                    >
                                        {elFotoPreview
                                            ? 'Cambiar Foto'
                                            : 'Subir Foto'}
                                    </Button>
                                    {elFotoPreview && (
                                        <div className="relative mt-2 flex justify-center">
                                            <div className="relative">
                                                <img
                                                    src={elFotoPreview}
                                                    alt="Preview"
                                                    className="h-24 w-24 rounded-full object-cover"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setElFotoPreview(parejaProp.el?.foto_url || null);
                                                        form.setData('el_foto', null);
                                                    }}
                                                    className="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                    <InputError
                                        message={form.errors.el_foto}
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
                                <input
                                    type="hidden"
                                    name="ella_id"
                                    value={form.data.ella_id}
                                />

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
                                        value={form.data.ella_nombres || ''}
                                        onChange={(e) =>
                                            form.setData(
                                                'ella_nombres',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.ella_nombres}
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
                                        value={form.data.ella_apellidos || ''}
                                        onChange={(e) =>
                                            form.setData(
                                                'ella_apellidos',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.ella_apellidos}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="ella_cedula">
                                        Cédula (Opcional)
                                    </Label>
                                    <Input
                                        id="ella_cedula"
                                        type="text"
                                        name="ella_cedula"
                                        placeholder="Ingresa el número de cédula"
                                        value={form.data.ella_cedula || ''}
                                        onChange={(e) =>
                                            form.setData(
                                                'ella_cedula',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.ella_cedula}
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
                                        value={form.data.ella_celular || ''}
                                        onChange={(e) =>
                                            form.setData(
                                                'ella_celular',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.ella_celular}
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
                                    <input
                                        ref={ellaFileInputRef}
                                        id="ella_foto"
                                        type="file"
                                        accept="image/*"
                                        name="ella_foto"
                                        onChange={(e) =>
                                            handleFotoChange(e, 'ella')
                                        }
                                        className="hidden"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            ellaFileInputRef.current?.click()
                                        }
                                    >
                                        {ellaFotoPreview
                                            ? 'Cambiar Foto'
                                            : 'Subir Foto'}
                                    </Button>
                                    {ellaFotoPreview && (
                                        <div className="relative mt-2 flex justify-center">
                                            <div className="relative">
                                                <img
                                                    src={ellaFotoPreview}
                                                    alt="Preview"
                                                    className="h-24 w-24 rounded-full object-cover"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setEllaFotoPreview(null);
                                                        setEllaFotoPreview(parejaProp.ella?.foto_url || null);
                                                        form.setData('ella_foto', null);
                                                    }}
                                                    className="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                    <InputError
                                        message={form.errors.ella_foto}
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
                                    <Label htmlFor="fecha_acogida">
                                        Fecha de Acogida al Movimiento{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Input
                                        id="fecha_acogida"
                                        type="date"
                                        required
                                        name="fecha_acogida"
                                        max={today}
                                        value={
                                            form.data.fecha_acogida
                                                ? formatDateForInput(
                                                      form.data.fecha_acogida,
                                                  )
                                                : ''
                                        }
                                        onChange={(e) =>
                                            form.setData(
                                                'fecha_acogida',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.fecha_acogida}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="fecha_boda">
                                        Fecha de Boda (Opcional)
                                    </Label>
                                    <Input
                                        id="fecha_boda"
                                        type="date"
                                        name="fecha_boda"
                                        max={today}
                                        value={
                                            form.data.fecha_boda
                                                ? formatDateForInput(
                                                      form.data.fecha_boda,
                                                  )
                                                : ''
                                        }
                                        onChange={(e) =>
                                            form.setData(
                                                'fecha_boda',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.fecha_boda}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="equipo_id">Equipo</Label>
                                    <Select
                                        value={
                                            form.data.equipo_id
                                                ? form.data.equipo_id.toString()
                                                : 'none'
                                        }
                                        onValueChange={(value) =>
                                            form.setData(
                                                'equipo_id',
                                                value === 'none' ? null : parseInt(value, 10),
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar equipo (opcional)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                Sin equipo
                                            </SelectItem>
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
                                    <InputError
                                        message={form.errors.equipo_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="estado">
                                        Estado{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Select
                                        value={form.data.estado}
                                        onValueChange={(value) =>
                                            form.setData(
                                                'estado',
                                                value as
                                                    | 'activo'
                                                    | 'retirado',
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="activo">
                                                Activo
                                            </SelectItem>
                                            <SelectItem value="retirado">
                                                Retirado
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.estado}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="pareja_foto">
                                        Foto de la Pareja (Opcional)
                                    </Label>
                                    <input
                                        ref={parejaFileInputRef}
                                        id="pareja_foto"
                                        type="file"
                                        accept="image/*"
                                        name="pareja_foto"
                                        onChange={(e) =>
                                            handleFotoChange(e, 'pareja')
                                        }
                                        className="hidden"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            parejaFileInputRef.current?.click()
                                        }
                                    >
                                        {parejaFotoPreview
                                            ? 'Cambiar Foto'
                                            : 'Subir Foto'}
                                    </Button>
                                    {parejaFotoPreview && (
                                        <div className="relative mt-2 flex justify-center">
                                            <div className="relative">
                                                <img
                                                    src={parejaFotoPreview}
                                                    alt="Preview"
                                                    className="h-24 w-24 rounded-full object-cover"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setParejaFotoPreview(null);
                                                        setParejaFotoPreview(parejaProp.pareja_foto_url || null);
                                                        form.setData('pareja_foto', null);
                                                    }}
                                                    className="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                    <InputError
                                        message={form.errors.pareja_foto}
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
                                <a href={parejasIndex().url}>Cancelar</a>
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                            >
                                {form.processing && <Spinner />}
                                Guardar Cambios
                            </Button>
                        </div>

                        {form.recentlySuccessful && (
                            <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/20 dark:text-green-200">
                                Pareja actualizada exitosamente.
                            </div>
                        )}
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
