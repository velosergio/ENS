<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuiaService
{
    /**
     * Enviar mensaje al webhook de n8n y esperar respuesta.
     *
     * @return array<string, mixed>
     */
    public function enviarMensaje(string $mensaje, ?User $usuario = null): array
    {
        $webhookUrl = config('services.guia.webhook');

        if (! $webhookUrl) {
            return [
                'exito' => false,
                'error' => 'GUIA_WEBHOOK no está configurado en el archivo .env',
                'respuesta' => null,
            ];
        }

        try {
            $timeout = config('services.guia.timeout', 30);

            // Preparar datos del usuario si está disponible
            $usuarioData = null;
            if ($usuario) {
                $usuarioData = [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombres.' '.$usuario->apellidos,
                    'email' => $usuario->email,
                    'rol' => $usuario->rol,
                ];
            }

            // Preparar payload
            $payload = [
                'mensaje' => $mensaje,
                'usuario' => $usuarioData,
                'timestamp' => now()->toIso8601String(),
            ];

            // Enviar petición al webhook
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($webhookUrl, $payload);

            $responseData = $response->json() ?? [];

            if ($response->successful()) {
                // El webhook puede responder con { "respuesta": "..." } o directamente el texto
                $respuesta = $responseData['respuesta'] ?? $responseData['message'] ?? $response->body();

                return [
                    'exito' => true,
                    'respuesta' => $respuesta,
                    'error' => null,
                ];
            }

            // Si hay error en la respuesta
            $errorMessage = $responseData['error'] ?? $responseData['message'] ?? 'Error desconocido del webhook';

            return [
                'exito' => false,
                'error' => $errorMessage,
                'respuesta' => null,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Error de conexión con webhook GUIA', [
                'url' => $webhookUrl,
                'error' => $e->getMessage(),
            ]);

            return [
                'exito' => false,
                'error' => 'No se pudo conectar con el servidor de GUIA. Verifica que el webhook esté disponible.',
                'respuesta' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje a GUIA', [
                'mensaje' => $mensaje,
                'error' => $e->getMessage(),
            ]);

            return [
                'exito' => false,
                'error' => 'Error inesperado: '.$e->getMessage(),
                'respuesta' => null,
            ];
        }
    }

    /**
     * Verificar que el webhook esté configurado (para health check).
     *
     * @return array<string, mixed>
     */
    public function verificarWebhook(): array
    {
        $webhookUrl = config('services.guia.webhook');

        if (! $webhookUrl) {
            return [
                'configurado' => false,
                'mensaje' => 'GUIA_WEBHOOK no está configurado',
            ];
        }

        return [
            'configurado' => true,
            'mensaje' => 'Webhook configurado correctamente',
            'url' => $webhookUrl,
        ];
    }
}
