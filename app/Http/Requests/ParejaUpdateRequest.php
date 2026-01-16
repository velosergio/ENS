<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParejaUpdateRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $parejaId = $this->route('pareja')?->id;

        return [
            // Datos de la pareja
            'fecha_ingreso' => ['required', 'date', 'before_or_equal:today'],
            'equipo_id' => ['nullable', 'exists:equipos,id'],
            'pareja_foto' => ['nullable', 'image', 'max:5120'], // 5MB max
            'estado' => ['required', 'in:activo,retirado'],

            // Datos de ÉL
            'el_nombres' => ['required', 'string', 'max:255'],
            'el_apellidos' => ['required', 'string', 'max:255'],
            'el_celular' => ['required', 'string', 'max:20'],
            'el_fecha_nacimiento' => ['required', 'date', 'before:today'],
            'el_email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(
                    $this->input('el_id'),
                    'id'
                ),
            ],
            'el_foto' => ['nullable', 'image', 'max:5120'], // 5MB max
            'el_id' => ['required', 'integer', 'exists:users,id'],

            // Datos de ELLA
            'ella_nombres' => ['required', 'string', 'max:255'],
            'ella_apellidos' => ['required', 'string', 'max:255'],
            'ella_celular' => ['required', 'string', 'max:20'],
            'ella_fecha_nacimiento' => ['required', 'date', 'before:today'],
            'ella_email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(
                    $this->input('ella_id'),
                    'id'
                ),
            ],
            'ella_foto' => ['nullable', 'image', 'max:5120'], // 5MB max
            'ella_id' => ['required', 'integer', 'exists:users,id'],
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
            // Pareja
            'fecha_ingreso.required' => 'El campo fecha de ingreso es obligatorio.',
            'fecha_ingreso.date' => 'La fecha de ingreso debe ser una fecha válida.',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser futura.',
            'equipo_id.exists' => 'El equipo seleccionado no existe.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser activo o retirado.',

            // ÉL
            'el_nombres.required' => 'Los nombres de ÉL son obligatorios.',
            'el_apellidos.required' => 'Los apellidos de ÉL son obligatorios.',
            'el_celular.required' => 'El celular de ÉL es obligatorio.',
            'el_fecha_nacimiento.required' => 'La fecha de nacimiento de ÉL es obligatoria.',
            'el_fecha_nacimiento.before' => 'La fecha de nacimiento de ÉL debe ser anterior a hoy.',
            'el_email.required' => 'El email de ÉL es obligatorio.',
            'el_email.unique' => 'El email de ÉL ya está registrado.',
            'el_id.required' => 'El ID de ÉL es obligatorio.',
            'el_id.exists' => 'El usuario ÉL no existe.',

            // ELLA
            'ella_nombres.required' => 'Los nombres de ELLA son obligatorios.',
            'ella_apellidos.required' => 'Los apellidos de ELLA son obligatorios.',
            'ella_celular.required' => 'El celular de ELLA es obligatorio.',
            'ella_fecha_nacimiento.required' => 'La fecha de nacimiento de ELLA es obligatoria.',
            'ella_fecha_nacimiento.before' => 'La fecha de nacimiento de ELLA debe ser anterior a hoy.',
            'ella_email.required' => 'El email de ELLA es obligatorio.',
            'ella_email.unique' => 'El email de ELLA ya está registrado.',
            'ella_id.required' => 'El ID de ELLA es obligatorio.',
            'ella_id.exists' => 'El usuario ELLA no existe.',
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
