import { Form, Head, router } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle, Search, UserPlus, X } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';

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
    foto_base64: string | null;
    pareja_id: number | null;
    sexo: 'masculino' | 'femenino' | null;
}

interface PasoTresProps {
    user: User;
}

export default function PasoTres({ user }: PasoTresProps) {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<User[]>([]);
    const [searching, setSearching] = useState(false);
    const [selectedPartner, setSelectedPartner] = useState<User | null>(null);
    const [skipPartner, setSkipPartner] = useState(false);
    const searchTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    // Sincronizar datos cuando el usuario cambie
    useEffect(() => {
        if (user.pareja_id) {
            // Si el usuario ya tiene una pareja seleccionada, cargarla
            // Por ahora, solo reseteamos el estado
            setSelectedPartner(null);
            setSkipPartner(false);
        }
    }, [user.pareja_id]);

    const handleSearch = async (query: string) => {
        setSearchQuery(query);
        setSearching(true);

        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }

        if (query.length < 2) {
            setSearchResults([]);
            setSearching(false);
            return;
        }

        searchTimeoutRef.current = setTimeout(async () => {
            try {
                const response = await fetch(
                    `/registro/buscar-usuarios?search=${encodeURIComponent(query)}`,
                );
                const data = await response.json();
                setSearchResults(data);
            } catch (error) {
                console.error(error);
            } finally {
                setSearching(false);
            }
        }, 300);
    };

    useEffect(() => {
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
        };
    }, []);

    return (
        <AuthLayout
            title="Paso 3: Elige tu Esposa/o"
            description="Busca y selecciona a tu pareja o continúa sin ella"
        >
            <Head title="Registro - Paso 3" />
            <div className="w-full max-w-2xl">
                <div className="mb-8 flex items-center justify-center gap-2">
                    {[1, 2, 3, 4].map((s) => (
                        <div key={s} className="flex items-center gap-2">
                            <div
                                className={`h-3 w-3 rounded-full transition-all ${
                                    s <= 3 ? 'bg-primary' : 'bg-muted'
                                }`}
                            />
                            {s < 4 && (
                                <div
                                    className={`h-1 w-8 transition-all ${
                                        s < 3 ? 'bg-primary' : 'bg-muted'
                                    }`}
                                />
                            )}
                        </div>
                    ))}
                </div>

                <Form
                    action="/registro/paso-tres"
                    method="post"
                    className="animate-in fade-in duration-300"
                >
                    {({ processing }) => (
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="search">Buscar Usuario</Label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        type="text"
                                        value={searchQuery}
                                        onChange={(e) => handleSearch(e.target.value)}
                                        placeholder="Busca por nombre, apellido o correo..."
                                        className="pl-10"
                                    />
                                    {searching && (
                                        <LoaderCircle className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
                                    )}
                                </div>
                                {searchResults.length > 0 && (
                                    <div className="mt-2 max-h-48 space-y-2 overflow-y-auto rounded-lg border p-2">
                                        {searchResults.map((result) => (
                                            <button
                                                key={result.id}
                                                type="button"
                                                onClick={() => {
                                                    setSelectedPartner(result);
                                                    setSkipPartner(false);
                                                    setSearchQuery('');
                                                    setSearchResults([]);
                                                }}
                                                className={`w-full rounded-lg border p-3 text-left transition-all ${
                                                    selectedPartner?.id === result.id
                                                        ? 'border-primary bg-primary/10'
                                                        : 'border-border hover:border-primary/50'
                                                }`}
                                            >
                                                <div className="flex items-center gap-3">
                                                    {result.foto_base64 ? (
                                                        <img
                                                            src={result.foto_base64}
                                                            alt={`${result.nombres} ${result.apellidos}`}
                                                            className="h-10 w-10 rounded-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                                            <UserPlus className="h-5 w-5" />
                                                        </div>
                                                    )}
                                                    <div>
                                                        <p className="font-medium">
                                                            {result.nombres} {result.apellidos}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {result.email}
                                                        </p>
                                                    </div>
                                                </div>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {selectedPartner && (
                                <div className="rounded-lg border border-primary bg-primary/10 p-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            {selectedPartner.foto_base64 ? (
                                                <img
                                                    src={selectedPartner.foto_base64}
                                                    alt={`${selectedPartner.nombres} ${selectedPartner.apellidos}`}
                                                    className="h-12 w-12 rounded-full object-cover"
                                                />
                                            ) : (
                                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                                                    <UserPlus className="h-6 w-6" />
                                                </div>
                                            )}
                                            <div>
                                                <p className="font-medium">
                                                    {selectedPartner.nombres}{' '}
                                                    {selectedPartner.apellidos}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {selectedPartner.email}
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setSelectedPartner(null);
                                                setSkipPartner(false);
                                            }}
                                            className="rounded-full p-1 hover:bg-destructive/10"
                                        >
                                            <X className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            )}

                            <input
                                type="hidden"
                                name="pareja_id"
                                value={selectedPartner?.id || ''}
                            />
                            <input
                                type="hidden"
                                name="skip_partner"
                                value={skipPartner ? '1' : '0'}
                            />

                            <Button
                                type="button"
                                onClick={() => {
                                    setSkipPartner(true);
                                    setSelectedPartner(null);
                                }}
                                variant="outline"
                                className="w-full"
                                size="lg"
                            >
                                {user.sexo === 'masculino'
                                    ? 'Aún mi esposa no se registra'
                                    : user.sexo === 'femenino'
                                      ? 'Aún mi esposo no se registra'
                                      : 'Aún mi esposa/o no se registra'}
                            </Button>

                            <div className="mt-2 flex gap-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={async () => {
                                        // Guardar datos automáticamente antes de regresar (si hay cambios)
                                        if (selectedPartner || skipPartner) {
                                            try {
                                                await router.post('/registro/paso-tres', {
                                                    pareja_id: skipPartner ? null : selectedPartner?.id || null,
                                                    skip_partner: skipPartner,
                                                    _preserve: true,
                                                }, {
                                                    preserveScroll: true,
                                                    onSuccess: () => {
                                                        router.get('/registro/paso-dos');
                                                    },
                                                    onError: () => {
                                                        router.get('/registro/paso-dos');
                                                    },
                                                });
                                            } catch {
                                                router.get('/registro/paso-dos');
                                            }
                                        } else {
                                            router.get('/registro/paso-dos');
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
                                    disabled={processing || (!selectedPartner && !skipPartner)}
                                    className="flex-1"
                                    size="lg"
                                >
                                    {processing && <Spinner />}
                                    {skipPartner ? 'Continuar' : 'Enviar Solicitud'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </AuthLayout>
    );
}
