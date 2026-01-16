<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ParejaUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fecha_ingreso' => ['required', 'date', 'before_or_equal:today'],
            'numero_equipo' => ['nullable', 'integer', 'min:0'],
            'pareja_foto_base64' => ['nullable', 'string'],
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
            'fecha_ingreso.required' => 'El campo fecha de ingreso es obligatorio.',
            'fecha_ingreso.date' => 'La fecha de ingreso debe ser una fecha válida.',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser futura.',
            'numero_equipo.integer' => 'El número del equipo debe ser un número entero.',
            'numero_equipo.min' => 'El número del equipo debe ser mayor o igual a 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Mapear pareja_foto_base64 a foto_base64 para el modelo
        if ($this->has('pareja_foto_base64')) {
            $this->merge([
                'foto_base64' => $this->input('pareja_foto_base64'),
            ]);
        }

        // Limpiar numero_equipo si está vacío
        if ($this->has('numero_equipo') && $this->input('numero_equipo') === '') {
            $this->merge([
                'numero_equipo' => null,
            ]);
        }
    }
}
