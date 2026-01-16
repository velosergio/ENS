<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'responsable_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('equipos', 'responsable_id')->ignore($equipoId),
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
            'responsable_id.exists' => 'El responsable seleccionado no existe.',
            'responsable_id.unique' => 'El usuario seleccionado ya es responsable de otro equipo.',
        ];
    }
}
