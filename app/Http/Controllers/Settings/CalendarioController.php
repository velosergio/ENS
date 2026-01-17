<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionCalendario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarioController extends Controller
{
    /**
     * Mostrar la página de configuración del calendario.
     */
    public function edit(Request $request): Response
    {
        $configuraciones = ConfiguracionCalendario::all()->keyBy('tipo_evento');

        return Inertia::render('settings/calendario', [
            'configuraciones' => $configuraciones->map(function ($config) {
                return [
                    'id' => $config->id,
                    'tipo_evento' => $config->tipo_evento,
                    'color' => $config->color,
                    'icono' => $config->icono,
                ];
            })->values(),
        ]);
    }

    /**
     * Actualizar la configuración del calendario.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'configuraciones' => ['required', 'array'],
            'configuraciones.*.id' => ['required', 'exists:configuracion_calendario,id'],
            'configuraciones.*.color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'configuraciones.*.icono' => ['nullable', 'string', 'max:100'],
        ], [
            'configuraciones.required' => 'Debe proporcionar las configuraciones.',
            'configuraciones.*.id.required' => 'El ID de la configuración es requerido.',
            'configuraciones.*.id.exists' => 'La configuración especificada no existe.',
            'configuraciones.*.color.required' => 'El color es obligatorio.',
            'configuraciones.*.color.regex' => 'El color debe ser un código hexadecimal válido (ej: #3b82f6).',
            'configuraciones.*.icono.max' => 'El nombre del icono no puede tener más de 100 caracteres.',
        ]);

        foreach ($request->configuraciones as $configData) {
            $config = ConfiguracionCalendario::findOrFail($configData['id']);
            $config->update([
                'color' => $configData['color'],
                'icono' => $configData['icono'] ?? null,
            ]);
        }

        return redirect()->route('calendario.configuracion.edit')->with('status', 'Configuración del calendario actualizada correctamente.');
    }
}
