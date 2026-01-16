<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ParejaController as MainParejaController;
use App\Http\Requests\Settings\ParejaUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Controlador para configuración de pareja propia.
 *
 * Este controlador delega al ParejaController principal
 * manteniendo la compatibilidad con la estructura de settings.
 */
class ParejaController extends Controller
{
    /**
     * Show the couple's settings page.
     */
    public function edit(Request $request): Response
    {
        return app(MainParejaController::class)->editSettings($request);
    }

    /**
     * Update the couple's settings.
     */
    public function update(ParejaUpdateRequest $request): RedirectResponse
    {
        return app(MainParejaController::class)->updateSettings($request);
    }

    /**
     * Retirar la pareja del movimiento.
     */
    public function retirar(Request $request): RedirectResponse
    {
        return app(MainParejaController::class)->retirarSettings($request);
    }
}
