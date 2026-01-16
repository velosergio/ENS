<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EquipoAsignarResponsableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el controlador con middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $equipoId = $this->route('equipo')->id ?? null;

        return [
            'pareja_id' => [
                'nullable',
                'exists:parejas,id',
                function ($attribute, $value, $fail) use ($equipoId) {
                    if ($value) {
                        // Obtener la pareja
                        $pareja = \App\Models\Pareja::find($value);
                        if ($pareja) {
                            // Obtener el usuario masculino de la pareja (o el primero si no hay masculino)
                            $usuario = $pareja->usuarios()->where('sexo', 'masculino')->first() ?? $pareja->usuarios->first();
                            if ($usuario) {
                                // Verificar si este usuario ya es responsable de otro equipo
                                $equipoExistente = \App\Models\Equipo::where('responsable_id', $usuario->id)
                                    ->where('id', '!=', $equipoId)
                                    ->first();
                                if ($equipoExistente) {
                                    $fail('Esta pareja ya es responsable de otro equipo.');
                                }
                            }
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pareja_id.exists' => 'La pareja seleccionada no existe.',
        ];
    }
}
