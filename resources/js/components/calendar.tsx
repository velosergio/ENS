import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import { Download } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import EventoDetalleModal from '@/components/evento-detalle-modal';
import EventoModal from '@/components/evento-modal';
import FiltrosCalendario from '@/components/filtros-calendario';
import { Button } from '@/components/ui/button';
import { events as eventsRoute, exportar as exportarRoute, show as showEvento, updateFecha as updateFechaRoute, updateAniversarioFecha as updateAniversarioFechaRoute } from '@/routes/calendario';
import { type TipoEventoCalendario } from '@/types';

// Función helper para obtener el token CSRF
function getCsrfToken(): string | null {
    // Primero intentar obtener del meta tag (más confiable)
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        const token = metaTag.getAttribute('content');
        if (token) {
            return token;
        }
    }

    // Si no está en el meta tag, intentar obtener de las cookies (Laravel usa XSRF-TOKEN)
    // Laravel codifica el token en la cookie, necesitamos decodificarlo
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
        const trimmed = cookie.trim();
        const equalIndex = trimmed.indexOf('=');
        if (equalIndex === -1) {
            continue;
        }
        
        const name = trimmed.substring(0, equalIndex);
        const value = trimmed.substring(equalIndex + 1);
        
        if (name === 'XSRF-TOKEN' && value) {
            // Laravel codifica el valor de la cookie, necesitamos decodificarlo
            try {
                return decodeURIComponent(value);
            } catch {
                // Si falla el decode, devolver el valor original
                return value;
            }
        }
    }

    return null;
}

interface CalendarEvent {
    id: string;
    title: string;
    start: string;
    end: string;
    allDay: boolean;
    backgroundColor: string;
    borderColor: string;
    textColor: string;
    extendedProps: {
        descripcion?: string;
        tipo: string;
        alcance: string;
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
    };
    puede_editar?: boolean;
    puede_eliminar?: boolean;
}

interface Equipo {
    id: number;
    numero: number;
}

interface CalendarProps {
    equipos?: Equipo[];
    puedeGlobal?: boolean;
}

type ViewType = 'dayGridMonth' | 'timeGridWeek' | 'timeGridDay' | 'listWeek';

export default function Calendar({ equipos = [], puedeGlobal = false }: CalendarProps) {
    const calendarRef = useRef<FullCalendar>(null);
    const [currentView, setCurrentView] = useState<ViewType>('dayGridMonth');
    const [eventos, setEventos] = useState<CalendarEvent[]>([]);
    const [eventosCompletos, setEventosCompletos] = useState<CalendarEvent[]>([]); // Todos los eventos sin filtrar
    const [loading, setLoading] = useState(false);
    const [tiposFiltrados, setTiposFiltrados] = useState<TipoEventoCalendario[]>([]);

    // Estados para modales
    const [modalCrearAbierto, setModalCrearAbierto] = useState(false);
    const [modalDetalleAbierto, setModalDetalleAbierto] = useState(false);
    const [modalEditarAbierto, setModalEditarAbierto] = useState(false);
    const [fechaInicioCrear, setFechaInicioCrear] = useState<string>();
    const [eventoDetalle, setEventoDetalle] = useState<CalendarEvent | null>(null);
    const [eventoEditar, setEventoEditar] = useState<{
        id: number;
        titulo: string;
        descripcion: string | null;
        fecha_inicio: string;
        fecha_fin: string;
        hora_inicio: string | null;
        hora_fin: string | null;
        es_todo_el_dia: boolean;
        tipo: 'general' | 'formacion' | 'retiro_espiritual' | 'reunion_equipo';
        alcance: 'equipo' | 'global';
        equipo_id: number | null;
    } | null>(null);

    const fetchEvents = async (start: Date, end: Date) => {
        setLoading(true);
        try {
            const startStr = start.toISOString().split('T')[0];
            const endStr = end.toISOString().split('T')[0];

            const response = await fetch(
                `${eventsRoute.url()}?start=${startStr}&end=${endStr}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                },
            );

            if (response.ok) {
                const data = await response.json();
                setEventosCompletos(data);
            }
        } catch (error) {
            console.error('Error al cargar eventos:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleDatesSet = (arg: { start: Date; end: Date; view: { type: string } }) => {
        fetchEvents(arg.start, arg.end);
    };

    const handleViewChange = (viewType: ViewType) => {
        setCurrentView(viewType);
        const calendarApi = calendarRef.current?.getApi();
        if (calendarApi) {
            calendarApi.changeView(viewType);
        }
    };

    const handleDateClick = (arg: { date: Date; allDay: boolean }) => {
        const fecha = arg.date.toISOString().split('T')[0];
        setFechaInicioCrear(fecha);
        setModalCrearAbierto(true);
    };

    const handleEventClick = async (info: { event: { id: string } }): Promise<void> => {
        const eventId = info.event.id;
        
        // Si es un cumpleaños o aniversario (ID empieza con "cumpleanos_" o "aniversario_"), usar los datos del evento directamente
        if (eventId.startsWith('cumpleanos_') || eventId.startsWith('aniversario_')) {
            const eventoData = eventos.find((e) => e.id === eventId);
            if (eventoData) {
                setEventoDetalle(eventoData);
                setModalDetalleAbierto(true);
            }
            return;
        }

        // Para eventos normales, cargar desde el servidor
        try {
            const eventoIdNum = parseInt(eventId);
            if (isNaN(eventoIdNum)) {
                console.error('ID de evento inválido:', eventId);
                return;
            }

            const response = await fetch(showEvento.url({ evento: eventoIdNum }), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const evento = await response.json();
                setEventoDetalle(evento);
                setModalDetalleAbierto(true);
            }
        } catch (error) {
            console.error('Error al cargar detalle del evento:', error);
        }
    };

    const handleRefresh = () => {
        const calendarApi = calendarRef.current?.getApi();
        if (calendarApi) {
            const view = calendarApi.view;
            fetchEvents(view.activeStart, view.activeEnd);
        }
    };

    // Filtrar eventos según los tipos seleccionados
    const eventosFiltrados = useMemo(() => {
        if (tiposFiltrados.length === 0 || tiposFiltrados.length === 7) {
            // Si no hay filtros o todos están seleccionados, mostrar todos
            return eventosCompletos;
        }

        return eventosCompletos.filter((evento) => {
            const tipo = evento.extendedProps.tipo as TipoEventoCalendario;
            return tiposFiltrados.includes(tipo);
        });
    }, [eventosCompletos, tiposFiltrados]);

    // Actualizar eventos cuando cambian los filtrados
    useEffect(() => {
        setEventos(eventosFiltrados);
    }, [eventosFiltrados]);

    const handleEventDrop = async (info: { event: { id: string; start: Date | null; end: Date | null; allDay: boolean }; revert: () => void }) => {
        const eventId = info.event.id;

        // No permitir arrastrar cumpleaños
        if (eventId.startsWith('cumpleanos_')) {
            info.revert();
            return;
        }

        if (!info.event.start) {
            info.revert();
            return;
        }

        try {
            // Formatear fechas correctamente: solo fecha YYYY-MM-DD para eventos de todo el día,
            // o fecha completa ISO para eventos con hora
            let startStr: string;
            let endStr: string | null = null;

            if (info.event.allDay) {
                // Para eventos de todo el día, solo fecha
                startStr = info.event.start.toISOString().split('T')[0];
                if (info.event.end) {
                    // Para eventos de todo el día, FullCalendar usa fecha exclusiva (siguiente día)
                    // Necesitamos restar un día para obtener la fecha real de fin
                    const endDate = new Date(info.event.end);
                    endDate.setDate(endDate.getDate() - 1);
                    endStr = endDate.toISOString().split('T')[0];
                }
            } else {
                // Para eventos con hora, usar formato ISO completo
                startStr = info.event.start.toISOString();
                if (info.event.end) {
                    endStr = info.event.end.toISOString();
                }
            }

            const allDay = info.event.allDay;

            const csrfToken = getCsrfToken();
            if (!csrfToken) {
                console.error('No se pudo obtener el token CSRF');
                info.revert();
                return;
            }

            const headers: HeadersInit = {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            };

            // Verificar si es un aniversario (formato: aniversario_boda_{pareja_id}_{año} o aniversario_acogida_{pareja_id}_{año})
            const isAniversario = eventId.startsWith('aniversario_boda_') || eventId.startsWith('aniversario_acogida_');

            let response: Response;
            if (isAniversario) {
                // Para aniversarios, usar la ruta específica y enviar el ID completo como string
                response = await fetch(updateAniversarioFechaRoute().url, {
                    method: 'POST',
                    headers,
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        id: eventId,
                        start: startStr,
                        allDay,
                    }),
                });
            } else {
                // Para eventos normales, convertir eventId a número
                const eventoIdNum = parseInt(eventId);
                if (isNaN(eventoIdNum)) {
                    info.revert();
                    return;
                }

                response = await fetch(updateFechaRoute.url({ evento: eventoIdNum }), {
                    method: 'POST',
                    headers,
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        start: startStr,
                        end: endStr,
                        allDay,
                    }),
                });
            }

            if (!response.ok) {
                // Si la respuesta no es OK, revertir el movimiento
                let errorMessage = 'Error desconocido';
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.error || errorData.message || JSON.stringify(errorData);
                } catch {
                    errorMessage = `Error ${response.status}: ${response.statusText}`;
                }
                console.error('Error al actualizar fecha:', errorMessage);
                info.revert();
                return;
            }

            // Si la respuesta es exitosa, no revertir - FullCalendar mantendrá la nueva posición
            // Refrescar eventos en el siguiente tick para asegurar que la UI se actualice
            setTimeout(() => {
                const calendarApi = calendarRef.current?.getApi();
                if (calendarApi) {
                    const view = calendarApi.view;
                    fetchEvents(view.activeStart, view.activeEnd);
                }
            }, 100);
        } catch (error) {
            // En caso de error de red u otro error, revertir
            console.error('[handleEventDrop] Error en catch:', error);
            info.revert();
        }
    };

    const handleEventResize = async (info: { event: { id: string; start: Date | null; end: Date | null; allDay: boolean }; revert: () => void }) => {
        const eventId = info.event.id;

        // No permitir redimensionar cumpleaños
        if (eventId.startsWith('cumpleanos_')) {
            info.revert();
            return;
        }

        if (!info.event.start || !info.event.end) {
            info.revert();
            return;
        }

        try {
            // Formatear fechas correctamente
            let startStr: string;
            let endStr: string;

            if (info.event.allDay) {
                // Para eventos de todo el día, solo fecha
                startStr = info.event.start.toISOString().split('T')[0];
                // Para eventos de todo el día, FullCalendar usa fecha exclusiva (siguiente día)
                const endDate = new Date(info.event.end!);
                endDate.setDate(endDate.getDate() - 1);
                endStr = endDate.toISOString().split('T')[0];
            } else {
                // Para eventos con hora, usar formato ISO completo
                startStr = info.event.start.toISOString();
                endStr = info.event.end!.toISOString();
            }

            // Convertir eventId a número (para eventos normales)
            const eventoIdNum = parseInt(eventId);
            if (isNaN(eventoIdNum)) {
                info.revert();
                return;
            }

            const csrfToken = getCsrfToken();
            if (!csrfToken) {
                console.error('No se pudo obtener el token CSRF');
                info.revert();
                return;
            }

            const headers: HeadersInit = {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            };

            const response = await fetch(updateFechaRoute.url({ evento: eventoIdNum }), {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify({
                    start: startStr,
                    end: endStr,
                    allDay: info.event.allDay,
                }),
            });

            if (!response.ok) {
                // Si la respuesta no es OK, revertir el cambio
                let errorMessage = 'Error desconocido';
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.error || errorData.message || JSON.stringify(errorData);
                } catch {
                    errorMessage = `Error ${response.status}: ${response.statusText}`;
                }
                console.error('Error al actualizar duración:', errorMessage);
                info.revert();
                return;
            }

            // Si la respuesta es exitosa, no revertir
            // Refrescar eventos en el siguiente tick
            setTimeout(() => {
                const calendarApi = calendarRef.current?.getApi();
                if (calendarApi) {
                    const view = calendarApi.view;
                    fetchEvents(view.activeStart, view.activeEnd);
                }
            }, 100);
        } catch (error) {
            // En caso de error de red u otro error, revertir
            console.error('Error al redimensionar evento:', error);
            info.revert();
        }
    };

    const handleExportar = useCallback(() => {
        const calendarApi = calendarRef.current?.getApi();
        if (!calendarApi) {
            return;
        }

        const view = calendarApi.view;
        const start = view.activeStart.toISOString().split('T')[0];
        const end = view.activeEnd.toISOString().split('T')[0];

        const url = `${exportarRoute.url()}?start=${start}&end=${end}`;
        window.open(url, '_blank');
    }, []);

    const handleFiltrosChange = useCallback((tipos: TipoEventoCalendario[]) => {
        setTiposFiltrados(tipos);
    }, []);

    const handleEditarDesdeDetalle = (evento: CalendarEvent) => {
        // No permitir editar cumpleaños o aniversarios desde el modal de detalle
        // Los aniversarios se editan arrastrándolos en el calendario
        if (evento.id.startsWith('cumpleanos_') || evento.id.startsWith('aniversario_')) {
            return;
        }

        // Convertir el evento del detalle al formato de edición
        const eventoParaEditar = {
            id: parseInt(evento.id),
            titulo: evento.title,
            descripcion: evento.extendedProps.descripcion || null,
            fecha_inicio: evento.start.split('T')[0],
            fecha_fin: evento.allDay
                ? new Date(new Date(evento.end).getTime() - 24 * 60 * 60 * 1000).toISOString().split('T')[0]
                : evento.end.split('T')[0],
            hora_inicio: evento.allDay ? null : (evento.start.includes('T') ? evento.start.split('T')[1].slice(0, 5) : null),
            hora_fin: evento.allDay ? null : (evento.end.includes('T') ? evento.end.split('T')[1].slice(0, 5) : null),
            es_todo_el_dia: evento.allDay,
            tipo: evento.extendedProps.tipo as 'general' | 'formacion' | 'retiro_espiritual' | 'reunion_equipo',
            alcance: evento.extendedProps.alcance as 'equipo' | 'global',
            equipo_id: evento.extendedProps.equipo_id,
        };

        setEventoEditar(eventoParaEditar);
        setModalEditarAbierto(true);
        setModalDetalleAbierto(false);
    };

    return (
        <>
            <div className="flex flex-col space-y-4">
                {/* Barra de herramientas: filtros y exportar */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <FiltrosCalendario onFiltrosChange={handleFiltrosChange} />
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={handleExportar}
                        className="flex items-center gap-2"
                    >
                        <Download className="h-4 w-4" />
                        Exportar .ics
                    </Button>
                </div>

                {/* Botones de cambio de vista */}
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant={currentView === 'dayGridMonth' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => handleViewChange('dayGridMonth')}
                    >
                        Mensual
                    </Button>
                    <Button
                        type="button"
                        variant={currentView === 'timeGridWeek' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => handleViewChange('timeGridWeek')}
                    >
                        Semanal
                    </Button>
                    <Button
                        type="button"
                        variant={currentView === 'timeGridDay' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => handleViewChange('timeGridDay')}
                    >
                        Diaria
                    </Button>
                    <Button
                        type="button"
                        variant={currentView === 'listWeek' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => handleViewChange('listWeek')}
                    >
                        Lista
                    </Button>
                </div>

                {/* FullCalendar */}
                <div className="w-full overflow-auto rounded-lg border bg-background">
                    {loading && (
                        <div className="flex items-center justify-center p-8">
                            <div className="text-muted-foreground">Cargando eventos...</div>
                        </div>
                    )}
                    <div className="w-full">
                        <FullCalendar
                            ref={calendarRef}
                            plugins={[dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin]}
                            initialView="dayGridMonth"
                            headerToolbar={{
                                left: 'prev,next today',
                                center: 'title',
                                right: '',
                            }}
                            locale="es"
                            buttonText={{
                                today: 'Hoy',
                            }}
                            allDayText="Todo el día"
                            firstDay={1}
                            weekends={true}
                            events={eventos}
                            editable={true}
                            droppable={false}
                            eventStartEditable={true}
                            eventDurationEditable={true}
                            eventDrop={handleEventDrop}
                            eventResize={handleEventResize}
                            eventAllow={(dropInfo, draggedEvent) => {
                                // No permitir arrastrar cumpleaños, pero sí aniversarios
                                if (!draggedEvent) {
                                    return false;
                                }
                                // Permitir aniversarios (son editables)
                                if (draggedEvent.id.startsWith('aniversario_')) {
                                    return true;
                                }
                                // No permitir cumpleaños
                                return !draggedEvent.id.startsWith('cumpleanos_');
                            }}
                            datesSet={handleDatesSet}
                            dateClick={handleDateClick}
                            eventClick={(info) => {
                            handleEventClick(info).catch((error) => {
                                console.error('Error en handleEventClick:', error);
                            });
                        }}
                            dayCellContent={(args) => {
                                return args.dayNumberText;
                            }}
                            slotLabelFormat={{
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true,
                            }}
                            eventTimeFormat={{
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true,
                            }}
                            height="auto"
                            contentHeight="auto"
                            aspectRatio={1.8}
                        />
                    </div>
                </div>
            </div>

            {/* Modal crear evento */}
            <EventoModal
                isOpen={modalCrearAbierto}
                onClose={() => setModalCrearAbierto(false)}
                fechaInicio={fechaInicioCrear}
                equipos={equipos}
                puedeGlobal={puedeGlobal}
                onSuccess={handleRefresh}
            />

            {/* Modal editar evento */}
            {modalEditarAbierto && eventoEditar && (
                <EventoModal
                    isOpen={modalEditarAbierto}
                    onClose={() => {
                        setModalEditarAbierto(false);
                        setEventoEditar(null);
                    }}
                    eventoEditar={eventoEditar}
                    equipos={equipos}
                    puedeGlobal={puedeGlobal}
                    onSuccess={handleRefresh}
                />
            )}

            {/* Modal detalle evento */}
            <EventoDetalleModal
                isOpen={modalDetalleAbierto}
                onClose={() => {
                    setModalDetalleAbierto(false);
                    setEventoDetalle(null);
                }}
                evento={eventoDetalle ? ({
                    ...eventoDetalle,
                    extendedProps: {
                        ...eventoDetalle.extendedProps,
                        tipo: eventoDetalle.extendedProps.tipo as 'general' | 'formacion' | 'retiro_espiritual' | 'reunion_equipo',
                        alcance: eventoDetalle.extendedProps.alcance as 'equipo' | 'global',
                    },
                }) : null}
                onEdit={(evento) => {
                    // Convertir EventoDetalle a CalendarEvent para handleEditarDesdeDetalle
                    const calendarEvent: CalendarEvent = {
                        ...evento,
                        borderColor: evento.backgroundColor,
                        textColor: '#ffffff',
                    };
                    handleEditarDesdeDetalle(calendarEvent);
                }}
                onDeleted={handleRefresh}
            />
        </>
    );
}
