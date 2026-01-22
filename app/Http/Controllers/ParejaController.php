<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParejaCreateRequest;
use App\Http\Requests\ParejaUpdateRequest;
use App\Models\Pareja;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ParejaController extends Controller
{
    public function __construct(
        protected ImageService $imageService,
    ) {}

    /**
     * Listar parejas con búsqueda y filtros.
     */
    public function index(Request $request): Response
    {
        $query = Pareja::query()
            ->with([
                'usuarios' => function ($q) {
                    $q->orderBy('sexo');
                },
                'equipo',
            ]);

        // Solo excluir parejas con usuarios mango si el usuario actual no es mango o admin
        $user = Auth::user();
        if (! $user || ($user->rol !== 'mango' && $user->rol !== 'admin')) {
            $query->sinMango(); // Excluir parejas con usuarios mango
        }

        // Filtro por estado
        if ($request->has('estado') && $request->estado !== '' && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        } elseif (! $request->has('estado') || $request->estado === '') {
            // Por defecto solo activas si no se especifica estado
            $query->where('estado', 'activo');
        }
        // Si estado === 'todos', no se aplica filtro de estado

        // Filtro por equipo
        if ($request->has('equipo_id') && $request->equipo_id !== '') {
            $query->where('equipo_id', $request->equipo_id);
        }

        // Búsqueda en tiempo real
        if ($request->has('buscar') && $request->buscar !== '') {
            $query->buscar($request->buscar);
        }

        // Ordenar por fecha de acogida descendente
        $parejas = $query->orderBy('fecha_acogida', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($pareja) {
                $el = $pareja->el();
                $ella = $pareja->ella();

                return [
                    'id' => $pareja->id,
                    'equipo_id' => $pareja->equipo_id,
                    'equipo' => $pareja->equipo ? [
                        'id' => $pareja->equipo->id,
                        'numero' => $pareja->equipo->numero,
                    ] : null,
                    'fecha_acogida' => $pareja->fecha_acogida?->format('Y-m-d'),
                    'fecha_boda' => $pareja->fecha_boda?->format('Y-m-d'),
                    'estado' => $pareja->estado,
                    'foto_thumbnail_50' => $pareja->foto_thumbnail_50_url,
                    'el' => $el ? [
                        'id' => $el->id,
                        'nombres' => $el->nombres,
                        'apellidos' => $el->apellidos,
                        'email' => $el->email,
                        'cedula' => $el->cedula,
                        'celular' => $el->celular,
                        'fecha_nacimiento' => $el->fecha_nacimiento?->format('Y-m-d'),
                    ] : null,
                    'ella' => $ella ? [
                        'id' => $ella->id,
                        'nombres' => $ella->nombres,
                        'apellidos' => $ella->apellidos,
                        'email' => $ella->email,
                        'cedula' => $ella->cedula,
                        'celular' => $ella->celular,
                        'fecha_nacimiento' => $ella->fecha_nacimiento?->format('Y-m-d'),
                    ] : null,
                ];
            });

        $equipos = \App\Models\Equipo::query()
            ->orderBy('numero', 'asc')
            ->get()
            ->map(fn ($equipo) => [
                'id' => $equipo->id,
                'numero' => $equipo->numero,
            ]);

        return Inertia::render('parejas/index', [
            'parejas' => Inertia::scroll($parejas),
            'filters' => [
                'buscar' => $request->buscar,
                'estado' => $request->estado ?? 'activo',
                'equipo_id' => $request->equipo_id,
            ],
            'equipos' => $equipos,
        ]);
    }

    /**
     * Mostrar formulario para crear pareja.
     */
    public function create(): Response
    {
        $equipos = \App\Models\Equipo::query()
            ->orderBy('numero', 'asc')
            ->get()
            ->map(fn ($equipo) => [
                'id' => $equipo->id,
                'numero' => $equipo->numero,
            ]);

        return Inertia::render('parejas/create', [
            'equipos' => $equipos,
        ]);
    }

    /**
     * Crear una nueva pareja con sus dos usuarios.
     */
    public function store(ParejaCreateRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            // Guardar imagen de la pareja y generar thumbnails
            $parejaImages = $this->imageService->saveImageFromFile(
                $request->file('pareja_foto'),
                'parejas',
            );

            // Crear la pareja
            $pareja = Pareja::create([
                'fecha_acogida' => $request->fecha_acogida,
                'fecha_boda' => $request->fecha_boda ?? null,
                'equipo_id' => $request->equipo_id,
                'foto_path' => $parejaImages['original'],
                'foto_thumbnail_50' => $parejaImages['50'],
                'foto_thumbnail_100' => $parejaImages['100'],
                'foto_thumbnail_500' => $parejaImages['500'],
                'estado' => 'activo',
            ]);

            // Guardar imagen de ÉL y generar thumbnails
            $elImages = $this->imageService->saveImageFromFile(
                $request->file('el_foto'),
                'users',
            );

            // Crear usuario ÉL
            $el = User::create([
                'nombres' => $request->el_nombres,
                'apellidos' => $request->el_apellidos,
                'cedula' => $request->el_cedula ?? null,
                'celular' => $request->el_celular,
                'fecha_nacimiento' => $request->el_fecha_nacimiento,
                'sexo' => 'masculino',
                'email' => $request->el_email,
                'foto_path' => $elImages['original'],
                'foto_thumbnail_50' => $elImages['50'],
                'foto_thumbnail_100' => $elImages['100'],
                'foto_thumbnail_500' => $elImages['500'],
                'password' => $request->password,
                'pareja_id' => $pareja->id,
                'rol' => 'equipista',
            ]);

            // Guardar imagen de ELLA y generar thumbnails
            $ellaImages = $this->imageService->saveImageFromFile(
                $request->file('ella_foto'),
                'users',
            );

            // Crear usuario ELLA
            $ella = User::create([
                'nombres' => $request->ella_nombres,
                'apellidos' => $request->ella_apellidos,
                'cedula' => $request->ella_cedula ?? null,
                'celular' => $request->ella_celular,
                'fecha_nacimiento' => $request->ella_fecha_nacimiento,
                'sexo' => 'femenino',
                'email' => $request->ella_email,
                'foto_path' => $ellaImages['original'],
                'foto_thumbnail_50' => $ellaImages['50'],
                'foto_thumbnail_100' => $ellaImages['100'],
                'foto_thumbnail_500' => $ellaImages['500'],
                'password' => $request->password,
                'pareja_id' => $pareja->id,
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
        $pareja->load([
            'usuarios' => function ($q) {
                $q->orderBy('sexo');
            },
            'equipo',
        ]);

        $el = $pareja->el();
        $ella = $pareja->ella();

        $equipos = \App\Models\Equipo::query()
            ->orderBy('numero', 'asc')
            ->get()
            ->map(fn ($equipo) => [
                'id' => $equipo->id,
                'numero' => $equipo->numero,
            ]);

        return Inertia::render('parejas/edit', [
            'pareja' => [
                'id' => $pareja->id,
                'fecha_acogida' => $pareja->fecha_acogida?->format('Y-m-d'),
                'equipo_id' => $pareja->equipo_id,
                'equipo' => $pareja->equipo ? [
                    'id' => $pareja->equipo->id,
                    'numero' => $pareja->equipo->numero,
                ] : null,
                'pareja_foto_url' => $pareja->foto_url,
                'estado' => $pareja->estado,
                'el' => $el ? [
                    'id' => $el->id,
                    'nombres' => $el->nombres,
                    'apellidos' => $el->apellidos,
                    'email' => $el->email,
                    'cedula' => $el->cedula,
                    'celular' => $el->celular,
                    'fecha_nacimiento' => $el->fecha_nacimiento?->format('Y-m-d'),
                    'foto_url' => $el->foto_url,
                    'foto_thumbnail_50' => $el->foto_thumbnail_50_url,
                ] : null,
                'ella' => $ella ? [
                    'id' => $ella->id,
                    'nombres' => $ella->nombres,
                    'apellidos' => $ella->apellidos,
                    'email' => $ella->email,
                    'cedula' => $ella->cedula,
                    'celular' => $ella->celular,
                    'fecha_nacimiento' => $ella->fecha_nacimiento?->format('Y-m-d'),
                    'foto_url' => $ella->foto_url,
                    'foto_thumbnail_50' => $ella->foto_thumbnail_50_url,
                ] : null,
            ],
            'equipos' => $equipos,
        ]);
    }

    /**
     * Actualizar pareja y sus usuarios.
     */
    public function update(ParejaUpdateRequest $request, Pareja $pareja): RedirectResponse
    {
        DB::transaction(function () use ($request, $pareja) {
            // Guardar imagen de la pareja si se actualiza
            $parejaImages = $this->imageService->saveImageFromFile(
                $request->file('pareja_foto'),
                'parejas',
                $pareja->foto_path,
            );

            // Actualizar pareja
            $pareja->update([
                'fecha_acogida' => $request->fecha_acogida,
                'fecha_boda' => $request->fecha_boda ?? null,
                'equipo_id' => $request->equipo_id,
                'foto_path' => $parejaImages['original'],
                'foto_thumbnail_50' => $parejaImages['50'],
                'foto_thumbnail_100' => $parejaImages['100'],
                'foto_thumbnail_500' => $parejaImages['500'],
                'estado' => $request->estado,
            ]);

            // Actualizar usuario ÉL
            if ($request->el_id) {
                $el = User::findOrFail($request->el_id);
                $elImages = $this->imageService->saveImageFromFile(
                    $request->file('el_foto'),
                    'users',
                    $el->foto_path,
                );

                $el->update([
                    'nombres' => $request->el_nombres,
                    'apellidos' => $request->el_apellidos,
                    'cedula' => $request->el_cedula ?? null,
                    'celular' => $request->el_celular,
                    'fecha_nacimiento' => $request->el_fecha_nacimiento,
                    'email' => $request->el_email,
                    'foto_path' => $elImages['original'],
                    'foto_thumbnail_50' => $elImages['50'],
                    'foto_thumbnail_100' => $elImages['100'],
                    'foto_thumbnail_500' => $elImages['500'],
                ]);
            }

            // Actualizar usuario ELLA
            if ($request->ella_id) {
                $ella = User::findOrFail($request->ella_id);
                $ellaImages = $this->imageService->saveImageFromFile(
                    $request->file('ella_foto'),
                    'users',
                    $ella->foto_path,
                );

                $ella->update([
                    'nombres' => $request->ella_nombres,
                    'apellidos' => $request->ella_apellidos,
                    'cedula' => $request->ella_cedula ?? null,
                    'celular' => $request->ella_celular,
                    'fecha_nacimiento' => $request->ella_fecha_nacimiento,
                    'email' => $request->ella_email,
                    'foto_path' => $ellaImages['original'],
                    'foto_thumbnail_50' => $ellaImages['50'],
                    'foto_thumbnail_100' => $ellaImages['100'],
                    'foto_thumbnail_500' => $ellaImages['500'],
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

        $pareja->load('equipo');

        $equipos = \App\Models\Equipo::query()
            ->orderBy('numero', 'asc')
            ->get()
            ->map(fn ($equipo) => [
                'id' => $equipo->id,
                'numero' => $equipo->numero,
            ]);

        return Inertia::render('settings/pareja', [
            'pareja' => [
                'id' => $pareja->id,
                'fecha_acogida' => $pareja->fecha_acogida?->format('Y-m-d'),
                'fecha_boda' => $pareja->fecha_boda?->format('Y-m-d'),
                'equipo_id' => $pareja->equipo_id,
                'equipo' => $pareja->equipo ? [
                    'id' => $pareja->equipo->id,
                    'numero' => $pareja->equipo->numero,
                ] : null,
                'foto_url' => $pareja->foto_url,
                'foto_thumbnail_50' => $pareja->foto_thumbnail_50_url,
                'estado' => $pareja->estado,
            ],
            'equipos' => $equipos,
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

        // Guardar imagen si se actualiza
        $parejaImages = $this->imageService->saveImageFromFile(
            $request->file('pareja_foto'),
            'parejas',
            $pareja->foto_path,
        );

        $pareja->fill($request->validated());
        $pareja->foto_path = $parejaImages['original'];
        $pareja->foto_thumbnail_50 = $parejaImages['50'];
        $pareja->foto_thumbnail_100 = $parejaImages['100'];
        $pareja->foto_thumbnail_500 = $parejaImages['500'];
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
