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
            'equipo_id' => ['nullable', 'exists:equipos,id'],
            'pareja_foto' => ['nullable', 'image', 'max:5120'], // 5MB max
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
            'equipo_id.exists' => 'El equipo seleccionado no existe.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar equipo_id si está vacío
        if ($this->has('equipo_id') && $this->input('equipo_id') === '') {
            $this->merge([
                'equipo_id' => null,
            ]);
        }
    }
}
