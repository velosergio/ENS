<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ParejaUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ParejaController extends Controller
{
    /**
     * Show the couple's settings page.
     */
    public function edit(Request $request): Response
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
     * Update the couple's settings.
     */
    public function update(ParejaUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $pareja = $user->pareja;

        if (! $pareja) {
            abort(404, 'Pareja no encontrada');
        }

        $pareja->fill($request->validated());
        $pareja->save();

        return to_route('pareja.edit');
    }

    /**
     * Retirar la pareja del movimiento.
     */
    public function retirar(Request $request): RedirectResponse
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

        return redirect('/login')->with('status', 'Su pareja ha sido retirada del movimiento. Ya no tiene acceso a la plataforma.');
    }
}
