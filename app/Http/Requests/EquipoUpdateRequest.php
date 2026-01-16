<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipoUpdateRequest extends FormRequest
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
            'numero' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('equipos', 'numero')->ignore($equipoId),
            ],
            'consiliario_nombre' => ['nullable', 'string', 'max:255'],
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
            'numero.required' => 'El número del equipo es obligatorio.',
            'numero.integer' => 'El número del equipo debe ser un número entero.',
            'numero.min' => 'El número del equipo debe ser mayor o igual a 1.',
            'numero.unique' => 'Ya existe un equipo con ese número.',
            'consiliario_nombre.max' => 'El nombre del consiliario no puede exceder 255 caracteres.',
        ];
    }
}
