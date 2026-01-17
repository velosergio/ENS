import { router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { store, update } from '@/routes/calendario';
import { type AlcanceEvento, type TipoEventoCalendario } from '@/types';

interface Equipo {
    id: number;
    numero: number;
}

interface EventoModalProps {
    isOpen: boolean;
    onClose: () => void;
    fechaInicio?: string; // YYYY-MM-DD para crear desde fecha específica
    eventoEditar?: {
        id: number;
        titulo: string;
        descripcion: string | null;
        fecha_inicio: string;
        fecha_fin: string;
        hora_inicio: string | null;
        hora_fin: string | null;
        es_todo_el_dia: boolean;
        tipo: TipoEventoCalendario;
        alcance: AlcanceEvento;
        equipo_id: number | null;
    };
    equipos: Equipo[];
    puedeGlobal: boolean; // mango/admin
    onSuccess: () => void;
}

export default function EventoModal({
    isOpen,
    onClose,
    fechaInicio,
    eventoEditar,
    equipos,
    puedeGlobal,
    onSuccess,
}: EventoModalProps) {
    const form = useForm({
        titulo: eventoEditar?.titulo || '',
        descripcion: eventoEditar?.descripcion || '',
        fecha_inicio: eventoEditar?.fecha_inicio || fechaInicio || new Date().toISOString().split('T')[0],
        fecha_fin: eventoEditar?.fecha_fin || fechaInicio || new Date().toISOString().split('T')[0],
        hora_inicio: eventoEditar?.hora_inicio || '',
        hora_fin: eventoEditar?.hora_fin || '',
        es_todo_el_dia: eventoEditar?.es_todo_el_dia ?? true,
        tipo: (eventoEditar?.tipo || 'general') as TipoEventoCalendario,
        alcance: (eventoEditar?.alcance || 'equipo') as AlcanceEvento,
        equipo_id: eventoEditar?.equipo_id?.toString() || '',
    });

    // Reset form cuando cambia el modal o evento a editar
    useEffect(() => {
        if (isOpen) {
            if (eventoEditar) {
                form.setData({
                    titulo: eventoEditar.titulo,
                    descripcion: eventoEditar.descripcion || '',
                    fecha_inicio: eventoEditar.fecha_inicio,
                    fecha_fin: eventoEditar.fecha_fin,
                    hora_inicio: eventoEditar.hora_inicio || '',
                    hora_fin: eventoEditar.hora_fin || '',
                    es_todo_el_dia: eventoEditar.es_todo_el_dia,
                    tipo: eventoEditar.tipo,
                    alcance: eventoEditar.alcance,
                    equipo_id: eventoEditar.equipo_id?.toString() || '',
                });
            } else {
                const fecha = fechaInicio || new Date().toISOString().split('T')[0];
                form.reset();
                form.setData({
                    titulo: '',
                    descripcion: '',
                    fecha_inicio: fecha,
                    fecha_fin: fecha,
                    hora_inicio: '',
                    hora_fin: '',
                    es_todo_el_dia: true,
                    tipo: 'general',
                    alcance: 'equipo',
                    equipo_id: '',
                });
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isOpen, eventoEditar?.id]);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        // Preparar datos para enviar (convertir tipos según lo esperado por el backend)
        const dataToSubmit = {
            titulo: form.data.titulo,
            descripcion: form.data.descripcion || null,
            fecha_inicio: form.data.fecha_inicio,
            fecha_fin: form.data.fecha_fin,
            hora_inicio: form.data.es_todo_el_dia ? null : (form.data.hora_inicio || null),
            hora_fin: form.data.es_todo_el_dia ? null : (form.data.hora_fin || null),
            es_todo_el_dia: form.data.es_todo_el_dia,
            tipo: form.data.tipo,
            alcance: form.data.alcance,
            equipo_id: form.data.alcance === 'equipo' ? (form.data.equipo_id ? parseInt(form.data.equipo_id) : null) : null,
        };

        if (eventoEditar) {
            router.patch(update.url({ evento: eventoEditar.id }), dataToSubmit, {
                preserveScroll: true,
                onSuccess: () => {
                    onSuccess();
                    onClose();
                },
            });
        } else {
            router.post(store.url(), dataToSubmit, {
                preserveScroll: true,
                onSuccess: () => {
                    onSuccess();
                    onClose();
                },
            });
        }
    }

    const handleClose = () => {
        form.reset();
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && handleClose()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {eventoEditar ? 'Editar evento' : 'Nuevo evento'}
                    </DialogTitle>
                    <DialogDescription>
                        {eventoEditar
                            ? 'Actualiza la información del evento'
                            : 'Completa los datos para crear un nuevo evento'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="titulo">
                            Título <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="titulo"
                            value={form.data.titulo}
                            onChange={(e) => form.setData('titulo', e.target.value)}
                            required
                        />
                        <InputError message={form.errors.titulo} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="descripcion">Descripción</Label>
                        <textarea
                            id="descripcion"
                            value={form.data.descripcion}
                            onChange={(e) => form.setData('descripcion', e.target.value)}
                            rows={3}
                            className="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        />
                        <InputError message={form.errors.descripcion} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="fecha_inicio">
                                Fecha inicio <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="fecha_inicio"
                                type="date"
                                value={form.data.fecha_inicio}
                                onChange={(e) => form.setData('fecha_inicio', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.fecha_inicio} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="fecha_fin">
                                Fecha fin <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="fecha_fin"
                                type="date"
                                value={form.data.fecha_fin}
                                onChange={(e) => form.setData('fecha_fin', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.fecha_fin} />
                        </div>
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="es_todo_el_dia"
                            checked={form.data.es_todo_el_dia}
                            onCheckedChange={(checked) => {
                                form.setData('es_todo_el_dia', checked as boolean);
                                if (checked) {
                                    form.setData('hora_inicio', '');
                                    form.setData('hora_fin', '');
                                }
                            }}
                        />
                        <Label
                            htmlFor="es_todo_el_dia"
                            className="text-sm font-normal cursor-pointer"
                        >
                            Todo el día
                        </Label>
                    </div>

                    {!form.data.es_todo_el_dia && (
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="hora_inicio">Hora inicio</Label>
                                <Input
                                    id="hora_inicio"
                                    type="time"
                                    value={form.data.hora_inicio}
                                    onChange={(e) => form.setData('hora_inicio', e.target.value)}
                                />
                                <InputError message={form.errors.hora_inicio} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="hora_fin">Hora fin</Label>
                                <Input
                                    id="hora_fin"
                                    type="time"
                                    value={form.data.hora_fin}
                                    onChange={(e) => form.setData('hora_fin', e.target.value)}
                                />
                                <InputError message={form.errors.hora_fin} />
                            </div>
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="tipo">
                            Tipo <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={form.data.tipo}
                            onValueChange={(value) => form.setData('tipo', value as TipoEventoCalendario)}
                        >
                            <SelectTrigger id="tipo">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="general">Evento General</SelectItem>
                                <SelectItem value="formacion">Formación</SelectItem>
                                <SelectItem value="retiro_espiritual">Retiro Espiritual</SelectItem>
                                <SelectItem value="reunion_equipo">Reunión de Equipo</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.tipo} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="alcance">
                            Alcance <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={form.data.alcance}
                            onValueChange={(value) => form.setData('alcance', value as AlcanceEvento)}
                            disabled={!puedeGlobal && form.data.alcance === 'global'}
                        >
                            <SelectTrigger id="alcance">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="equipo">Equipo</SelectItem>
                                {puedeGlobal && <SelectItem value="global">Global</SelectItem>}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.alcance} />
                    </div>

                    {form.data.alcance === 'equipo' && equipos.length > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="equipo_id">Equipo</Label>
                            <Select
                                value={form.data.equipo_id}
                                onValueChange={(value) => form.setData('equipo_id', value)}
                            >
                                <SelectTrigger id="equipo_id">
                                    <SelectValue placeholder="Seleccionar equipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    {equipos.map((equipo) => (
                                        <SelectItem key={equipo.id} value={equipo.id.toString()}>
                                            Equipo {equipo.numero}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.equipo_id} />
                        </div>
                    )}

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Guardando...' : eventoEditar ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
