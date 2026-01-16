import { Transition } from '@headlessui/react';
import { Head, router, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useState, useRef, useEffect } from 'react';

import ParejaController from '@/actions/App/Http/Controllers/Settings/ParejaController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import SettingsLayout from '@/layouts/settings/layout';
import { edit, retirar as retirarPareja } from '@/routes/pareja';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración de pareja',
        href: edit().url,
    },
];

interface EquipoData {
    id: number;
    numero: number;
}

interface ParejaData {
    id: number;
    fecha_ingreso: string | null;
    equipo_id: number | null;
    equipo: EquipoData | null;
    foto_url: string | null;
    estado: 'activo' | 'retirado';
}

interface ParejaProps {
    pareja: ParejaData;
    equipos: EquipoData[];
}

export default function Pareja({
    pareja: parejaProp,
    equipos,
}: ParejaProps) {
    // Función helper para formatear fecha de ISO a yyyy-MM-dd
    const formatDateForInput = (dateString: string | null | undefined): string => {
        if (!dateString) return '';
        // Si ya está en formato yyyy-MM-dd, devolverlo tal cual
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString;
        // Si es un string ISO, extraer solo la parte de la fecha
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toISOString().split('T')[0];
    };

    const today = new Date().toISOString().split('T')[0];

    // Estado para la foto: puede ser URL (del backend) o blob URL (nueva subida)
    const [fotoPreview, setFotoPreview] = useState<string | null>(parejaProp.foto_url);
    const [fotoFile, setFotoFile] = useState<File | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            const previewUrl = URL.createObjectURL(file);
            setFotoFile(file);
            setFotoPreview(previewUrl);
        }
    };

    // Limpiar URLs de objetos cuando el componente se desmonte
    useEffect(() => {
        return () => {
            if (fotoPreview && fotoPreview.startsWith('blob:')) {
                URL.revokeObjectURL(fotoPreview);
            }
        };
    }, [fotoPreview]);

    const form = useForm({
        fecha_ingreso: formatDateForInput(parejaProp.fecha_ingreso),
        equipo_id: parejaProp.equipo_id?.toString() || '',
        pareja_foto: null as File | null,
    });

    // Sincronizar fotoFile con form cuando cambia
    useEffect(() => {
        form.setData('pareja_foto', fotoFile);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [fotoFile]);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(ParejaController.update.url(), {
            preserveScroll: true,
            forceFormData: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración de pareja" />

            <h1 className="sr-only">Configuración de Pareja</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Información de la pareja"
                        description="Actualiza la información de la pareja"
                    />

                    <form onSubmit={submit} className="space-y-6">
                        {(() => {
                            const { processing, recentlySuccessful, errors } = form;
                            return (
                            <>
                                {/* Sección: Información de la Pareja */}
                                <div className="space-y-4 rounded-lg border p-6">
                                    <h2 className="text-lg font-semibold">
                                        Información de la Pareja
                                    </h2>

                                    <div className="grid gap-2">
                                        <Label htmlFor="fecha_ingreso">
                                            Fecha de Acogida al Movimiento{' '}
                                            <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="fecha_ingreso"
                                            type="date"
                                            className="mt-1 block w-full"
                                            value={form.data.fecha_ingreso}
                                            onChange={(e) => form.setData('fecha_ingreso', e.target.value)}
                                            name="fecha_ingreso"
                                            required
                                            max={today}
                                        />
                                        <InputError
                                            className="mt-2"
                                            message={errors.fecha_ingreso}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="equipo_id">
                                            Equipo
                                        </Label>
                                        <Select
                                            value={
                                                form.data.equipo_id === '' || form.data.equipo_id === 'none'
                                                    ? 'none'
                                                    : form.data.equipo_id
                                            }
                                            onValueChange={(value) => {
                                                form.setData('equipo_id', value === 'none' ? '' : value);
                                            }}
                                        >
                                            <SelectTrigger className="mt-1 block w-full">
                                                <SelectValue placeholder="Seleccionar equipo (opcional)" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">
                                                    Sin equipo
                                                </SelectItem>
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
                                        <InputError
                                            className="mt-2"
                                            message={errors.equipo_id}
                                        />
                                    </div>
                                </div>

                                {/* Sección: Fotografía */}
                                <div className="space-y-4 rounded-lg border p-6">
                                    <h2 className="text-lg font-semibold">
                                        Fotografía de la Pareja
                                    </h2>

                                    <div className="grid gap-2">
                                        <Label htmlFor="pareja_foto">
                                            Fotografía de la Pareja
                                        </Label>
                                        <input
                                            ref={fileInputRef}
                                            id="pareja_foto"
                                            type="file"
                                            accept="image/*"
                                            onChange={handleFileChange}
                                            className="hidden"
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => fileInputRef.current?.click()}
                                            className="w-full"
                                        >
                                            {fotoPreview
                                                ? 'Cambiar Fotografía'
                                                : 'Subir Fotografía'}
                                        </Button>
                                        {fotoPreview && (
                                            <div className="relative mt-2 flex justify-center">
                                                <div className="relative">
                                                    <img
                                                        src={fotoPreview}
                                                        alt="Preview"
                                                        className="h-32 w-32 rounded-lg object-cover"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setFotoPreview(parejaProp.foto_url);
                                                            setFotoFile(null);
                                                        }}
                                                        className="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                        aria-label="Eliminar fotografía"
                                                    >
                                                        <X className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        )}
                                        <InputError
                                            className="mt-2"
                                            message={errors.pareja_foto}
                                        />
                                    </div>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-pareja-button"
                                    >
                                        Guardar Cambios
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">Guardado</p>
                                    </Transition>
                                </div>
                            </>
                        );
                        })()}
                    </form>

                    {/* Sección: Retirarse del Movimiento */}
                    {parejaProp.estado === 'activo' && (
                        <div className="space-y-6">
                            <HeadingSmall
                                title="Retirarse del Movimiento"
                                description="Retira a tu pareja del movimiento permanentemente"
                            />
                            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                                    <p className="font-medium">Advertencia</p>
                                    <p className="text-sm">
                                        Al retirarse del movimiento, perderán el acceso a la
                                        plataforma inmediatamente. Solo un administrador podrá
                                        reactivar su pareja.
                                    </p>
                                </div>

                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button
                                            variant="destructive"
                                            data-test="retirar-pareja-button"
                                        >
                                            Retirarse del Movimiento
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogTitle>
                                            ¿Estás seguro de que deseas retirar tu pareja del
                                            movimiento?
                                        </DialogTitle>
                                        <DialogDescription>
                                            Al confirmar esta acción, tu pareja será marcada como
                                            retirada y ambos usuarios perderán acceso inmediatamente
                                            a la plataforma. Serán redirigidos a la página de login
                                            y no podrán iniciar sesión hasta que un administrador
                                            reactive su pareja.
                                            <br />
                                            <br />
                                            Esta acción no elimina los datos de la pareja, solo
                                            restringe el acceso.
                                        </DialogDescription>

                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button variant="secondary">
                                                    Cancelar
                                                </Button>
                                            </DialogClose>

                                            <Button
                                                variant="destructive"
                                                onClick={() => {
                                                    router.post(retirarPareja().url, {}, {
                                                        onSuccess: () => {
                                                            // El logout y redirección se manejan en el backend
                                                        },
                                                    });
                                                }}
                                                data-test="confirm-retirar-pareja-button"
                                            >
                                                Confirmar Retiro
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
