import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { type SharedData } from '@/types';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    useEffect(() => {
        // Forzar tema claro en la landing page
        document.documentElement.classList.remove('dark');
        document.documentElement.style.colorScheme = 'light';
    }, []);

    return (
        <>
            <Head title="Equipos de Nuestra Señora" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-background p-6">
                <div className="flex w-full max-w-md flex-col items-center gap-8 text-center">
                    <div className="flex flex-col items-center gap-6">
                        <img
                            src="/logo.svg"
                            alt="Equipos de Nuestra Señora"
                            className="h-auto w-full max-w-xs"
                        />
                        <h1 className="text-3xl font-bold text-foreground">
                            Equipos de Nuestra Señora
                        </h1>
                    </div>

                    <div className="flex w-full flex-col gap-4 sm:flex-row sm:justify-center">
                        {auth.user ? (
                            <Button asChild size="lg" className="w-full sm:w-auto">
                                <Link href={dashboard()}>Panel de control</Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="outline" size="lg" className="w-full sm:w-auto">
                                    <Link href="/login">Iniciar sesión</Link>
                                </Button>
                                {canRegister && (
                                    <Button asChild size="lg" className="w-full sm:w-auto">
                                        <Link href="/register">Registrar Pareja</Link>
                                    </Button>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
