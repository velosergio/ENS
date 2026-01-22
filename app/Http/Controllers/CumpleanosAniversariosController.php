<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Services\CumpleanosAniversariosService;
use App\Services\CumpleanosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CumpleanosAniversariosController extends Controller
{
    /**
     * Mostrar vista de cumpleaños y aniversarios del mes actual.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $mes = (int) $request->input('mes', now()->month);
        $año = (int) $request->input('año', now()->year);
        $equipoId = $request->input('equipo_id') ? (int) $request->input('equipo_id') : null;

        // Validar que el usuario tenga permiso (solo equipo y mango según especificación)
        if (! $user->esMango()) {
            $equipoUsuario = $user->equipo()?->id;
            if (! $equipoUsuario) {
                abort(403, 'Debes pertenecer a un equipo para ver cumpleaños y aniversarios.');
            }
            // Si no es mango, forzar el equipo del usuario
            $equipoId = $equipoUsuario;
        }

        $cumpleanosService = new CumpleanosService;
        $aniversariosService = new CumpleanosAniversariosService;

        // Obtener cumpleaños del mes
        $cumpleanos = $cumpleanosService->obtenerCumpleanosDelMes($mes, $año, $user, $equipoId);

        // Obtener aniversarios del mes
        $aniversarios = $aniversariosService->obtenerAniversariosDelMes($mes, $año, $user, $equipoId);

        // Obtener equipos para el filtro (solo si es mango/admin)
        $equipos = [];
        if ($user->esMango() || $user->esAdmin()) {
            $equipos = Equipo::query()
                ->orderBy('numero', 'asc')
                ->get()
                ->map(fn ($equipo) => [
                    'id' => $equipo->id,
                    'numero' => $equipo->numero,
                ]);
        }

        // Ocultar datos sensibles según rol
        $puedeVerDatosSensibles = $user->esMango() || $user->esAdmin();

        $cumpleanosFormateados = array_map(function ($cumpleano) use ($puedeVerDatosSensibles) {
            if (! $puedeVerDatosSensibles) {
                unset($cumpleano['email'], $cumpleano['celular']);
            }

            return $cumpleano;
        }, $cumpleanos);

        $aniversariosFormateados = array_map(function ($aniversario) use ($puedeVerDatosSensibles) {
            if (! $puedeVerDatosSensibles) {
                if (isset($aniversario['pareja']['el'])) {
                    unset($aniversario['pareja']['el']['email'], $aniversario['pareja']['el']['celular']);
                }
                if (isset($aniversario['pareja']['ella'])) {
                    unset($aniversario['pareja']['ella']['email'], $aniversario['pareja']['ella']['celular']);
                }
            }

            return $aniversario;
        }, $aniversarios);

        return Inertia::render('cumpleanos-aniversarios/index', [
            'cumpleanos' => $cumpleanosFormateados,
            'aniversarios' => $aniversariosFormateados,
            'mes' => $mes,
            'año' => $año,
            'equipo_id' => $equipoId,
            'equipos' => $equipos,
            'puedeVerDatosSensibles' => $puedeVerDatosSensibles,
        ]);
    }

    /**
     * Obtener próximos eventos (cumpleaños y aniversarios).
     */
    public function proximos(Request $request): JsonResponse
    {
        $user = $request->user();
        $dias = (int) $request->input('dias', 7); // Por defecto 7 días

        // Validar que el usuario tenga permiso
        if (! $user->esMango()) {
            $equipoUsuario = $user->equipo()?->id;
            if (! $equipoUsuario) {
                return response()->json([]);
            }
        }

        $aniversariosService = new CumpleanosAniversariosService;
        $equipoId = $user->esMango() ? ($request->input('equipo_id') ? (int) $request->input('equipo_id') : null) : $user->equipo()?->id;

        $eventos = $aniversariosService->obtenerProximosEventos($dias, $user, $equipoId);

        // Ocultar datos sensibles según rol
        $puedeVerDatosSensibles = $user->esMango() || $user->esAdmin();

        $eventosFormateados = array_map(function ($evento) use ($puedeVerDatosSensibles) {
            if (! $puedeVerDatosSensibles) {
                if (isset($evento['email'])) {
                    unset($evento['email'], $evento['celular']);
                }
                if (isset($evento['pareja'])) {
                    if (isset($evento['pareja']['el'])) {
                        unset($evento['pareja']['el']['email'], $evento['pareja']['el']['celular']);
                    }
                    if (isset($evento['pareja']['ella'])) {
                        unset($evento['pareja']['ella']['email'], $evento['pareja']['ella']['celular']);
                    }
                }
            }

            return $evento;
        }, $eventos);

        return response()->json($eventosFormateados);
    }
}
