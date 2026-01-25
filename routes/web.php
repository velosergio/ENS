<?php

use App\Http\Controllers\Auth\ConfirmPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Fortify maneja /login automáticamente, redirigir /iniciar-sesion a /login
Route::get('/iniciar-sesion', function () {
    return redirect('/login');
})->name('iniciar-sesion');

// Fortify maneja /register automáticamente

Route::get('/olvide-contraseña', function (Request $request) {
    return Inertia::render('auth/forgot-password', [
        'status' => $request->session()->get('status'),
    ]);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/confirmar-contraseña', [ConfirmPasswordController::class, 'show'])->name('password.confirm.show');
    Route::post('/confirmar-contraseña', [ConfirmPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.confirm.show.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $user = $request->user()->load('pareja.usuarios');

        // Obtener próximos eventos (próximos 14 días)
        $fechaInicio = now()->format('Y-m-d');
        $fechaFin = now()->addDays(14)->format('Y-m-d');

        $query = \App\Models\EventoCalendario::query()
            ->with(['creadoPor', 'equipo']);

        // Filtrado según rol (misma lógica que CalendarController)
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

        // Filtrar por rango de fechas y ordenar por fecha de inicio
        $eventos = $query->porRangoFechas($fechaInicio, $fechaFin)
            ->orderBy('fecha_inicio', 'asc')
            ->orderBy('hora_inicio', 'asc')
            ->limit(7)
            ->get();

        // Obtener configuraciones para colores e iconos por defecto
        $configuraciones = \App\Models\ConfiguracionCalendario::todas();

        // Formatear eventos para el dashboard
        $eventosFormateados = $eventos->map(function ($evento) use ($configuraciones) {
            $formato = $evento->toFullCalendarFormat($configuraciones);

            return [
                'id' => $formato['id'],
                'titulo' => $formato['title'],
                'fecha_inicio' => $formato['start'],
                'fecha_fin' => $formato['end'],
                'allDay' => $formato['allDay'],
                'tipo' => $evento->tipo,
                'alcance' => $evento->alcance,
                'color' => $formato['backgroundColor'],
                'icono' => $evento->icono ?? ($configuraciones[$evento->tipo]['icono'] ?? null),
            ];
        })->toArray();

        // Obtener próximos eventos (cumpleaños y aniversarios) usando el servicio
        $aniversariosService = new \App\Services\CumpleanosAniversariosService;
        $equipoId = $user->esMango() ? null : $user->equipo()?->id;
        $eventosCumpleanosAniversarios = $aniversariosService->obtenerProximosEventos(14, $user, $equipoId);

        // Formatear cumpleaños y aniversarios para el dashboard
        $eventosCumpleanosAniversariosFormateados = array_map(function ($evento) use ($configuraciones) {
            $fecha = $evento['start'] ?? $evento['fecha_cumpleanos'] ?? $evento['fecha'] ?? '';
            $tipo = $evento['tipo'] ?? 'cumpleanos';

            // Determinar color e icono según tipo
            $color = '#ec4899';
            $icono = 'Cake';
            if ($tipo === 'aniversario_boda') {
                $config = $configuraciones['aniversario_boda'] ?? ['color' => '#f59e0b', 'icono' => 'Heart'];
                $color = $config['color'];
                $icono = $config['icono'] ?? 'Heart';
            } elseif ($tipo === 'aniversario_acogida') {
                $config = $configuraciones['aniversario_acogida'] ?? ['color' => '#10b981', 'icono' => 'Users'];
                $color = $config['color'];
                $icono = $config['icono'] ?? 'Users';
            } else {
                $config = $configuraciones['cumpleanos'] ?? ['color' => '#ec4899', 'icono' => 'Cake'];
                $color = $config['color'];
                $icono = $config['icono'] ?? 'Cake';
            }

            return [
                'id' => $evento['id'],
                'titulo' => $evento['title'] ?? $evento['titulo'] ?? ($evento['nombre'] ?? 'Evento'),
                'fecha_inicio' => $fecha,
                'fecha_fin' => $fecha,
                'allDay' => true,
                'tipo' => $tipo,
                'alcance' => 'global',
                'color' => $color,
                'icono' => $icono,
            ];
        }, $eventosCumpleanosAniversarios);

        // Combinar eventos, cumpleaños y aniversarios, ordenar por fecha
        $todosLosEventos = array_merge($eventosFormateados, $eventosCumpleanosAniversariosFormateados);

        // Ordenar por fecha de inicio (tomar solo la fecha, no la hora completa)
        usort($todosLosEventos, function ($a, $b) {
            $fechaAStr = is_array($a['fecha_inicio']) ? $a['fecha_inicio'][0] : explode('T', $a['fecha_inicio'])[0];
            $fechaBStr = is_array($b['fecha_inicio']) ? $b['fecha_inicio'][0] : explode('T', $b['fecha_inicio'])[0];

            $fechaA = strtotime($fechaAStr);
            $fechaB = strtotime($fechaBStr);

            if ($fechaA === $fechaB) {
                return 0;
            }

            return $fechaA < $fechaB ? -1 : 1;
        });

        // Limitar a 7 eventos más próximos
        $eventosProximos = array_slice($todosLosEventos, 0, 7);

        return Inertia::render('dashboard', [
            'pareja' => $user->pareja,
            'eventosProximos' => $eventosProximos,
        ]);
    })->name('dashboard');
});

// Módulo de Parejas (mango/admin)
Route::middleware(['auth', 'permission:parejas,view'])->group(function () {
    Route::resource('parejas', \App\Http\Controllers\ParejaController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);

    Route::post('parejas/{pareja}/retirar', [\App\Http\Controllers\ParejaController::class, 'retirar'])
        ->middleware('permission:parejas,update')
        ->name('parejas.retirar');

    Route::post('parejas/{pareja}/reactivar', [\App\Http\Controllers\ParejaController::class, 'reactivar'])
        ->middleware('permission:parejas,reactivar')
        ->name('parejas.reactivar');
});

// Módulo de Equipos (mango/admin)
Route::middleware(['auth', 'permission:equipos,view'])->group(function () {
    Route::resource('equipos', \App\Http\Controllers\EquipoController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::post('equipos/{equipo}/asignar-responsable', [\App\Http\Controllers\EquipoController::class, 'asignarResponsable'])
        ->middleware('permission:equipos,asignar-responsable')
        ->name('equipos.asignar-responsable');

    Route::post('equipos/{equipo}/configurar-consiliario', [\App\Http\Controllers\EquipoController::class, 'configurarConsiliario'])
        ->middleware('permission:equipos,configurar-consiliario')
        ->name('equipos.configurar-consiliario');
});

// Módulo GUIA (solo mango)
Route::middleware(['auth', 'permission:guia,view'])->group(function () {
    Route::get('guia', [\App\Http\Controllers\GuiaController::class, 'index'])
        ->name('guia.index');

    Route::post('guia/chat', [\App\Http\Controllers\GuiaController::class, 'chat'])
        ->middleware('permission:guia,chat')
        ->name('guia.chat');
});

// Panel de Salud del Sistema (todos los usuarios autenticados)
Route::middleware(['auth'])->group(function () {
    Route::get('health', [\App\Http\Controllers\HealthController::class, 'index'])
        ->name('health.index');
});

// Módulo de Calendario (todos los usuarios autenticados)
Route::middleware(['auth', 'permission:calendario,view'])->group(function () {
    Route::get('calendario', [\App\Http\Controllers\CalendarController::class, 'index'])->name('calendario.index');
    Route::get('calendario/events', [\App\Http\Controllers\CalendarController::class, 'events'])->name('calendario.events');

    Route::middleware('permission:calendario,create')->group(function () {
        Route::post('calendario', [\App\Http\Controllers\CalendarController::class, 'store'])->name('calendario.store');
    });

    // Ruta específica antes de la ruta dinámica
    Route::get('calendario/exportar', [\App\Http\Controllers\CalendarController::class, 'exportar'])->name('calendario.exportar');

    Route::get('calendario/{evento}', [\App\Http\Controllers\CalendarController::class, 'show'])->name('calendario.show');

    Route::middleware('permission:calendario,update')->group(function () {
        Route::patch('calendario/{evento}', [\App\Http\Controllers\CalendarController::class, 'update'])->name('calendario.update');
        // Ruta específica antes de la ruta parametrizada para evitar conflictos de enrutamiento
        Route::post('calendario/aniversario/fecha', [\App\Http\Controllers\CalendarController::class, 'updateAniversarioFecha'])->name('calendario.update-aniversario-fecha');
        Route::post('calendario/{evento}/fecha', [\App\Http\Controllers\CalendarController::class, 'updateFecha'])->name('calendario.update-fecha');
    });

    Route::middleware('permission:calendario,delete')->group(function () {
        Route::delete('calendario/{evento}', [\App\Http\Controllers\CalendarController::class, 'destroy'])->name('calendario.destroy');
    });

    // Módulo de Cumpleaños y Aniversarios
    Route::get('cumpleanos-aniversarios', [\App\Http\Controllers\CumpleanosAniversariosController::class, 'index'])
        ->name('cumpleanos-aniversarios.index');
    Route::get('cumpleanos-aniversarios/proximos', [\App\Http\Controllers\CumpleanosAniversariosController::class, 'proximos'])
        ->name('cumpleanos-aniversarios.proximos');
});

require __DIR__.'/settings.php';
