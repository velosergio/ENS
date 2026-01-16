<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParejaCreateRequest;
use App\Http\Requests\ParejaUpdateRequest;
use App\Models\Pareja;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ParejaController extends Controller
{
    /**
     * Listar parejas con búsqueda y filtros.
     */
    public function index(Request $request): Response
    {
        $query = Pareja::query()
            ->with(['usuarios' => function ($q) {
                $q->orderBy('sexo');
            }]);

        // Solo excluir parejas con usuarios mango si el usuario actual no es mango o admin
        $user = Auth::user();
        if (!$user || ($user->rol !== 'mango' && $user->rol !== 'admin')) {
            $query->sinMango(); // Excluir parejas con usuarios mango
        }

        // Filtro por estado
        if ($request->has('estado') && $request->estado !== '' && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        } elseif (!$request->has('estado') || $request->estado === '') {
            // Por defecto solo activas si no se especifica estado
            $query->where('estado', 'activo');
        }
        // Si estado === 'todos', no se aplica filtro de estado

        // Filtro por número de equipo
        if ($request->has('numero_equipo') && $request->numero_equipo !== '') {
            $query->where('numero_equipo', $request->numero_equipo);
        }

        // Búsqueda en tiempo real
        if ($request->has('buscar') && $request->buscar !== '') {
            $query->buscar($request->buscar);
        }

        // Ordenar por fecha de ingreso descendente
        $parejas = $query->orderBy('fecha_ingreso', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($pareja) {
                $el = $pareja->el();
                $ella = $pareja->ella();

                return [
                    'id' => $pareja->id,
                    'numero_equipo' => $pareja->numero_equipo,
                    'fecha_ingreso' => $pareja->fecha_ingreso?->format('Y-m-d'),
                    'estado' => $pareja->estado,
                    'el' => $el ? [
                        'id' => $el->id,
                        'nombres' => $el->nombres,
                        'apellidos' => $el->apellidos,
                        'email' => $el->email,
                        'celular' => $el->celular,
                        'fecha_nacimiento' => $el->fecha_nacimiento?->format('Y-m-d'),
                    ] : null,
                    'ella' => $ella ? [
                        'id' => $ella->id,
                        'nombres' => $ella->nombres,
                        'apellidos' => $ella->apellidos,
                        'email' => $ella->email,
                        'celular' => $ella->celular,
                        'fecha_nacimiento' => $ella->fecha_nacimiento?->format('Y-m-d'),
                    ] : null,
                ];
            });

        return Inertia::render('parejas/index', [
            'parejas' => $parejas,
            'filters' => [
                'buscar' => $request->buscar,
                'estado' => $request->estado ?? 'activo',
                'numero_equipo' => $request->numero_equipo,
            ],
        ]);
    }

    /**
     * Mostrar formulario para crear pareja.
     */
    public function create(): Response
    {
        return Inertia::render('parejas/create');
    }

    /**
     * Crear una nueva pareja con sus dos usuarios.
     */
    public function store(ParejaCreateRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            // Crear la pareja
            $pareja = Pareja::create([
                'fecha_ingreso' => $request->fecha_ingreso,
                'numero_equipo' => $request->numero_equipo,
                'foto_base64' => $request->pareja_foto_base64 ?? null,
                'estado' => 'activo',
            ]);

            // Crear usuario ÉL
            $el = User::create([
                'nombres' => $request->el_nombres,
                'apellidos' => $request->el_apellidos,
                'celular' => $request->el_celular,
                'fecha_nacimiento' => $request->el_fecha_nacimiento,
                'sexo' => 'masculino',
                'email' => $request->el_email,
                'foto_base64' => $request->el_foto_base64 ?? null,
                'password' => $request->password,
                'pareja_id' => $pareja->id,
                'equipo_id' => null, // Se asignará cuando exista el modelo Equipo
                'rol' => 'equipista',
            ]);

            // Crear usuario ELLA
            $ella = User::create([
                'nombres' => $request->ella_nombres,
                'apellidos' => $request->ella_apellidos,
                'celular' => $request->ella_celular,
                'fecha_nacimiento' => $request->ella_fecha_nacimiento,
                'sexo' => 'femenino',
                'email' => $request->ella_email,
                'foto_base64' => $request->ella_foto_base64 ?? null,
                'password' => $request->password,
                'pareja_id' => $pareja->id,
                'equipo_id' => null, // Se asignará cuando exista el modelo Equipo
                'rol' => 'equipista',
            ]);

            return $el;
        });

        return redirect()->route('parejas.index')
            ->with('success', 'Pareja creada exitosamente.');
    }

    /**
     * Mostrar formulario para editar pareja.
     */
    public function edit(Pareja $pareja): Response
    {
        $pareja->load(['usuarios' => function ($q) {
            $q->orderBy('sexo');
        }]);

        $el = $pareja->el();
        $ella = $pareja->ella();

        return Inertia::render('parejas/edit', [
            'pareja' => [
                'id' => $pareja->id,
                'fecha_ingreso' => $pareja->fecha_ingreso?->format('Y-m-d'),
                'numero_equipo' => $pareja->numero_equipo,
                'pareja_foto_base64' => $pareja->foto_base64,
                'estado' => $pareja->estado,
                'el' => $el ? [
                    'id' => $el->id,
                    'nombres' => $el->nombres,
                    'apellidos' => $el->apellidos,
                    'email' => $el->email,
                    'celular' => $el->celular,
                    'fecha_nacimiento' => $el->fecha_nacimiento?->format('Y-m-d'),
                    'foto_base64' => $el->foto_base64,
                ] : null,
                'ella' => $ella ? [
                    'id' => $ella->id,
                    'nombres' => $ella->nombres,
                    'apellidos' => $ella->apellidos,
                    'email' => $ella->email,
                    'celular' => $ella->celular,
                    'fecha_nacimiento' => $ella->fecha_nacimiento?->format('Y-m-d'),
                    'foto_base64' => $ella->foto_base64,
                ] : null,
            ],
        ]);
    }

    /**
     * Actualizar pareja y sus usuarios.
     */
    public function update(ParejaUpdateRequest $request, Pareja $pareja): RedirectResponse
    {
        DB::transaction(function () use ($request, $pareja) {
            // Actualizar pareja
            $pareja->update([
                'fecha_ingreso' => $request->fecha_ingreso,
                'numero_equipo' => $request->numero_equipo,
                'foto_base64' => $request->pareja_foto_base64 ?? $pareja->foto_base64,
                'estado' => $request->estado,
            ]);

            // Actualizar usuario ÉL
            if ($request->el_id) {
                $el = User::findOrFail($request->el_id);
                $el->update([
                    'nombres' => $request->el_nombres,
                    'apellidos' => $request->el_apellidos,
                    'celular' => $request->el_celular,
                    'fecha_nacimiento' => $request->el_fecha_nacimiento,
                    'email' => $request->el_email,
                    'foto_base64' => $request->el_foto_base64 ?? $el->foto_base64,
                ]);
            }

            // Actualizar usuario ELLA
            if ($request->ella_id) {
                $ella = User::findOrFail($request->ella_id);
                $ella->update([
                    'nombres' => $request->ella_nombres,
                    'apellidos' => $request->ella_apellidos,
                    'celular' => $request->ella_celular,
                    'fecha_nacimiento' => $request->ella_fecha_nacimiento,
                    'email' => $request->ella_email,
                    'foto_base64' => $request->ella_foto_base64 ?? $ella->foto_base64,
                ]);
            }

            // Si se retira la pareja y el usuario actual pertenece a esta pareja, cerrar sesión
            if ($request->estado === 'retirado') {
                $currentUser = $request->user();
                if ($currentUser && $currentUser->pareja_id === $pareja->id) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
            }
        });

        // Si se cerró sesión, redirigir al login
        if (! $request->user()) {
            return redirect()->route('login')
                ->with('status', 'Su pareja ha sido retirada del movimiento. Ya no tiene acceso a la plataforma.');
        }

        return redirect()->route('parejas.index')
            ->with('success', 'Pareja actualizada exitosamente.');
    }

    /**
     * Retirar pareja del movimiento.
     */
    public function retirar(Request $request, Pareja $pareja): RedirectResponse
    {
        // Solo se puede retirar si está activa
        if (! $pareja->estaActiva()) {
            return back()->withErrors([
                'estado' => 'La pareja ya está retirada.',
            ]);
        }

        $pareja->retirar();

        $currentUser = $request->user();

        // Si el usuario actual pertenece a esta pareja, cerrar sesión
        if ($currentUser && $currentUser->pareja_id === $pareja->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Su pareja ha sido retirada del movimiento. Ya no tiene acceso a la plataforma.');
        }

        return redirect()->route('parejas.index')
            ->with('success', 'Pareja retirada exitosamente.');
    }

    /**
     * Reactivar pareja en el movimiento.
     */
    public function reactivar(Request $request, Pareja $pareja): RedirectResponse
    {
        // Solo se puede reactivar si está retirada
        if ($pareja->estaActiva()) {
            return back()->withErrors([
                'estado' => 'La pareja ya está activa.',
            ]);
        }

        $pareja->reactivar();

        return redirect()->route('parejas.index')
            ->with('success', 'Pareja reactivada exitosamente.');
    }

    /**
     * Mostrar formulario para editar pareja propia (settings).
     */
    public function editSettings(Request $request): Response
    {
        $user = $request->user();
        $pareja = $user->pareja;

        if (! $pareja) {
            abort(404, 'Pareja no encontrada');
        }

        return Inertia::render('settings/pareja', [
            'pareja' => [
                'id' => $pareja->id,
                'fecha_ingreso' => $pareja->fecha_ingreso?->format('Y-m-d'),
                'numero_equipo' => $pareja->numero_equipo,
                'foto_base64' => $pareja->foto_base64,
                'estado' => $pareja->estado,
            ],
        ]);
    }

    /**
     * Actualizar pareja propia (settings).
     */
    public function updateSettings(\App\Http\Requests\Settings\ParejaUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $pareja = $user->pareja;

        if (! $pareja) {
            abort(404, 'Pareja no encontrada');
        }

        $pareja->fill($request->validated());
        $pareja->save();

        return to_route('pareja.edit')
            ->with('success', 'Pareja actualizada exitosamente.');
    }

    /**
     * Retirar pareja propia del movimiento (settings).
     */
    public function retirarSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pareja = $user->pareja;

        if (! $pareja) {
            abort(404, 'Pareja no encontrada');
        }

        // Solo se puede retirar si está activa
        if (! $pareja->estaActiva()) {
            return back()->withErrors([
                'estado' => 'La pareja ya está retirada.',
            ]);
        }

        // Retirar la pareja
        $pareja->retirar();

        // Cerrar sesión automáticamente
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')
            ->with('status', 'Su pareja ha sido retirada del movimiento. Ya no tiene acceso a la plataforma.');
    }
}
