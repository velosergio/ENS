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
            'fecha_acogida' => ['required', 'date', 'before_or_equal:today'],
            'fecha_boda' => ['nullable', 'date', 'before_or_equal:today'],
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
            'fecha_acogida.required' => 'El campo fecha de acogida es obligatorio.',
            'fecha_acogida.date' => 'La fecha de acogida debe ser una fecha válida.',
            'fecha_acogida.before_or_equal' => 'La fecha de acogida no puede ser futura.',
            'fecha_boda.date' => 'La fecha de boda debe ser una fecha válida.',
            'fecha_boda.before_or_equal' => 'La fecha de boda no puede ser futura.',
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
