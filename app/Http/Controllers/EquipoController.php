<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipoAsignarResponsableRequest;
use App\Http\Requests\EquipoConfigurarConsiliarioRequest;
use App\Http\Requests\EquipoCreateRequest;
use App\Http\Requests\EquipoUpdateRequest;
use App\Models\Equipo;
use App\Models\Pareja;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EquipoController extends Controller
{
    /**
     * Listar equipos con búsqueda y filtros.
     */
    public function index(Request $request): Response
    {
        $query = Equipo::query()
            ->with(['responsable.pareja.usuarios', 'parejas.usuarios']);

        // Búsqueda en tiempo real
        if ($request->has('buscar') && $request->buscar !== '') {
            $query->buscar($request->buscar);
        }

        // Filtro por número
        if ($request->has('numero') && $request->numero !== '') {
            $query->where('numero', $request->numero);
        }

        // Filtro por responsable
        if ($request->has('responsable_id') && $request->responsable_id !== '') {
            $query->where('responsable_id', $request->responsable_id);
        }

        // Ordenar por número ascendente
        $equipos = $query->orderBy('numero', 'asc')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($equipo) {
                $responsable = $equipo->responsable;
                $parejaResponsable = $equipo->parejaResponsable();

                return [
                    'id' => $equipo->id,
                    'numero' => $equipo->numero,
                    'consiliario_nombre' => $equipo->consiliario_nombre,
                    'responsable' => $responsable ? [
                        'id' => $responsable->id,
                        'nombres' => $responsable->nombres,
                        'apellidos' => $responsable->apellidos,
                        'email' => $responsable->email,
                    ] : null,
                    'pareja_responsable' => $parejaResponsable ? [
                        'id' => $parejaResponsable->id,
                        'el' => $parejaResponsable->el() ? [
                            'nombres' => $parejaResponsable->el()->nombres,
                            'apellidos' => $parejaResponsable->el()->apellidos,
                        ] : null,
                        'ella' => $parejaResponsable->ella() ? [
                            'nombres' => $parejaResponsable->ella()->nombres,
                            'apellidos' => $parejaResponsable->ella()->apellidos,
                        ] : null,
                    ] : null,
                    'total_parejas' => $equipo->parejas()->count(),
                    'total_usuarios' => $equipo->usuarios()->count(),
                ];
            });

        return Inertia::render('equipos/index', [
            'equipos' => Inertia::scroll($equipos),
            'filters' => [
                'buscar' => $request->buscar,
                'numero' => $request->numero,
                'responsable_id' => $request->responsable_id,
            ],
        ]);
    }

    /**
     * Mostrar formulario para crear equipo.
     */
    public function create(): Response
    {
        // Obtener usuarios disponibles para asignar como responsable (solo equipistas y admins, excluyendo mango)
        $usuariosDisponibles = User::query()
            ->whereIn('rol', ['equipista', 'admin'])
            ->with('pareja.usuarios')
            ->get()
            ->map(function ($user) {
                $pareja = $user->pareja;

                return [
                    'id' => $user->id,
                    'nombres' => $user->nombres,
                    'apellidos' => $user->apellidos,
                    'email' => $user->email,
                    'pareja' => $pareja ? [
                        'id' => $pareja->id,
                        'el' => $pareja->el() ? [
                            'nombres' => $pareja->el()->nombres,
                            'apellidos' => $pareja->el()->apellidos,
                        ] : null,
                        'ella' => $pareja->ella() ? [
                            'nombres' => $pareja->ella()->nombres,
                            'apellidos' => $pareja->ella()->apellidos,
                        ] : null,
                    ] : null,
                ];
            });

        return Inertia::render('equipos/create', [
            'usuarios_disponibles' => $usuariosDisponibles,
        ]);
    }

    /**
     * Crear un nuevo equipo.
     */
    public function store(EquipoCreateRequest $request): RedirectResponse
    {
        $equipo = Equipo::create([
            'numero' => $request->numero,
            'responsable_id' => $request->responsable_id,
            'consiliario_nombre' => $request->consiliario_nombre,
        ]);

        // Si se asignó un responsable, ascender a admin
        if ($request->responsable_id) {
            $this->ascenderResponsable($request->responsable_id);
        }

        return redirect()->route('equipos.index')
            ->with('success', 'Equipo creado exitosamente.');
    }

    /**
     * Mostrar detalle del equipo.
     */
    public function show(Request $request, Equipo $equipo): Response
    {
        $equipo->load(['responsable.pareja.usuarios']);

        $parejaResponsable = $equipo->parejaResponsable();

        // Obtener parejas del equipo con scroll infinito
        $parejas = $equipo->parejas()
            ->with(['usuarios' => function ($q) {
                $q->orderBy('sexo');
            }])
            ->orderBy('fecha_ingreso', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($pareja) {
                $el = $pareja->el();
                $ella = $pareja->ella();

                return [
                    'id' => $pareja->id,
                    'fecha_ingreso' => $pareja->fecha_ingreso?->format('Y-m-d'),
                    'estado' => $pareja->estado,
                    'foto_thumbnail_50' => $pareja->foto_thumbnail_50,
                    'el' => $el ? [
                        'id' => $el->id,
                        'nombres' => $el->nombres,
                        'apellidos' => $el->apellidos,
                        'email' => $el->email,
                    ] : null,
                    'ella' => $ella ? [
                        'id' => $ella->id,
                        'nombres' => $ella->nombres,
                        'apellidos' => $ella->apellidos,
                        'email' => $ella->email,
                    ] : null,
                ];
            });

        // Obtener usuarios disponibles para asignar como responsable
        $usuariosDisponibles = User::query()
            ->whereIn('rol', ['equipista', 'admin'])
            ->with('pareja.usuarios')
            ->get()
            ->map(function ($user) {
                $pareja = $user->pareja;

                return [
                    'id' => $user->id,
                    'nombres' => $user->nombres,
                    'apellidos' => $user->apellidos,
                    'email' => $user->email,
                    'pareja' => $pareja ? [
                        'id' => $pareja->id,
                        'el' => $pareja->el() ? [
                            'nombres' => $pareja->el()->nombres,
                            'apellidos' => $pareja->el()->apellidos,
                        ] : null,
                        'ella' => $pareja->ella() ? [
                            'nombres' => $pareja->ella()->nombres,
                            'apellidos' => $pareja->ella()->apellidos,
                        ] : null,
                    ] : null,
                ];
            });

        return Inertia::render('equipos/show', [
            'equipo' => [
                'id' => $equipo->id,
                'numero' => $equipo->numero,
                'consiliario_nombre' => $equipo->consiliario_nombre,
                'responsable' => $equipo->responsable ? [
                    'id' => $equipo->responsable->id,
                    'nombres' => $equipo->responsable->nombres,
                    'apellidos' => $equipo->responsable->apellidos,
                    'email' => $equipo->responsable->email,
                ] : null,
                'pareja_responsable' => $parejaResponsable ? [
                    'id' => $parejaResponsable->id,
                    'el' => $parejaResponsable->el() ? [
                        'nombres' => $parejaResponsable->el()->nombres,
                        'apellidos' => $parejaResponsable->el()->apellidos,
                    ] : null,
                    'ella' => $parejaResponsable->ella() ? [
                        'nombres' => $parejaResponsable->ella()->nombres,
                        'apellidos' => $parejaResponsable->ella()->apellidos,
                    ] : null,
                ] : null,
            ],
            'parejas' => Inertia::scroll($parejas),
            'usuarios_disponibles' => $usuariosDisponibles,
        ]);
    }

    /**
     * Mostrar formulario para editar equipo.
     */
    public function edit(Equipo $equipo): Response
    {
        // Obtener usuarios disponibles para asignar como responsable (solo equipistas y admins, excluyendo mango)
        $usuariosDisponibles = User::query()
            ->whereIn('rol', ['equipista', 'admin'])
            ->with('pareja.usuarios')
            ->get()
            ->map(function ($user) {
                $pareja = $user->pareja;

                return [
                    'id' => $user->id,
                    'nombres' => $user->nombres,
                    'apellidos' => $user->apellidos,
                    'email' => $user->email,
                    'pareja' => $pareja ? [
                        'id' => $pareja->id,
                        'el' => $pareja->el() ? [
                            'nombres' => $pareja->el()->nombres,
                            'apellidos' => $pareja->el()->apellidos,
                        ] : null,
                        'ella' => $pareja->ella() ? [
                            'nombres' => $pareja->ella()->nombres,
                            'apellidos' => $pareja->ella()->apellidos,
                        ] : null,
                    ] : null,
                ];
            });

        return Inertia::render('equipos/edit', [
            'equipo' => [
                'id' => $equipo->id,
                'numero' => $equipo->numero,
                'consiliario_nombre' => $equipo->consiliario_nombre,
                'responsable_id' => $equipo->responsable_id,
            ],
            'usuarios_disponibles' => $usuariosDisponibles,
        ]);
    }

    /**
     * Actualizar equipo.
     */
    public function update(EquipoUpdateRequest $request, Equipo $equipo): RedirectResponse
    {
        $equipo->update([
            'numero' => $request->numero,
            'consiliario_nombre' => $request->consiliario_nombre,
        ]);

        return redirect()->route('equipos.index')
            ->with('success', 'Equipo actualizado exitosamente.');
    }

    /**
     * Eliminar equipo.
     */
    public function destroy(Equipo $equipo): RedirectResponse
    {
        // Validar que no tenga parejas asignadas
        if ($equipo->tieneParejas()) {
            return redirect()->route('equipos.index')
                ->with('error', 'No se puede eliminar un equipo que tiene parejas asignadas.');
        }

        // Si tiene responsable, degradar antes de eliminar
        if ($equipo->responsable_id) {
            $this->degradarResponsable($equipo->responsable_id);
        }

        $equipo->delete();

        return redirect()->route('equipos.index')
            ->with('success', 'Equipo eliminado exitosamente.');
    }

    /**
     * Asignar responsable al equipo.
     */
    public function asignarResponsable(
        EquipoAsignarResponsableRequest $request,
        Equipo $equipo
    ): RedirectResponse {
        DB::transaction(function () use ($request, $equipo) {
            // Si había un responsable anterior, degradarlo
            if ($equipo->responsable_id) {
                $this->degradarResponsable($equipo->responsable_id);
            }

            // Asignar nuevo responsable
            $equipo->update([
                'responsable_id' => $request->responsable_id,
            ]);

            // Si se asignó un responsable, ascender a admin
            if ($request->responsable_id) {
                $this->ascenderResponsable($request->responsable_id);
            }
        });

        return redirect()->route('equipos.show', $equipo)
            ->with('success', 'Responsable asignado exitosamente.');
    }

    /**
     * Configurar consiliario del equipo.
     */
    public function configurarConsiliario(
        EquipoConfigurarConsiliarioRequest $request,
        Equipo $equipo
    ): RedirectResponse {
        $equipo->update([
            'consiliario_nombre' => $request->consiliario_nombre,
        ]);

        return redirect()->route('equipos.show', $equipo)
            ->with('success', 'Consiliario configurado exitosamente.');
    }

    /**
     * Ascender pareja a admin cuando se asigna como responsable.
     */
    protected function ascenderResponsable(int $userId): void
    {
        $user = User::findOrFail($userId);
        $pareja = $user->pareja;

        if ($pareja) {
            // Ascender ambos usuarios de la pareja a admin
            $pareja->usuarios()->update(['rol' => 'admin']);
        }
    }

    /**
     * Degradar pareja cuando se quita como responsable.
     */
    protected function degradarResponsable(int $userId): void
    {
        $user = User::findOrFail($userId);
        $pareja = $user->pareja;

        if ($pareja) {
            // Degradar ambos usuarios de la pareja a equipista
            $pareja->usuarios()->update(['rol' => 'equipista']);
        }
    }
}
