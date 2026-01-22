import { useForm, Head } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        el_nombres: '',
        el_apellidos: '',
        el_celular: '',
        el_fecha_nacimiento: '',
        el_email: '',
        el_foto: null as File | null,
        ella_nombres: '',
        ella_apellidos: '',
        ella_celular: '',
        ella_fecha_nacimiento: '',
        ella_email: '',
        ella_foto: null as File | null,
        fecha_acogida: '',
        fecha_boda: '',
        el_cedula: '',
        ella_cedula: '',
        equipo_id: '',
        pareja_foto: null as File | null,
        password: '',
        password_confirmation: '',
    });
    const [elFotoPreview, setElFotoPreview] = useState<string | null>(null);
    const [ellaFotoPreview, setEllaFotoPreview] = useState<string | null>(null);
    const [parejaFotoPreview, setParejaFotoPreview] = useState<string | null>(null);

    const handleFotoChange = (
        e: React.ChangeEvent<HTMLInputElement>,
        tipo: 'el' | 'ella' | 'pareja',
    ) => {
        const file = e.target.files?.[0];
        if (file) {
            // Crear preview
            const reader = new FileReader();
            reader.onloadend = () => {
                const preview = reader.result as string;
                if (tipo === 'el') {
                    setElFotoPreview(preview);
                    setData('el_foto', file);
                } else if (tipo === 'ella') {
                    setEllaFotoPreview(preview);
                    setData('ella_foto', file);
                } else {
                    setParejaFotoPreview(preview);
                    setData('pareja_foto', file);
                }
            };
            reader.readAsDataURL(file);
        }
    };

    const formatDateForInput = (dateString: string | null | undefined): string => {
        if (!dateString) return '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString;
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toISOString().split('T')[0];
    };

    const today = new Date().toISOString().split('T')[0];

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/register', {
            forceFormData: true,
            onSuccess: () => {
                reset('password', 'password_confirmation');
            },
        });
    }

    return (
        <AuthLayout
            title="Registro de Pareja"
            description="Completa los datos de ambos integrantes de la pareja"
        >
            <Head title="Registro de Pareja" />
            <form onSubmit={submit} className="flex flex-col gap-6">
                <>
                        <div className="flex flex-col gap-6">
                            {/* Datos de ÉL */}
                            <div className="flex flex-col gap-4 rounded-lg border p-4">
                                <h2 className="text-lg font-semibold">Datos de ÉL</h2>
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="el_nombres">Nombres</Label>
                                        <Input
                                            id="el_nombres"
                                            type="text"
                                            required
                                            name="el_nombres"
                                            placeholder="Ingresa los nombres"
                                            value={data.el_nombres || ''}
                                            onChange={(e) =>
                                                setData('el_nombres', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.el_nombres} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="el_apellidos">Apellidos</Label>
                                        <Input
                                            id="el_apellidos"
                                            type="text"
                                            required
                                            name="el_apellidos"
                                            placeholder="Ingresa los apellidos"
                                            value={data.el_apellidos || ''}
                                            onChange={(e) =>
                                                setData('el_apellidos', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.el_apellidos} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="el_cedula">Cédula (Opcional)</Label>
                                        <Input
                                            id="el_cedula"
                                            type="text"
                                            name="el_cedula"
                                            placeholder="Ingresa el número de cédula"
                                            value={data.el_cedula || ''}
                                            onChange={(e) =>
                                                setData('el_cedula', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.el_cedula} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="el_celular">Celular</Label>
                                        <Input
                                            id="el_celular"
                                            type="tel"
                                            required
                                            name="el_celular"
                                            placeholder="Ingresa el número de celular"
                                            value={data.el_celular || ''}
                                            onChange={(e) =>
                                                setData('el_celular', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.el_celular} />
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
                                                data.el_fecha_nacimiento
                                                    ? formatDateForInput(data.el_fecha_nacimiento)
                                                    : ''
                                            }
                                            onChange={(e) =>
                                                setData('el_fecha_nacimiento', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.el_fecha_nacimiento} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="el_email">Correo Electrónico</Label>
                                        <Input
                                            id="el_email"
                                            type="email"
                                            required
                                            name="el_email"
                                            placeholder="email@example.com"
                                            value={data.el_email || ''}
                                            onChange={(e) =>
                                                setData('el_email', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.el_email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="el_foto">Foto (Opcional)</Label>
                                        <Input
                                            id="el_foto"
                                            type="file"
                                            accept="image/*"
                                            name="el_foto"
                                            onChange={(e) => handleFotoChange(e, 'el')}
                                        />
                                        {elFotoPreview && (
                                            <img
                                                src={elFotoPreview}
                                                alt="Preview"
                                                className="h-24 w-24 rounded-full object-cover"
                                            />
                                        )}
                                        <InputError message={errors.el_foto} />
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            {/* Datos de ELLA */}
                            <div className="flex flex-col gap-4 rounded-lg border p-4">
                                <h2 className="text-lg font-semibold">Datos de ELLA</h2>
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="ella_nombres">Nombres</Label>
                                        <Input
                                            id="ella_nombres"
                                            type="text"
                                            required
                                            name="ella_nombres"
                                            placeholder="Ingresa los nombres"
                                            value={data.ella_nombres || ''}
                                            onChange={(e) =>
                                                setData('ella_nombres', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.ella_nombres} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="ella_apellidos">Apellidos</Label>
                                        <Input
                                            id="ella_apellidos"
                                            type="text"
                                            required
                                            name="ella_apellidos"
                                            placeholder="Ingresa los apellidos"
                                            value={data.ella_apellidos || ''}
                                            onChange={(e) =>
                                                setData('ella_apellidos', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.ella_apellidos} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="ella_cedula">Cédula (Opcional)</Label>
                                        <Input
                                            id="ella_cedula"
                                            type="text"
                                            name="ella_cedula"
                                            placeholder="Ingresa el número de cédula"
                                            value={data.ella_cedula || ''}
                                            onChange={(e) =>
                                                setData('ella_cedula', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.ella_cedula} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="ella_celular">Celular</Label>
                                        <Input
                                            id="ella_celular"
                                            type="tel"
                                            required
                                            name="ella_celular"
                                            placeholder="Ingresa el número de celular"
                                            value={data.ella_celular || ''}
                                            onChange={(e) =>
                                                setData('ella_celular', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.ella_celular} />
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
                                                data.ella_fecha_nacimiento
                                                    ? formatDateForInput(data.ella_fecha_nacimiento)
                                                    : ''
                                            }
                                            onChange={(e) =>
                                                setData('ella_fecha_nacimiento', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.ella_fecha_nacimiento} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="ella_email">Correo Electrónico</Label>
                                        <Input
                                            id="ella_email"
                                            type="email"
                                            required
                                            name="ella_email"
                                            placeholder="email@example.com"
                                            value={data.ella_email || ''}
                                            onChange={(e) =>
                                                setData('ella_email', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.ella_email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="ella_foto">Foto (Opcional)</Label>
                                        <Input
                                            id="ella_foto"
                                            type="file"
                                            accept="image/*"
                                            name="ella_foto"
                                            onChange={(e) => handleFotoChange(e, 'ella')}
                                        />
                                        {ellaFotoPreview && (
                                            <img
                                                src={ellaFotoPreview}
                                                alt="Preview"
                                                className="h-24 w-24 rounded-full object-cover"
                                            />
                                        )}
                                        <InputError message={errors.ella_foto} />
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            {/* Datos de la Pareja */}
                            <div className="flex flex-col gap-4 rounded-lg border p-4">
                                <h2 className="text-lg font-semibold">Datos de la Pareja</h2>
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="fecha_acogida">
                                            Fecha de Acogida al Movimiento
                                        </Label>
                                        <Input
                                            id="fecha_acogida"
                                            type="date"
                                            required
                                            name="fecha_acogida"
                                            max={today}
                                            value={
                                                data.fecha_acogida
                                                    ? formatDateForInput(data.fecha_acogida)
                                                    : ''
                                            }
                                            onChange={(e) =>
                                                setData('fecha_acogida', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.fecha_acogida} />
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
                                                data.fecha_boda
                                                    ? formatDateForInput(data.fecha_boda)
                                                    : ''
                                            }
                                            onChange={(e) =>
                                                setData('fecha_boda', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.fecha_boda} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="pareja_foto">Foto de la Pareja (Opcional)</Label>
                                        <Input
                                            id="pareja_foto"
                                            type="file"
                                            accept="image/*"
                                            name="pareja_foto"
                                            onChange={(e) => handleFotoChange(e, 'pareja')}
                                        />
                                        {parejaFotoPreview && (
                                            <img
                                                src={parejaFotoPreview}
                                                alt="Preview"
                                                className="h-24 w-24 rounded-full object-cover"
                                            />
                                        )}
                                        <InputError message={errors.pareja_foto} />
                                    </div>

                                    <Alert className="bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-900">
                                        <Info className="size-4 text-blue-600 dark:text-blue-400" />
                                        <AlertDescription className="text-blue-800 dark:text-blue-200">
                                            <p className="font-medium mb-1">
                                                Información sobre la contraseña
                                            </p>
                                            <ul className="list-inside list-disc space-y-1 text-sm">
                                                <li>
                                                    Esta contraseña es compartida por la pareja.
                                                </li>
                                                <li>
                                                    Ambos integrantes pueden iniciar sesión con
                                                    cualquiera de los dos correos electrónicos
                                                    registrado usando esta
                                                    misma contraseña.
                                                </li>
                                            </ul>
                                        </AlertDescription>
                                    </Alert>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">Contraseña</Label>
                                        <Input
                                            id="password"
                                            type="password"
                                            required
                                            name="password"
                                            placeholder="Contraseña"
                                            value={data.password || ''}
                                            onChange={(e) =>
                                                setData('password', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.password} />
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
                                            value={data.password_confirmation || ''}
                                            onChange={(e) =>
                                                setData('password_confirmation', e.target.value)
                                            }
                                        />
                                        <InputError message={errors.password_confirmation} />
                                    </div>
                                </div>
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                                data-test="register-pareja-button"
                            >
                                {processing && <Spinner />}
                                Registrar Pareja
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            ¿Ya tienes una cuenta?{' '}
                            <TextLink href="/login">
                                Inicia sesión
                            </TextLink>
                        </div>
                </>
            </form>
        </AuthLayout>
    );
}