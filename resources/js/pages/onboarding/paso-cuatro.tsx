import { Form, Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';

export default function PasoCuatro() {
    return (
        <AuthLayout
            title="Paso 4: Elige tu Equipo"
            description="Completa tu registro seleccionando tu equipo"
        >
            <Head title="Registro - Paso 4" />
            <div className="w-full max-w-2xl">
                <div className="mb-8 flex items-center justify-center gap-2">
                    {[1, 2, 3, 4].map((s) => (
                        <div key={s} className="flex items-center gap-2">
                            <div
                                className={`h-3 w-3 rounded-full transition-all ${
                                    s <= 4 ? 'bg-primary' : 'bg-muted'
                                }`}
                            />
                            {s < 4 && (
                                <div
                                    className={`h-1 w-8 transition-all ${
                                        s < 4 ? 'bg-primary' : 'bg-muted'
                                    }`}
                                />
                            )}
                        </div>
                    ))}
                </div>

                <Form
                    action="/registro/paso-cuatro"
                    method="post"
                    className="animate-in fade-in duration-300"
                >
                    {({ processing }) => (
                        <div className="grid gap-6">
                            <div className="rounded-lg border border-muted bg-muted/50 p-6 text-center">
                                <p className="mb-4 text-lg font-medium">Próximamente</p>
                                <p className="text-sm text-muted-foreground">
                                    Pronto podrás elegir el sector, la región y la superregión. Por
                                    ahora, continúa con la app de ENS.
                                </p>
                            </div>

                            <input type="hidden" name="equipo_id" value="" />

                            <div className="mt-4 flex gap-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        router.get('/registro/paso-tres');
                                    }}
                                    className="flex-1"
                                    size="lg"
                                >
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    Regresar
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="flex-1"
                                    size="lg"
                                >
                                    {processing && <Spinner />}
                                    Finalizar Registro
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </AuthLayout>
    );
}
