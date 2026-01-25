<?php

namespace App\Http\Controllers;

use App\Services\GuiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuiaController extends Controller
{
    public function __construct(
        protected GuiaService $guiaService
    ) {}

    /**
     * Mostrar página del chatbot GUIA.
     */
    public function index(): Response
    {
        return Inertia::render('guia/index');
    }

    /**
     * Enviar mensaje al webhook y recibir respuesta.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mensaje' => ['required', 'string', 'max:1000'],
        ]);

        $resultado = $this->guiaService->enviarMensaje(
            $validated['mensaje'],
            $request->user()
        );

        if ($resultado['exito']) {
            return response()->json([
                'success' => true,
                'respuesta' => $resultado['respuesta'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $resultado['error'],
        ], 500);
    }
}
