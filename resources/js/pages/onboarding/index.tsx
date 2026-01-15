import { Head, router } from '@inertiajs/react';
import { LoaderCircle, Search, UserPlus, X } from 'lucide-react';
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
    celular: string | null;
    fecha_nacimiento: string | null;
    foto_base64: string | null;
    sexo: 'masculino' | 'femenino' | null;
    pareja_id: number | null;
}

interface OnboardingProps {
    user: User;
    currentStep: number;
}

export default function Onboarding({ user, currentStep: initialStep }: OnboardingProps) {
    const [step, setStep] = useState(initialStep);
    const [processing, setProcessing] = useState(false);
    const [fadeOut, setFadeOut] = useState(false);
    const [fadeIn, setFadeIn] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<User[]>([]);
    const [searching, setSearching] = useState(false);
    const searchTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    // Formulario etapa 1
    const [formData, setFormData] = useState({
        nombres: user.nombres || '',
        apellidos: user.apellidos || '',
        celular: user.celular || '',
        fecha_nacimiento: user.fecha_nacimiento || '',
        email: user.email || '',
        password: '',
        password_confirmation: '',
    });

    // Formulario etapa 2
    const [sexo, setSexo] = useState<'masculino' | 'femenino' | null>(user.sexo);
    const [fotoBase64, setFotoBase64] = useState<string | null>(user.foto_base64);
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Formulario etapa 3
    const [selectedPartner, setSelectedPartner] = useState<User | null>(null);
    const [skipPartner, setSkipPartner] = useState(false);

    const transitionStep = (newStep: number, callback?: () => void) => {
        setFadeOut(true);
        setTimeout(() => {
            setStep(newStep);
            setFadeIn(false);
            setTimeout(() => {
                setFadeIn(true);
                setFadeOut(false);
                if (callback) {
                    callback();
                }
            }, 50);
        }, 300);
    };

    const handleStepOne = async () => {
        setProcessing(true);
        try {
            await router.post('/onboarding/step-one', formData);
            transitionStep(2);
        } catch (error) {
            console.error(error);
        } finally {
            setProcessing(false);
        }
    };

    const handleStepTwo = async () => {
        if (!sexo) {
            return;
        }
        setProcessing(true);
        try {
            await router.post('/onboarding/step-two', {
                sexo,
                foto_base64: fotoBase64,
            });
            transitionStep(3);
        } catch (error) {
            console.error(error);
        } finally {
            setProcessing(false);
        }
    };

    const handleStepThree = async () => {
        setProcessing(true);
        try {
            await router.post('/onboarding/step-three', {
                pareja_id: skipPartner ? null : selectedPartner?.id,
                skip_partner: skipPartner,
            });
            transitionStep(4);
        } catch (error) {
            console.error(error);
        } finally {
            setProcessing(false);
        }
    };

    const handleStepFour = async () => {
        setProcessing(true);
        try {
            await router.post('/onboarding/step-four', {
                equipo_id: null,
            });
            router.visit('/dashboard');
        } catch (error) {
            console.error(error);
        } finally {
            setProcessing(false);
        }
    };

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
                const response = await fetch(`/onboarding/search-users?search=${encodeURIComponent(query)}`);
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

    const renderStepOne = () => (
        <div className={`transition-opacity duration-300 ${fadeOut ? 'opacity-0' : 'opacity-100'}`}>
            <h2 className="mb-6 text-2xl font-semibold">Información Personal</h2>
            <div className="grid gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="nombres">Nombres</Label>
                    <Input
                        id="nombres"
                        type="text"
                        value={formData.nombres}
                        onChange={(e) => setFormData({ ...formData, nombres: e.target.value })}
                        required
                        autoFocus
                        placeholder="Ingresa tus nombres"
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="apellidos">Apellidos</Label>
                    <Input
                        id="apellidos"
                        type="text"
                        value={formData.apellidos}
                        onChange={(e) => setFormData({ ...formData, apellidos: e.target.value })}
                        required
                        placeholder="Ingresa tus apellidos"
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="celular">Celular</Label>
                    <Input
                        id="celular"
                        type="tel"
                        value={formData.celular}
                        onChange={(e) => setFormData({ ...formData, celular: e.target.value })}
                        required
                        placeholder="Ingresa tu número de celular"
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="fecha_nacimiento">Fecha de Nacimiento</Label>
                    <Input
                        id="fecha_nacimiento"
                        type="date"
                        value={formData.fecha_nacimiento}
                        onChange={(e) => setFormData({ ...formData, fecha_nacimiento: e.target.value })}
                        required
                        max={new Date().toISOString().split('T')[0]}
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="email">Correo Electrónico (Único)</Label>
                    <Input
                        id="email"
                        type="email"
                        value={formData.email}
                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                        required
                        placeholder="email@example.com"
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password">Contraseña</Label>
                    <Input
                        id="password"
                        type="password"
                        value={formData.password}
                        onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                        required
                        placeholder="Mínimo 8 caracteres"
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Confirmar Contraseña</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        value={formData.password_confirmation}
                        onChange={(e) => setFormData({ ...formData, password_confirmation: e.target.value })}
                        required
                        placeholder="Confirma tu contraseña"
                    />
                </div>
                <Button
                    onClick={handleStepOne}
                    disabled={processing || !formData.nombres || !formData.apellidos || !formData.celular || !formData.fecha_nacimiento || !formData.email || !formData.password || formData.password !== formData.password_confirmation}
                    className="mt-4 w-full"
                    size="lg"
                >
                    {processing && <Spinner />}
                    Continuar
                </Button>
            </div>
        </div>
    );

    const renderStepTwo = () => (
        <div className={`transition-opacity duration-300 ${fadeOut ? 'opacity-0' : 'opacity-100'}`}>
            <h2 className="mb-6 text-2xl font-semibold">Selecciona tu Sexo</h2>
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
                        <div className="relative mt-2">
                            <img
                                src={fotoBase64}
                                alt="Preview"
                                className="h-32 w-32 rounded-lg object-cover"
                            />
                            <button
                                type="button"
                                onClick={() => setFotoBase64(null)}
                                className="absolute -right-2 -top-2 rounded-full bg-destructive p-1 text-destructive-foreground"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                    )}
                </div>
                <Button
                    onClick={handleStepTwo}
                    disabled={processing || !sexo}
                    className="mt-4 w-full"
                    size="lg"
                >
                    {processing && <Spinner />}
                    Continuar
                </Button>
            </div>
        </div>
    );

    const renderStepThree = () => (
        <div className={`transition-opacity duration-300 ${fadeOut ? 'opacity-0' : 'opacity-100'}`}>
            <h2 className="mb-6 text-2xl font-semibold">Elige tu Esposa/o</h2>
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
                                            <p className="text-sm text-muted-foreground">{result.email}</p>
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
                                        {selectedPartner.nombres} {selectedPartner.apellidos}
                                    </p>
                                    <p className="text-sm text-muted-foreground">{selectedPartner.email}</p>
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
                <Button
                    onClick={() => {
                        setSkipPartner(true);
                        setSelectedPartner(null);
                    }}
                    variant="outline"
                    className="w-full"
                    size="lg"
                >
                    Aún mi esposa/o no se registra
                </Button>
                <Button
                    onClick={handleStepThree}
                    disabled={processing || (!selectedPartner && !skipPartner)}
                    className="mt-2 w-full"
                    size="lg"
                >
                    {processing && <Spinner />}
                    {skipPartner ? 'Continuar' : 'Enviar Solicitud'}
                </Button>
            </div>
        </div>
    );

    const renderStepFour = () => (
        <div className={`transition-opacity duration-300 ${fadeOut ? 'opacity-0' : 'opacity-100'}`}>
            <h2 className="mb-6 text-2xl font-semibold">Elige tu Equipo</h2>
            <div className="grid gap-6">
                <div className="rounded-lg border border-muted bg-muted/50 p-6 text-center">
                    <p className="mb-4 text-lg font-medium">Próximamente</p>
                    <p className="text-sm text-muted-foreground">
                        Pronto podrás elegir el sector, la región y la superregión. Por ahora, continúa con la
                        app de ENS.
                    </p>
                </div>
                <Button
                    onClick={handleStepFour}
                    disabled={processing}
                    className="mt-4 w-full"
                    size="lg"
                >
                    {processing && <Spinner />}
                    Finalizar Registro
                </Button>
            </div>
        </div>
    );

    return (
        <AuthLayout
            title="Completa tu Registro"
            description="Sigue los pasos para completar tu perfil"
        >
            <Head title="Registro - Onboarding" />
            <div className="w-full max-w-2xl">
                <div className="mb-8 flex items-center justify-center gap-2">
                    {[1, 2, 3, 4].map((s) => (
                        <div key={s} className="flex items-center gap-2">
                            <div
                                className={`h-3 w-3 rounded-full transition-all ${
                                    s <= step ? 'bg-primary' : 'bg-muted'
                                }`}
                            />
                            {s < 4 && (
                                <div
                                    className={`h-1 w-8 transition-all ${
                                        s < step ? 'bg-primary' : 'bg-muted'
                                    }`}
                                />
                            )}
                        </div>
                    ))}
                </div>
                <div className={`transition-opacity duration-300 ${fadeIn ? 'opacity-100' : 'opacity-0'}`}>
                    {step === 1 && renderStepOne()}
                    {step === 2 && renderStepTwo()}
                    {step === 3 && renderStepThree()}
                    {step === 4 && renderStepFour()}
                </div>
            </div>
        </AuthLayout>
    );
}
