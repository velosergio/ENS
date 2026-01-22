<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventoCalendarioCreateRequest;
use App\Http\Requests\EventoCalendarioUpdateRequest;
use App\Models\ConfiguracionCalendario;
use App\Models\Equipo;
use App\Models\EventoCalendario;
use App\Models\Pareja;
use App\Services\CumpleanosAniversariosService;
use App\Services\CumpleanosService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    /**
     * Mostrar la página principal del calendario.
     */
    public function index(Request $request): Response
    {
        // Obtener equipos disponibles para el selector (si el usuario es mango/admin)
        $equipos = [];
        if ($request->user()->esMango() || $request->user()->esAdmin()) {
            $equipos = Equipo::query()
                ->orderBy('numero', 'asc')
                ->get()
                ->map(fn ($equipo) => [
                    'id' => $equipo->id,
                    'numero' => $equipo->numero,
                ]);
        }

        // Obtener configuraciones para colores e iconos por defecto
        $configuraciones = ConfiguracionCalendario::todas();

        return Inertia::render('calendario/index', [
            'equipos' => $equipos,
            'configuraciones' => $configuraciones,
        ]);
    }

    /**
     * Endpoint API para obtener eventos en rango de fechas (formato FullCalendar).
     */
    public function events(Request $request): JsonResponse
    {
        $user = $request->user();
        $start = $request->input('start'); // YYYY-MM-DD
        $end = $request->input('end'); // YYYY-MM-DD

        if (! $start || ! $end) {
            return response()->json([]);
        }

        $query = EventoCalendario::query()
            ->with(['creadoPor', 'equipo']);

        // Filtrado según rol
        if ($user->esMango()) {
            // Mango ve todos los eventos
        } else {
            // Admin/Equipista ven eventos de su equipo + eventos globales
            $equipoId = $user->equipo()?->id;
            if ($equipoId) {
                $query->where(function ($q) use ($equipoId) {
                    $q->where('alcance', 'global')
                        ->orWhere(function ($query) use ($equipoId) {
                            $query->where('alcance', 'equipo')
                                ->where('equipo_id', $equipoId);
                        });
                });
            } else {
                // Si no tiene equipo, solo eventos globales
                $query->where('alcance', 'global');
            }
        }

        // Filtrar por rango de fechas
        $eventos = $query->porRangoFechas($start, $end)->get();

        // Obtener configuraciones para colores e iconos por defecto
        $configuraciones = ConfiguracionCalendario::todas();

        // Formatear eventos a formato FullCalendar
        $eventosFormateados = $eventos->map(function ($evento) use ($configuraciones) {
            return $evento->toFullCalendarFormat($configuraciones);
        })->toArray();

        // Obtener cumpleaños en el rango de fechas
        $cumpleanosService = new CumpleanosService;
        $configuracionCumpleanos = $configuraciones['cumpleanos'] ?? ['color' => '#ec4899', 'icono' => 'Cake'];
        $cumpleanos = $cumpleanosService->obtenerCumpleanosEnRango($start, $end, $configuracionCumpleanos);

        // Obtener aniversarios en el rango de fechas
        $aniversariosService = new CumpleanosAniversariosService;
        $equipoId = $user->esMango() ? null : $user->equipo()?->id;
        $aniversariosRaw = $aniversariosService->obtenerProximosAniversarios(
            (int) Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1,
            $user,
            $equipoId
        );

        // Formatear aniversarios para FullCalendar
        $configuracionAniversarioBoda = $configuraciones['aniversario_boda'] ?? ['color' => '#f59e0b', 'icono' => 'Heart'];
        $configuracionAniversarioAcogida = $configuraciones['aniversario_acogida'] ?? ['color' => '#10b981', 'icono' => 'Users'];
        $aniversarios = array_map(function ($aniversario) use ($configuracionAniversarioBoda, $configuracionAniversarioAcogida) {
            $config = $aniversario['tipo'] === 'aniversario_boda' ? $configuracionAniversarioBoda : $configuracionAniversarioAcogida;
            $fecha = Carbon::parse($aniversario['fecha']);

            return [
                'id' => $aniversario['id'],
                'title' => $aniversario['titulo'],
                'start' => $fecha->format('Y-m-d'),
                'end' => $fecha->copy()->addDay()->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => $config['color'],
                'borderColor' => $config['color'],
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'tipo' => $aniversario['tipo'],
                    'alcance' => 'global',
                    'equipo_id' => null,
                    'icono' => $config['icono'],
                    'años' => $aniversario['años'],
                    'pareja' => $aniversario['pareja'],
                ],
                'puede_editar' => true, // Los aniversarios son editables
                'puede_eliminar' => false, // Los aniversarios no son eliminables
            ];
        }, $aniversariosRaw);

        // Combinar eventos, cumpleaños y aniversarios
        $todosLosEventos = array_merge($eventosFormateados, $cumpleanos, $aniversarios);

        return response()->json($todosLosEventos);
    }

    /**
     * Crear un nuevo evento.
     */
    public function store(EventoCalendarioCreateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Validar permisos para alcance global (solo mango/admin)
        if ($data['alcance'] === 'global' && ! $user->esMango() && ! $user->esAdmin()) {
            return redirect()->back()
                ->withErrors(['alcance' => 'No tienes permiso para crear eventos globales.']);
        }

        // Validar que el equipo_id pertenece al equipo del usuario si no es mango/admin
        if ($data['alcance'] === 'equipo' && $data['equipo_id']) {
            $equipoUsuario = $user->equipo()?->id;
            if (! $user->esMango() && ! $user->esAdmin() && $equipoUsuario !== (int) $data['equipo_id']) {
                return redirect()->back()
                    ->withErrors(['equipo_id' => 'Solo puedes crear eventos para tu propio equipo.']);
            }
        }

        // Si no se especifica equipo_id pero el alcance es equipo, usar el equipo del usuario
        if ($data['alcance'] === 'equipo' && empty($data['equipo_id'])) {
            $equipoUsuario = $user->equipo()?->id;
            if (! $equipoUsuario) {
                return redirect()->back()
                    ->withErrors(['equipo_id' => 'Debes pertenecer a un equipo para crear eventos de equipo.']);
            }
            $data['equipo_id'] = $equipoUsuario;
        }

        // Obtener configuración por defecto si no se especifica color/icono
        $configuracion = ConfiguracionCalendario::porTipo($data['tipo']);
        if (! isset($data['color']) && $configuracion) {
            $data['color'] = $configuracion->color;
        }
        if (! isset($data['icono']) && $configuracion) {
            $data['icono'] = $configuracion->icono;
        }

        // Asignar creado_por automáticamente
        $data['creado_por'] = $user->id;

        EventoCalendario::create($data);

        return redirect()->back()
            ->with('success', 'Evento creado exitosamente.');
    }

    /**
     * Mostrar detalle de un evento (para modal).
     */
    public function show(Request $request, EventoCalendario $evento): JsonResponse
    {
        $user = $request->user();

        // Verificar que el usuario puede ver este evento
        if (! $this->puedeVerEvento($user, $evento)) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $evento->load(['creadoPor', 'equipo']);

        // Obtener configuraciones para colores e iconos por defecto
        $configuraciones = ConfiguracionCalendario::todas();

        return response()->json(
            array_merge(
                $evento->toFullCalendarFormat($configuraciones),
                [
                    'puede_editar' => $this->puedeEditarEvento($user, $evento),
                    'puede_eliminar' => $this->puedeEliminarEvento($user, $evento),
                ]
            )
        );
    }

    /**
     * Actualizar un evento.
     */
    public function update(EventoCalendarioUpdateRequest $request, EventoCalendario $evento): RedirectResponse
    {
        $user = $request->user();

        // Verificar permisos
        if (! $this->puedeEditarEvento($user, $evento)) {
            return redirect()->back()
                ->withErrors(['error' => 'No tienes permiso para editar este evento.']);
        }

        $data = $request->validated();

        // Validar permisos para cambiar alcance a global (solo mango/admin)
        if (isset($data['alcance']) && $data['alcance'] === 'global' && ! $user->esMango() && ! $user->esAdmin()) {
            return redirect()->back()
                ->withErrors(['alcance' => 'No tienes permiso para crear eventos globales.']);
        }

        // Validar que el equipo_id pertenece al equipo del usuario si no es mango/admin
        if (isset($data['alcance']) && $data['alcance'] === 'equipo' && isset($data['equipo_id'])) {
            $equipoUsuario = $user->equipo()?->id;
            if (! $user->esMango() && ! $user->esAdmin() && $equipoUsuario !== (int) $data['equipo_id']) {
                return redirect()->back()
                    ->withErrors(['equipo_id' => 'Solo puedes asignar eventos a tu propio equipo.']);
            }
        }

        $evento->update($data);

        return redirect()->back()
            ->with('success', 'Evento actualizado exitosamente.');
    }

    /**
     * Eliminar un evento.
     */
    public function destroy(Request $request, EventoCalendario $evento): RedirectResponse
    {
        $user = $request->user();

        // Verificar permisos
        if (! $this->puedeEliminarEvento($user, $evento)) {
            return redirect()->back()
                ->withErrors(['error' => 'No tienes permiso para eliminar este evento.']);
        }

        $evento->delete();

        return redirect()->back()
            ->with('success', 'Evento eliminado exitosamente.');
    }

    /**
     * Actualizar fecha de un evento (para drag & drop).
     */
    public function updateFecha(Request $request, EventoCalendario $evento): JsonResponse
    {
        $user = $request->user();

        // Verificar permisos
        if (! $this->puedeEditarEvento($user, $evento)) {
            return response()->json(['error' => 'No tienes permiso para editar este evento.'], 403);
        }

        $request->validate([
            'start' => ['required'],
            'end' => ['nullable'],
            'allDay' => ['nullable', 'boolean'],
        ]);

        // Determinar si es todo el día
        $esTodoElDia = $request->input('allDay', $evento->es_todo_el_dia);

        // Parsear fechas: pueden venir como YYYY-MM-DD (todo el día) o ISO completo (con hora)
        // Si viene en formato ISO con Z (UTC), Carbon parsea como UTC
        // Necesitamos convertirlo a la zona horaria local para guardarlo correctamente
        $nuevaFechaInicio = \Carbon\Carbon::parse($request->start);

        // Si la fecha viene en UTC (formato ISO con Z), convertir a hora local
        // FullCalendar envía fechas en UTC cuando se arrastra un evento con hora
        if (str_ends_with($request->start, 'Z')) {
            // Carbon parsea 'Z' como UTC, luego convertimos a hora local del servidor
            $nuevaFechaInicio = \Carbon\Carbon::parse($request->start, 'UTC')->setTimezone(config('app.timezone'));
        }

        // Manejar fecha fin
        if ($request->has('end') && $request->end) {
            // El frontend ya ajusta la fecha end para eventos de todo el día (resta un día)
            // Así que simplemente parseamos la fecha tal como viene
            $nuevaFechaFin = \Carbon\Carbon::parse($request->end);

            // Si la fecha viene en UTC (formato ISO con Z), convertir a hora local
            if (str_ends_with($request->end, 'Z')) {
                $nuevaFechaFin = \Carbon\Carbon::parse($request->end, 'UTC')->setTimezone(config('app.timezone'));
            }
        } else {
            // Si no se proporciona fecha fin, calcular basándose en la duración original
            if ($esTodoElDia) {
                // Para eventos de todo el día, usar diferencia en días
                $diferenciaDias = $evento->fecha_inicio->diffInDays($evento->fecha_fin);
                $nuevaFechaFin = $nuevaFechaInicio->copy()->addDays($diferenciaDias);
            } else {
                // Para eventos con hora, calcular diferencia completa (días + horas)
                $inicioOriginal = $evento->fecha_inicio->copy();
                $finOriginal = $evento->fecha_fin->copy();
                if ($evento->hora_inicio) {
                    $inicioOriginal->setTimeFromTimeString($evento->hora_inicio);
                }
                if ($evento->hora_fin) {
                    $finOriginal->setTimeFromTimeString($evento->hora_fin);
                }
                $diferencia = $inicioOriginal->diff($finOriginal);
                $nuevaFechaFin = $nuevaFechaInicio->copy()
                    ->add($diferencia);
            }
        }

        // Preparar datos para actualizar
        $datosActualizar = [
            'fecha_inicio' => $nuevaFechaInicio->format('Y-m-d'),
            'fecha_fin' => $nuevaFechaFin->format('Y-m-d'),
            'es_todo_el_dia' => $esTodoElDia,
        ];

        // Si NO es todo el día, actualizar las horas
        if (! $esTodoElDia) {
            // Extraer hora de la fecha/hora de inicio y fin
            $datosActualizar['hora_inicio'] = $nuevaFechaInicio->format('H:i:s');
            if ($nuevaFechaFin) {
                $datosActualizar['hora_fin'] = $nuevaFechaFin->format('H:i:s');
            }
        } else {
            // Si es todo el día, limpiar horas
            $datosActualizar['hora_inicio'] = null;
            $datosActualizar['hora_fin'] = null;
        }

        // Actualizar evento
        $evento->update($datosActualizar);

        $evento->refresh();

        return response()->json(['success' => true, 'evento' => $evento->toFullCalendarFormat(ConfiguracionCalendario::todas())]);
    }

    /**
     * Actualizar fecha de un aniversario (para drag & drop).
     */
    public function updateAniversarioFecha(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'id' => ['required', 'string'],
            'start' => ['required'],
            'allDay' => ['nullable', 'boolean'],
        ]);

        $eventId = $request->input('id');

        // Parsear ID del aniversario: formato "aniversario_boda_{pareja_id}_{año}" o "aniversario_acogida_{pareja_id}_{año}"
        if (! str_starts_with($eventId, 'aniversario_')) {
            return response()->json(['error' => 'ID de aniversario inválido.'], 400);
        }

        $parts = explode('_', $eventId);
        if (count($parts) < 4) {
            return response()->json(['error' => 'Formato de ID de aniversario inválido.'], 400);
        }

        $tipo = $parts[0].'_'.$parts[1]; // 'aniversario_boda' o 'aniversario_acogida'
        $parejaId = (int) $parts[2];

        $pareja = Pareja::findOrFail($parejaId);

        // Verificar permisos: solo el creador del aniversario (la pareja misma) o mango/admin pueden editar
        if (! $user->esMango() && ! $user->esAdmin()) {
            $parejaUsuario = $user->pareja;
            if (! $parejaUsuario || $parejaUsuario->id !== $pareja->id) {
                return response()->json(['error' => 'No tienes permiso para editar este aniversario.'], 403);
            }
        }

        // Parsear nueva fecha
        $nuevaFecha = Carbon::parse($request->start);
        if (str_ends_with($request->start, 'Z')) {
            $nuevaFecha = Carbon::parse($request->start, 'UTC')->setTimezone(config('app.timezone'));
        }

        // Obtener año original antes de actualizar
        $añoOriginal = $tipo === 'aniversario_boda'
            ? ($pareja->fecha_boda ? (int) $pareja->fecha_boda->format('Y') : null)
            : ($pareja->fecha_acogida ? (int) $pareja->fecha_acogida->format('Y') : null);

        if (! $añoOriginal) {
            return response()->json(['error' => 'No se puede calcular el aniversario sin fecha original.'], 400);
        }

        // Actualizar la fecha correspondiente según el tipo
        if ($tipo === 'aniversario_boda') {
            $pareja->fecha_boda = $nuevaFecha->format('Y-m-d');
        } elseif ($tipo === 'aniversario_acogida') {
            $pareja->fecha_acogida = $nuevaFecha->format('Y-m-d');
        } else {
            return response()->json(['error' => 'Tipo de aniversario inválido.'], 400);
        }

        $pareja->save();
        $pareja->refresh();

        // Calcular años del aniversario basado en el año nuevo y el año original
        $añoNuevo = (int) $nuevaFecha->format('Y');
        $años = $añoNuevo - $añoOriginal;

        // Obtener datos de la pareja
        $el = $pareja->el();
        $ella = $pareja->ella();
        $nombreEl = $el ? trim(($el->nombres ?? '').' '.($el->apellidos ?? '')) : '';
        $nombreElla = $ella ? trim(($ella->nombres ?? '').' '.($ella->apellidos ?? '')) : '';
        $nombrePareja = trim($nombreEl.' & '.$nombreElla) ?: 'Pareja sin nombre';

        // Formatear para FullCalendar
        $configuraciones = ConfiguracionCalendario::todas();
        $config = $tipo === 'aniversario_boda'
            ? ($configuraciones['aniversario_boda'] ?? ['color' => '#f59e0b', 'icono' => 'Heart'])
            : ($configuraciones['aniversario_acogida'] ?? ['color' => '#10b981', 'icono' => 'Users']);

        $titulo = $tipo === 'aniversario_boda'
            ? 'Aniversario de Boda: '.$nombrePareja
            : 'Aniversario de Acogida: '.$nombrePareja;

        $nuevoId = $tipo.'_'.$pareja->id.'_'.$añoNuevo;

        return response()->json([
            'success' => true,
            'evento' => [
                'id' => $nuevoId,
                'title' => $titulo,
                'start' => $nuevaFecha->format('Y-m-d'),
                'end' => $nuevaFecha->copy()->addDay()->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => $config['color'],
                'borderColor' => $config['color'],
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'tipo' => $tipo,
                    'alcance' => 'global',
                    'equipo_id' => null,
                    'icono' => $config['icono'],
                    'años' => $años,
                    'pareja' => [
                        'id' => $pareja->id,
                        'el' => $el ? [
                            'id' => $el->id,
                            'nombres' => $el->nombres,
                            'apellidos' => $el->apellidos,
                            'email' => $el->email,
                            'cedula' => $el->cedula,
                            'celular' => $el->celular,
                        ] : null,
                        'ella' => $ella ? [
                            'id' => $ella->id,
                            'nombres' => $ella->nombres,
                            'apellidos' => $ella->apellidos,
                            'email' => $ella->email,
                            'cedula' => $ella->cedula,
                            'celular' => $ella->celular,
                        ] : null,
                    ],
                ],
                'puede_editar' => true,
                'puede_eliminar' => false,
            ],
        ]);
    }

    /**
     * Exportar calendario a formato .ics (iCalendar).
     */
    public function exportar(Request $request): \Illuminate\Http\Response
    {
        $user = $request->user();
        $start = $request->input('start'); // YYYY-MM-DD
        $end = $request->input('end'); // YYYY-MM-DD

        if (! $start || ! $end) {
            $start = now()->startOfMonth()->format('Y-m-d');
            $end = now()->addMonths(2)->endOfMonth()->format('Y-m-d');
        }

        $query = EventoCalendario::query()
            ->with(['creadoPor', 'equipo']);

        // Filtrado según rol (igual que en events)
        if ($user->esMango()) {
            // Mango ve todos los eventos
        } else {
            $equipoId = $user->equipo()?->id;
            if ($equipoId) {
                $query->where(function ($q) use ($equipoId) {
                    $q->where('alcance', 'global')
                        ->orWhere(function ($query) use ($equipoId) {
                            $query->where('alcance', 'equipo')
                                ->where('equipo_id', $equipoId);
                        });
                });
            } else {
                $query->where('alcance', 'global');
            }
        }

        $eventos = $query->porRangoFechas($start, $end)->get();

        // Obtener cumpleaños
        $cumpleanosService = new CumpleanosService;
        $configuraciones = ConfiguracionCalendario::todas();
        $configuracionCumpleanos = $configuraciones['cumpleanos'] ?? ['color' => '#ec4899', 'icono' => 'Cake'];
        $cumpleanos = $cumpleanosService->obtenerCumpleanosEnRango($start, $end, $configuracionCumpleanos);

        // Obtener aniversarios
        $aniversariosService = new CumpleanosAniversariosService;
        $equipoId = $user->esMango() ? null : $user->equipo()?->id;
        $diasRango = (int) Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
        $aniversarios = $aniversariosService->obtenerProximosAniversarios($diasRango, $user, $equipoId);

        // Generar contenido .ics
        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//Equipos de Nuestra Señora//Calendario//ES\r\n";
        $icsContent .= "CALSCALE:GREGORIAN\r\n";
        $icsContent .= "METHOD:PUBLISH\r\n";

        // Agregar eventos normales
        foreach ($eventos as $evento) {
            $icsContent .= $this->eventoAICS($evento);
        }

        // Agregar cumpleaños
        foreach ($cumpleanos as $cumpleano) {
            $icsContent .= $this->cumpleanoAICS($cumpleano);
        }

        // Agregar aniversarios
        foreach ($aniversarios as $aniversario) {
            $icsContent .= $this->aniversarioAICS($aniversario);
        }

        $icsContent .= "END:VCALENDAR\r\n";

        $filename = 'calendario_'.now()->format('Y-m-d').'.ics';

        return response($icsContent, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Convertir un evento a formato iCalendar.
     */
    private function eventoAICS(EventoCalendario $evento): string
    {
        $dtStart = $evento->fecha_inicio->format('Ymd');
        $dtEnd = $evento->fecha_fin->copy()->addDay()->format('Ymd'); // iCalendar usa fecha exclusiva

        if (! $evento->es_todo_el_dia && $evento->hora_inicio && $evento->hora_fin) {
            // hora_inicio y hora_fin son strings 'H:i:s', no objetos Carbon
            $dtStart = $evento->fecha_inicio->format('Ymd').'T'.str_replace(':', '', $evento->hora_inicio);
            $dtEnd = $evento->fecha_fin->format('Ymd').'T'.str_replace(':', '', $evento->hora_fin);
        }

        $summary = $this->escapeICS($evento->titulo);
        $description = $evento->descripcion ? $this->escapeICS($evento->descripcion) : '';

        $ics = "BEGIN:VEVENT\r\n";
        $ics .= "UID:evento-{$evento->id}@ens\r\n";
        $ics .= 'DTSTAMP:'.now()->format('Ymd\THis\Z')."\r\n";
        $ics .= 'DTSTART'.($evento->es_todo_el_dia ? ';VALUE=DATE' : '').":{$dtStart}\r\n";
        $ics .= 'DTEND'.($evento->es_todo_el_dia ? ';VALUE=DATE' : '').":{$dtEnd}\r\n";
        $ics .= "SUMMARY:{$summary}\r\n";
        if ($description) {
            $ics .= "DESCRIPTION:{$description}\r\n";
        }
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";

        return $ics;
    }

    /**
     * Convertir un cumpleaños a formato iCalendar.
     */
    private function cumpleanoAICS(array $cumpleano): string
    {
        $dtStart = \Carbon\Carbon::parse($cumpleano['start'])->format('Ymd');
        $dtEnd = \Carbon\Carbon::parse($cumpleano['end'])->format('Ymd');
        $summary = $this->escapeICS($cumpleano['title']);

        $ics = "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$cumpleano['id']}@ens\r\n";
        $ics .= 'DTSTAMP:'.now()->format('Ymd\THis\Z')."\r\n";
        $ics .= "DTSTART;VALUE=DATE:{$dtStart}\r\n";
        $ics .= "DTEND;VALUE=DATE:{$dtEnd}\r\n";
        $ics .= "SUMMARY:{$summary}\r\n";
        $ics .= "RRULE:FREQ=YEARLY\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";

        return $ics;
    }

    /**
     * Convertir un aniversario a formato iCalendar.
     */
    private function aniversarioAICS(array $aniversario): string
    {
        $dtStart = Carbon::parse($aniversario['fecha'])->format('Ymd');
        $dtEnd = Carbon::parse($aniversario['fecha'])->addDay()->format('Ymd');
        $summary = $this->escapeICS($aniversario['titulo']);

        $ics = "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$aniversario['id']}@ens\r\n";
        $ics .= 'DTSTAMP:'.now()->format('Ymd\THis\Z')."\r\n";
        $ics .= "DTSTART;VALUE=DATE:{$dtStart}\r\n";
        $ics .= "DTEND;VALUE=DATE:{$dtEnd}\r\n";
        $ics .= "SUMMARY:{$summary}\r\n";
        $ics .= "RRULE:FREQ=YEARLY\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";

        return $ics;
    }

    /**
     * Escapar texto para formato iCalendar.
     */
    private function escapeICS(string $text): string
    {
        return str_replace(["\r\n", "\r", "\n", ',', ';', '\\'], ['\\n', '\\n', '\\n', '\\,', '\\;', '\\\\'], $text);
    }

    /**
     * Verificar si el usuario puede ver el evento.
     */
    private function puedeVerEvento($user, EventoCalendario $evento): bool
    {
        if ($user->esMango()) {
            return true;
        }

        if ($evento->alcance === 'global') {
            return true;
        }

        if ($evento->alcance === 'equipo') {
            $equipoUsuario = $user->equipo()?->id;

            return $equipoUsuario && $equipoUsuario === $evento->equipo_id;
        }

        return false;
    }

    /**
     * Verificar si el usuario puede editar el evento.
     */
    private function puedeEditarEvento($user, EventoCalendario $evento): bool
    {
        // Mango/admin pueden editar todos
        if ($user->esMango() || $user->esAdmin()) {
            return true;
        }

        // El creador puede editar sus propios eventos
        return $evento->creado_por === $user->id;
    }

    /**
     * Verificar si el usuario puede eliminar el evento.
     */
    private function puedeEliminarEvento($user, EventoCalendario $evento): bool
    {
        // Mango/admin pueden eliminar todos
        if ($user->esMango() || $user->esAdmin()) {
            return true;
        }

        // El creador puede eliminar sus propios eventos
        return $evento->creado_por === $user->id;
    }
}
