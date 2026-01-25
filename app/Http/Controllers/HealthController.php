<?php

namespace App\Http\Controllers;

use App\Services\HealthService;
use Inertia\Inertia;
use Inertia\Response;

class HealthController extends Controller
{
    public function __construct(
        protected HealthService $healthService
    ) {}

    /**
     * Mostrar página de salud del sistema.
     */
    public function index(): Response
    {
        $estado = $this->healthService->verificarEstado();

        return Inertia::render('health/index', [
            'estado' => $estado,
        ]);
    }
}
