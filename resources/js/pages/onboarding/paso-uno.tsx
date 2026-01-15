import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';

interface User {
    id: number;
    nombres: string | null;
    apellidos: string | null;
    email: string;
    celular: string | null;
    fecha_nacimiento: string | null;
}

interface PasoUnoProps {
    user: User | null;
}

export default function PasoUno({ user }: PasoUnoProps) {
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

    // Usar función de inicialización lazy para sincronizar con props
    const [formData, setFormData] = useState(() => ({
        nombres: user?.nombres || '',
        apellidos: user?.apellidos || '',
        celular: user?.celular || '',
        fecha_nacimiento: formatDateForInput(user?.fecha_nacimiento),
        email: user?.email || '',
        password: '',
        password_confirmation: '',
    }));

    return (
        <AuthLayout
            title="Paso 1: Información Personal"
            description="Completa tus datos básicos para continuar"
        >
            <Head title="Registro - Paso 1" />
            <div key={`paso-uno-${user?.id || 'new'}-${user?.email || ''}`} className="w-full max-w-2xl">
                <div className="mb-8 flex items-center justify-center gap-2">
                    {[1, 2, 3, 4].map((s) => (
                        <div key={s} className="flex items-center gap-2">
                            <div
                                className={`h-3 w-3 rounded-full transition-all ${
                                    s === 1 ? 'bg-primary' : s < 1 ? 'bg-primary' : 'bg-muted'
                                }`}
                            />
                            {s < 4 && (
                                <div
                                    className={`h-1 w-8 transition-all ${
                                        s < 1 ? 'bg-primary' : 'bg-muted'
                                    }`}
                                />
                            )}
                        </div>
                    ))}
                </div>

                <Form
                    action="/registro/paso-uno"
                    method="post"
                    className="animate-in fade-in duration-300"
                >
                    {({ processing, errors }) => (
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="nombres">Nombres</Label>
                                <Input
                                    id="nombres"
                                    type="text"
                                    value={formData.nombres}
                                    onChange={(e) =>
                                        setFormData({ ...formData, nombres: e.target.value })
                                    }
                                    name="nombres"
                                    required
                                    autoFocus
                                    placeholder="Ingresa tus nombres"
                                />
                                <InputError message={errors.nombres} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="apellidos">Apellidos</Label>
                                <Input
                                    id="apellidos"
                                    type="text"
                                    value={formData.apellidos}
                                    onChange={(e) =>
                                        setFormData({ ...formData, apellidos: e.target.value })
                                    }
                                    name="apellidos"
                                    required
                                    placeholder="Ingresa tus apellidos"
                                />
                                <InputError message={errors.apellidos} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="celular">Celular</Label>
                                <Input
                                    id="celular"
                                    type="tel"
                                    value={formData.celular}
                                    onChange={(e) =>
                                        setFormData({ ...formData, celular: e.target.value })
                                    }
                                    name="celular"
                                    required
                                    placeholder="Ingresa tu número de celular"
                                />
                                <InputError message={errors.celular} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="fecha_nacimiento">Fecha de Nacimiento</Label>
                                <Input
                                    id="fecha_nacimiento"
                                    type="date"
                                    value={formData.fecha_nacimiento}
                                    onChange={(e) =>
                                        setFormData({
                                            ...formData,
                                            fecha_nacimiento: e.target.value,
                                        })
                                    }
                                    name="fecha_nacimiento"
                                    required
                                    max={new Date().toISOString().split('T')[0]}
                                />
                                <InputError message={errors.fecha_nacimiento} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Correo Electrónico (Único)</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={formData.email}
                                    onChange={(e) =>
                                        setFormData({ ...formData, email: e.target.value })
                                    }
                                    name="email"
                                    required
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Contraseña</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={formData.password}
                                    onChange={(e) =>
                                        setFormData({ ...formData, password: e.target.value })
                                    }
                                    name="password"
                                    required
                                    placeholder="Mínimo 8 caracteres"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">Confirmar Contraseña</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={formData.password_confirmation}
                                    onChange={(e) =>
                                        setFormData({
                                            ...formData,
                                            password_confirmation: e.target.value,
                                        })
                                    }
                                    name="password_confirmation"
                                    required
                                    placeholder="Confirma tu contraseña"
                                />
                                <InputError message={errors.password_confirmation} />
                            </div>

                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    !formData.nombres ||
                                    !formData.apellidos ||
                                    !formData.celular ||
                                    !formData.fecha_nacimiento ||
                                    !formData.email ||
                                    !formData.password ||
                                    formData.password !== formData.password_confirmation
                                }
                                className="mt-4 w-full"
                                size="lg"
                            >
                                {processing && <Spinner />}
                                Continuar al Paso 2
                            </Button>
                        </div>
                    )}
                </Form>
            </div>
        </AuthLayout>
    );
}
