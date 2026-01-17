import { router } from '@inertiajs/react';
import { Edit, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { destroy } from '@/routes/calendario';
import { type AlcanceEvento, type TipoEventoCalendario } from '@/types';

interface EventoDetalle {
    id: string;
    title: string;
    start: string;
    end: string;
    allDay: boolean;
    backgroundColor: string;
    extendedProps: {
        descripcion?: string;
        tipo: TipoEventoCalendario;
        alcance: AlcanceEvento;
        equipo_id: number | null;
        icono: string | null;
        creado_por?: {
            id: number;
            nombres: string | null;
            apellidos: string | null;
            email: string;
        } | null;
        equipo?: {
            id: number;
            numero: number;
        } | null;
        usuario?: {
            id: number;
            nombres: string | null;
            apellidos: string | null;
            email: string;
            fecha_nacimiento: string;
            año_nacimiento: number;
        } | null;
        año_cumpleanos?: number;
        edad?: number;
    };
    puede_editar?: boolean;
    puede_eliminar?: boolean;
}

interface EventoDetalleModalProps {
    isOpen: boolean;
    onClose: () => void;
    evento: EventoDetalle | null;
    onEdit: (evento: EventoDetalle) => void;
    onDeleted: () => void;
}

const tipoEventoLabels: Record<TipoEventoCalendario, string> = {
    general: 'Evento General',
    formacion: 'Formación',
    retiro_espiritual: 'Retiro Espiritual',
    reunion_equipo: 'Reunión de Equipo',
    cumpleanos: 'Cumpleaños',
};

const alcanceLabels: Record<AlcanceEvento, string> = {
    equipo: 'Equipo',
    global: 'Global',
};

export default function EventoDetalleModal({
    isOpen,
    onClose,
    evento,
    onEdit,
    onDeleted,
}: EventoDetalleModalProps) {
    if (!evento) {
        return null;
    }

    const handleDelete = () => {
        // Los cumpleaños no se pueden eliminar
        if (evento.extendedProps.tipo === 'cumpleanos') {
            return;
        }

        if (confirm('¿Estás seguro de que deseas eliminar este evento?')) {
            router.delete(destroy.url({ evento: parseInt(evento.id) }), {
                preserveScroll: true,
                onSuccess: () => {
                    onDeleted();
                    onClose();
                },
            });
        }
    };

    const formatDate = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const formatTime = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const fechaInicio = new Date(evento.start);
    const fechaFin = new Date(evento.end);

    // Para eventos de todo el día, la fecha fin es exclusiva, así que restamos un día
    if (evento.allDay) {
        fechaFin.setDate(fechaFin.getDate() - 1);
    }

    const esMismoDia = fechaInicio.toDateString() === fechaFin.toDateString();

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        {evento.title}
                    </DialogTitle>
                    <DialogDescription>
                        {tipoEventoLabels[evento.extendedProps.tipo]} •{' '}
                        {alcanceLabels[evento.extendedProps.alcance]}
                        {evento.extendedProps.equipo && ` • Equipo ${evento.extendedProps.equipo.numero}`}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div>
                        <h3 className="text-sm font-semibold text-muted-foreground mb-1">Fechas</h3>
                        <div className="text-sm">
                            {esMismoDia ? (
                                <div>
                                    <div className="font-medium">
                                        {formatDate(evento.start)}
                                    </div>
                                    {!evento.allDay && (
                                        <div className="text-muted-foreground mt-1">
                                            {formatTime(evento.start)} - {formatTime(evento.end)}
                                        </div>
                                    )}
                                    {evento.allDay && (
                                        <div className="text-muted-foreground mt-1">Todo el día</div>
                                    )}
                                </div>
                            ) : (
                                <div>
                                    <div>
                                        <span className="font-medium">Inicio: </span>
                                        {formatDate(evento.start)}
                                        {!evento.allDay && ` a las ${formatTime(evento.start)}`}
                                    </div>
                                    <div className="mt-1">
                                        <span className="font-medium">Fin: </span>
                                        {formatDate(fechaFin.toISOString())}
                                        {!evento.allDay && ` a las ${formatTime(evento.end)}`}
                                    </div>
                                    {evento.allDay && (
                                        <div className="text-muted-foreground mt-1">Todo el día</div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    {evento.extendedProps.descripcion && (
                        <div>
                            <h3 className="text-sm font-semibold text-muted-foreground mb-1">
                                Descripción
                            </h3>
                            <p className="text-sm whitespace-pre-wrap">
                                {evento.extendedProps.descripcion}
                            </p>
                        </div>
                    )}

                    {evento.extendedProps.tipo === 'cumpleanos' && evento.extendedProps.usuario && (
                        <div>
                            <h3 className="text-sm font-semibold text-muted-foreground mb-1">
                                Información del cumpleañero
                            </h3>
                            <div className="space-y-1 text-sm">
                                <p>
                                    <span className="font-medium">Nombre: </span>
                                    {evento.extendedProps.usuario.nombres} {evento.extendedProps.usuario.apellidos}
                                </p>
                                <p>
                                    <span className="font-medium">Email: </span>
                                    <span className="text-muted-foreground">{evento.extendedProps.usuario.email}</span>
                                </p>
                                <p>
                                    <span className="font-medium">Fecha de nacimiento: </span>
                                    {new Date(evento.extendedProps.usuario.fecha_nacimiento).toLocaleDateString('es-ES', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })}
                                </p>
                                {evento.extendedProps.edad && (
                                    <p>
                                        <span className="font-medium">Cumple: </span>
                                        {evento.extendedProps.edad} {evento.extendedProps.edad === 1 ? 'año' : 'años'}
                                    </p>
                                )}
                            </div>
                        </div>
                    )}

                    {evento.extendedProps.creado_por && evento.extendedProps.tipo !== 'cumpleanos' && (
                        <div>
                            <h3 className="text-sm font-semibold text-muted-foreground mb-1">
                                Creado por
                            </h3>
                            <p className="text-sm">
                                {evento.extendedProps.creado_por.nombres}{' '}
                                {evento.extendedProps.creado_por.apellidos}
                                <span className="text-muted-foreground ml-1">
                                    ({evento.extendedProps.creado_por.email})
                                </span>
                            </p>
                        </div>
                    )}
                </div>

                <DialogFooter className="flex justify-between sm:justify-between">
                    <div>
                        {evento.extendedProps.tipo !== 'cumpleanos' && (evento.puede_editar || evento.puede_eliminar) && (
                            <div className="flex gap-2">
                                {evento.puede_editar && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            onEdit(evento);
                                            onClose();
                                        }}
                                    >
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar
                                    </Button>
                                )}
                                {evento.puede_eliminar && (
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="sm"
                                        onClick={handleDelete}
                                    >
                                        <Trash2 className="mr-2 h-4 w-4" />
                                        Eliminar
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                    <Button type="button" variant="outline" onClick={onClose}>
                        Cerrar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
