import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useState, useRef } from 'react';

import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración de perfil',
        href: edit().url,
    },
];

interface ProfileUser {
    id: number;
    nombres: string | null;
    apellidos: string | null;
    celular: string | null;
    fecha_nacimiento: string | null;
    sexo: 'masculino' | 'femenino' | null;
    foto_base64: string | null;
    email: string;
    email_verified_at: string | null;
}

interface ProfileProps {
    mustVerifyEmail: boolean;
    status?: string;
    user: ProfileUser;
}

export default function Profile({
    mustVerifyEmail,
    status,
    user: userProp,
}: ProfileProps) {
    const { auth } = usePage<SharedData>().props;

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

    const [fotoBase64, setFotoBase64] = useState<string | null>(userProp.foto_base64);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onloadend = () => {
                const base64String = reader.result as string;
                setFotoBase64(base64String);
            };
            reader.readAsDataURL(file);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración de perfil" />

            <h1 className="sr-only">Configuración de Perfil</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Información del perfil"
                        description="Actualiza tu información personal y de contacto"
                    />

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                {/* Sección: Información Personal */}
                                <div className="space-y-4 rounded-lg border p-6">
                                    <h2 className="text-lg font-semibold">
                                        Información Personal
                                    </h2>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="nombres">
                                                Nombres <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="nombres"
                                                className="mt-1 block w-full"
                                                defaultValue={userProp.nombres || ''}
                                                name="nombres"
                                                required
                                                autoComplete="given-name"
                                                placeholder="Nombres"
                                            />
                                            <InputError
                                                className="mt-2"
                                                message={errors.nombres}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="apellidos">
                                                Apellidos <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="apellidos"
                                                className="mt-1 block w-full"
                                                defaultValue={userProp.apellidos || ''}
                                                name="apellidos"
                                                required
                                                autoComplete="family-name"
                                                placeholder="Apellidos"
                                            />
                                            <InputError
                                                className="mt-2"
                                                message={errors.apellidos}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="celular">
                                                Celular <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="celular"
                                                type="tel"
                                                className="mt-1 block w-full"
                                                defaultValue={userProp.celular || ''}
                                                name="celular"
                                                required
                                                autoComplete="tel"
                                                placeholder="Celular"
                                            />
                                            <InputError
                                                className="mt-2"
                                                message={errors.celular}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="fecha_nacimiento">
                                                Fecha de Nacimiento{' '}
                                                <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="fecha_nacimiento"
                                                type="date"
                                                className="mt-1 block w-full"
                                                defaultValue={formatDateForInput(
                                                    userProp.fecha_nacimiento,
                                                )}
                                                name="fecha_nacimiento"
                                                required
                                                autoComplete="bday"
                                            />
                                            <InputError
                                                className="mt-2"
                                                message={errors.fecha_nacimiento}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="sexo">Sexo</Label>
                                        <div className="grid grid-cols-2 gap-4">
                                            <label className="flex cursor-pointer items-center gap-2 rounded-lg border p-3 transition-all hover:border-primary/50">
                                                <input
                                                    type="radio"
                                                    name="sexo"
                                                    value="masculino"
                                                    defaultChecked={userProp.sexo === 'masculino'}
                                                    className="h-4 w-4"
                                                />
                                                <span className="text-2xl">♂</span>
                                                <span className="font-medium">Masculino</span>
                                            </label>
                                            <label className="flex cursor-pointer items-center gap-2 rounded-lg border p-3 transition-all hover:border-primary/50">
                                                <input
                                                    type="radio"
                                                    name="sexo"
                                                    value="femenino"
                                                    defaultChecked={userProp.sexo === 'femenino'}
                                                    className="h-4 w-4"
                                                />
                                                <span className="text-2xl">♀</span>
                                                <span className="font-medium">Femenino</span>
                                            </label>
                                        </div>
                                        <InputError
                                            className="mt-2"
                                            message={errors.sexo}
                                        />
                                    </div>
                                </div>

                                {/* Sección: Fotografía */}
                                <div className="space-y-4 rounded-lg border p-6">
                                    <h2 className="text-lg font-semibold">Fotografía</h2>

                                    <div className="grid gap-2">
                                        <Label htmlFor="foto">Fotografía de Perfil</Label>
                                        <input
                                            ref={fileInputRef}
                                            id="foto"
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
                                            {fotoBase64
                                                ? 'Cambiar Fotografía'
                                                : 'Subir Fotografía'}
                                        </Button>
                                        {fotoBase64 && (
                                            <div className="relative mt-2 flex justify-center">
                                                <div className="relative">
                                                    <img
                                                        src={fotoBase64}
                                                        alt="Preview"
                                                        className="h-32 w-32 rounded-lg object-cover"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => setFotoBase64(null)}
                                                        className="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                        aria-label="Eliminar fotografía"
                                                    >
                                                        <X className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        )}
                                        <input
                                            type="hidden"
                                            name="foto_base64"
                                            value={fotoBase64 || ''}
                                        />
                                        <InputError
                                            className="mt-2"
                                            message={errors.foto_base64}
                                        />
                                    </div>
                                </div>

                                {/* Sección: Información de Cuenta */}
                                <div className="space-y-4 rounded-lg border p-6">
                                    <h2 className="text-lg font-semibold">
                                        Información de Cuenta
                                    </h2>


                                    <div className="grid gap-2">
                                        <Label htmlFor="email">
                                            Correo electrónico{' '}
                                            <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            className="mt-1 block w-full"
                                            defaultValue={auth.user.email}
                                            name="email"
                                            required
                                            autoComplete="username"
                                            placeholder="Correo electrónico"
                                        />
                                        <InputError
                                            className="mt-2"
                                            message={errors.email}
                                        />
                                    </div>

                                    {mustVerifyEmail &&
                                        auth.user.email_verified_at === null && (
                                            <div>
                                                <p className="-mt-4 text-sm text-muted-foreground">
                                                    Tu dirección de correo electrónico no está
                                                    verificada.{' '}
                                                    <Link
                                                        href={send()}
                                                        as="button"
                                                        className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                    >
                                                        Haz clic aquí para reenviar el correo de
                                                        verificación.
                                                    </Link>
                                                </p>

                                                {status === 'verification-link-sent' && (
                                                    <div className="mt-2 text-sm font-medium text-green-600">
                                                        Se ha enviado un nuevo enlace de verificación
                                                        a tu dirección de correo electrónico.
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
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
                        )}
                    </Form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
