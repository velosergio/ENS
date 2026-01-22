import { router } from '@inertiajs/react';
import { Calendar as CalendarIcon, Cake, BookOpen, Church, Users, Clock, Link as LinkIcon, Heart } from 'lucide-react';
import { useMemo } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { index as calendarioIndex, show as calendarioShow } from '@/routes/calendario';
import { type TipoEventoCalendario } from '@/types';

interface EventoProximo {
    id: string;
    titulo: string;
    fecha_inicio: string;
    fecha_fin?: string;
    allDay: boolean;
    tipo: TipoEventoCalendario;
    alcance: 'equipo' | 'global';
    color: string;
    icono?: string | null;
}

interface AgendaCardProps {
    eventos: EventoProximo[];
}

// Mapeo de nombres de iconos a componentes
const iconosMap: Record<string, React.ComponentType<{ className?: string }>> = {
    Calendar: CalendarIcon,
    BookOpen: BookOpen,
    Church: Church,
    Users: Users,
    Cake: Cake,
    Heart: Heart,
};

// Obtener componente de icono
function getIconComponent(iconoNombre: string | null | undefined): React.ComponentType<{ className?: string }> | null {
    if (!iconoNombre || !iconosMap[iconoNombre]) {
        return null;
    }
    return iconosMap[iconoNombre];
}

// Formatear fecha para mostrar
function formatearFecha(fechaStr: string, allDay: boolean): string {
    // Extraer solo la fecha si viene con hora
    const fechaSolo = fechaStr.includes('T') ? fechaStr.split('T')[0] : fechaStr;
    
    if (allDay) {
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const fechaEvento = new Date(fechaSolo);
        fechaEvento.setHours(0, 0, 0, 0);
        
        const diferenciaDias = Math.floor((fechaEvento.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24));
        
        if (diferenciaDias === 0) {
            return 'Hoy';
        } else if (diferenciaDias === 1) {
            return 'Mañana';
        } else if (diferenciaDias === -1) {
            return 'Ayer';
        }
        
        return fechaEvento.toLocaleDateString('es-CO', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
        });
    } else {
        // Si tiene hora, parsear la fecha completa
        const fechaCompleta = new Date(fechaStr);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const fechaEvento = new Date(fechaCompleta);
        fechaEvento.setHours(0, 0, 0, 0);
        
        const diferenciaDias = Math.floor((fechaEvento.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24));
        
        const fechaParte = diferenciaDias === 0
            ? 'Hoy'
            : diferenciaDias === 1
              ? 'Mañana'
              : diferenciaDias === -1
                ? 'Ayer'
                : fechaCompleta.toLocaleDateString('es-CO', {
                      weekday: 'short',
                      day: 'numeric',
                      month: 'short',
                  });
        
        const horaParte = fechaCompleta.toLocaleTimeString('es-CO', {
            hour: '2-digit',
            minute: '2-digit',
        });
        
        return `${fechaParte} a las ${horaParte}`;
    }
}

export default function AgendaCard({ eventos }: AgendaCardProps) {
    // Agrupar eventos por fecha
    const eventosAgrupados = useMemo(() => {
        const grupos: Record<string, EventoProximo[]> = {};
        
        eventos.forEach((evento) => {
            // Extraer solo la fecha (sin hora para agrupar)
            const fechaKey = evento.fecha_inicio.includes('T')
                ? evento.fecha_inicio.split('T')[0]
                : evento.fecha_inicio;
            
            if (!grupos[fechaKey]) {
                grupos[fechaKey] = [];
            }
            grupos[fechaKey].push(evento);
        });
        
        // Ordenar las fechas
        const fechasOrdenadas = Object.keys(grupos).sort();
        
        return fechasOrdenadas.map((fecha) => ({
            fecha,
            eventos: grupos[fecha],
        }));
    }, [eventos]);

    const handleVerEvento = (eventoId: string) => {
        // Si es un cumpleaños, no abrir modal (son generados automáticamente)
        if (eventoId.startsWith('cumpleanos_')) {
            return;
        }
        
        // Para eventos normales, abrir detalle
        router.visit(calendarioShow.url({ evento: parseInt(eventoId) }), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    if (eventos.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <CalendarIcon className="h-5 w-5" />
                        Próximos Eventos
                    </CardTitle>
                    <CardDescription>Eventos programados para los próximos 14 días</CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">No hay eventos próximos.</p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <CalendarIcon className="h-5 w-5" />
                    Próximos Eventos
                </CardTitle>
                <CardDescription>Eventos programados para los próximos 14 días</CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-4">
                    {eventosAgrupados.map((grupo) => (
                        <div key={grupo.fecha} className="space-y-2">
                            <h3 className="text-sm font-semibold text-muted-foreground">
                                {formatearFecha(grupo.fecha, true)}
                            </h3>
                            <div className="space-y-2">
                                {grupo.eventos.map((evento) => {
                                    const IconComponent = getIconComponent(evento.icono);
                                    
                                    return (
                                        <div
                                            key={evento.id}
                                            className="flex items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-accent"
                                        >
                                            <div
                                                className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded"
                                                style={{ backgroundColor: evento.color }}
                                            >
                                                {IconComponent ? (
                                                    <IconComponent className="h-4 w-4 text-white" />
                                                ) : (
                                                    <CalendarIcon className="h-4 w-4 text-white" />
                                                )}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="flex-1 min-w-0">
                                                        <button
                                                            type="button"
                                                            onClick={() => handleVerEvento(evento.id)}
                                                            className="text-left text-sm font-medium hover:underline"
                                                        >
                                                            {evento.titulo}
                                                        </button>
                                                        <div className="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                                                            {!evento.allDay && (
                                                                <span className="flex items-center gap-1">
                                                                    <Clock className="h-3 w-3" />
                                                                    {formatearFecha(evento.fecha_inicio, evento.allDay)}
                                                                </span>
                                                            )}
                                                            {evento.allDay && (
                                                                <span>{formatearFecha(evento.fecha_inicio, evento.allDay)}</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>
                <div className="mt-4">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="w-full"
                        onClick={() => router.visit(calendarioIndex().url)}
                    >
                        <LinkIcon className="mr-2 h-4 w-4" />
                        Ver calendario completo
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
