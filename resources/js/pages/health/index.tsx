import { Head } from '@inertiajs/react';
import { Activity, CheckCircle2, Database, XCircle } from 'lucide-react';

import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index as healthIndex } from '@/routes/health';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Salud del Sistema',
        href: healthIndex().url,
    },
];

interface ExtensionPHP {
    nombre: string;
    instalada: boolean;
    version: string | null;
    opcional?: boolean;
}

interface BaseDatos {
    conectada: boolean;
    tipo: string | null;
    version: string | null;
    mensaje: string;
}

interface Versiones {
    php: string;
    laravel: string;
    paquetes: Record<string, string>;
}

interface EstadoSalud {
    extensiones_php: ExtensionPHP[];
    base_datos: BaseDatos;
    versiones: Versiones;
}

interface HealthIndexProps {
    estado: EstadoSalud;
}

export default function HealthIndex({ estado }: HealthIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Salud del Sistema" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <HeadingSmall
                    title="Salud del Sistema"
                    description="Estado de dependencias, conexiones y servicios críticos"
                />

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {/* Extensiones PHP */}
                    <Card className="md:col-span-2 lg:col-span-3">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Activity className="h-5 w-5" />
                                Extensiones PHP
                            </CardTitle>
                            <CardDescription>
                                Estado de las extensiones PHP críticas para la aplicación
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {estado.extensiones_php.map((extension) => (
                                    <div
                                        key={extension.nombre}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div className="flex flex-col">
                                            <span className="font-medium text-foreground">
                                                {extension.nombre}
                                            </span>
                                            {extension.version && (
                                                <span className="text-xs text-muted-foreground">
                                                    {typeof extension.version === 'string'
                                                        ? extension.version
                                                        : JSON.stringify(extension.version)}
                                                </span>
                                            )}
                                        </div>
                                        <Badge
                                            variant={
                                                extension.instalada
                                                    ? extension.opcional
                                                        ? 'secondary'
                                                        : 'default'
                                                    : 'destructive'
                                            }
                                        >
                                            {extension.instalada ? (
                                                <>
                                                    <CheckCircle2 className="mr-1 h-3 w-3" />
                                                    {extension.opcional ? 'Opcional' : 'Instalada'}
                                                </>
                                            ) : (
                                                <>
                                                    <XCircle className="mr-1 h-3 w-3" />
                                                    {extension.opcional ? 'Opcional' : 'No instalada'}
                                                </>
                                            )}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Base de Datos */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Database className="h-5 w-5" />
                                Base de Datos
                            </CardTitle>
                            <CardDescription>Estado de la conexión</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">Estado</span>
                                <Badge
                                    variant={estado.base_datos.conectada ? 'default' : 'destructive'}
                                >
                                    {estado.base_datos.conectada ? 'Conectada' : 'Desconectada'}
                                </Badge>
                            </div>
                            {estado.base_datos.tipo && (
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Tipo</span>
                                    <span className="text-sm font-medium">
                                        {estado.base_datos.tipo}
                                    </span>
                                </div>
                            )}
                            {estado.base_datos.version && (
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Versión</span>
                                    <span className="text-sm font-medium">
                                        {estado.base_datos.version}
                                    </span>
                                </div>
                            )}
                            <div className="rounded-lg bg-muted p-3">
                                <p className="text-xs text-muted-foreground">
                                    {estado.base_datos.mensaje}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Versiones */}
                    <Card className="md:col-span-2 lg:col-span-3">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Activity className="h-5 w-5" />
                                Versiones
                            </CardTitle>
                            <CardDescription>Versiones de dependencias principales</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div className="rounded-lg border p-3">
                                    <div className="text-xs text-muted-foreground">PHP</div>
                                    <div className="mt-1 text-lg font-semibold">
                                        {estado.versiones.php}
                                    </div>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <div className="text-xs text-muted-foreground">Laravel</div>
                                    <div className="mt-1 text-lg font-semibold">
                                        {estado.versiones.laravel}
                                    </div>
                                </div>
                                {Object.entries(estado.versiones.paquetes).map(([nombre, version]) => (
                                    <div key={nombre} className="rounded-lg border p-3">
                                        <div className="text-xs text-muted-foreground">
                                            {nombre.split('/').pop()}
                                        </div>
                                        <div className="mt-1 text-lg font-semibold">{version}</div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
