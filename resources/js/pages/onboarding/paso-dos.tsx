import { Form, Head, router } from '@inertiajs/react';
import { ArrowLeft, X } from 'lucide-react';
import { useState, useRef } from 'react';

import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';

interface User {
    id: number;
    sexo: 'masculino' | 'femenino' | null;
    foto_base64: string | null;
}

interface PasoDosProps {
    user: User;
}

export default function PasoDos({ user }: PasoDosProps) {
    // Usar función de inicialización lazy para sincronizar con props
    const [sexo, setSexo] = useState<'masculino' | 'femenino' | null>(() => user.sexo);
    const [fotoBase64, setFotoBase64] = useState<string | null>(() => user.foto_base64);
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Actualizar estado solo si las props cambian (usando key en el componente padre)
    // Inertia recargará el componente cuando navegamos, así que el estado se inicializará correctamente

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
        <AuthLayout
            title="Paso 2: Selecciona tu Sexo"
            description="Elige tu sexo y sube una fotografía opcional"
        >
            <Head title="Registro - Paso 2" />
            <div key={`paso-dos-${user.id}-${user.sexo}-${user.foto_base64 ? 'has-photo' : 'no-photo'}`} className="w-full max-w-2xl">
                <div className="mb-8 flex items-center justify-center gap-2">
                    {[1, 2, 3, 4].map((s) => (
                        <div key={s} className="flex items-center gap-2">
                            <div
                                className={`h-3 w-3 rounded-full transition-all ${
                                    s <= 2 ? 'bg-primary' : 'bg-muted'
                                }`}
                            />
                            {s < 4 && (
                                <div
                                    className={`h-1 w-8 transition-all ${
                                        s < 2 ? 'bg-primary' : 'bg-muted'
                                    }`}
                                />
                            )}
                        </div>
                    ))}
                </div>

                <Form
                    action="/registro/paso-dos"
                    method="post"
                    className="animate-in fade-in duration-300"
                >
                    {({ processing }) => (
                        <div className="grid gap-6">
                            <div className="grid grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    onClick={() => setSexo('masculino')}
                                    className={`flex h-24 flex-col items-center justify-center gap-2 rounded-lg border-2 transition-all ${
                                        sexo === 'masculino'
                                            ? 'border-primary bg-primary/10'
                                            : 'border-border hover:border-primary/50'
                                    }`}
                                >
                                    <span className="text-4xl">♂</span>
                                    <span className="font-medium">Masculino</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setSexo('femenino')}
                                    className={`flex h-24 flex-col items-center justify-center gap-2 rounded-lg border-2 transition-all ${
                                        sexo === 'femenino'
                                            ? 'border-primary bg-primary/10'
                                            : 'border-border hover:border-primary/50'
                                    }`}
                                >
                                    <span className="text-4xl">♀</span>
                                    <span className="font-medium">Femenino</span>
                                </button>
                            </div>
                            <input type="hidden" name="sexo" value={sexo || ''} />

                            <div className="grid gap-2">
                                <Label htmlFor="foto">Fotografía (Opcional)</Label>
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
                                    {fotoBase64 ? 'Cambiar Fotografía' : 'Subir Fotografía'}
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
                            </div>

                            <div className="mt-4 flex gap-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={async () => {
                                        // Guardar datos automáticamente antes de regresar (si hay cambios)
                                        if (sexo || fotoBase64) {
                                            try {
                                                await router.post('/registro/paso-dos', {
                                                    sexo: sexo || null,
                                                    foto_base64: fotoBase64 || null,
                                                    _preserve: true,
                                                }, {
                                                    preserveScroll: true,
                                                    onSuccess: () => {
                                                        router.get('/registro/paso-uno');
                                                    },
                                                    onError: () => {
                                                        // Si hay error, navegar de todas formas
                                                        router.get('/registro/paso-uno');
                                                    },
                                                });
                                            } catch {
                                                router.get('/registro/paso-uno');
                                            }
                                        } else {
                                            router.get('/registro/paso-uno');
                                        }
                                    }}
                                    className="flex-1"
                                    size="lg"
                                >
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    Regresar
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing || !sexo}
                                    className="flex-1"
                                    size="lg"
                                >
                                    {processing && <Spinner />}
                                    Continuar al Paso 3
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </AuthLayout>
    );
}
