<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ParejaCreateRequest extends FormRequest
{
    use PasswordValidationRules;

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
        return [
            // Datos de ÉL
            'el_nombres' => ['required', 'string', 'max:255'],
            'el_apellidos' => ['required', 'string', 'max:255'],
            'el_celular' => ['required', 'string', 'max:20'],
            'el_fecha_nacimiento' => ['required', 'date', 'before:today'],
            'el_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'el_foto_base64' => ['nullable', 'string'],

            // Datos de ELLA
            'ella_nombres' => ['required', 'string', 'max:255'],
            'ella_apellidos' => ['required', 'string', 'max:255'],
            'ella_celular' => ['required', 'string', 'max:20'],
            'ella_fecha_nacimiento' => ['required', 'date', 'before:today'],
            'ella_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'ella_foto_base64' => ['nullable', 'string'],

            // Datos de la pareja
            'fecha_ingreso' => ['required', 'date', 'before_or_equal:today'],
            'equipo_id' => ['nullable', 'exists:equipos,id'],
            'pareja_foto_base64' => ['nullable', 'string'],
            'password' => $this->passwordRules(),
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
            // ÉL
            'el_nombres.required' => 'Los nombres de ÉL son obligatorios.',
            'el_apellidos.required' => 'Los apellidos de ÉL son obligatorios.',
            'el_celular.required' => 'El celular de ÉL es obligatorio.',
            'el_fecha_nacimiento.required' => 'La fecha de nacimiento de ÉL es obligatoria.',
            'el_fecha_nacimiento.before' => 'La fecha de nacimiento de ÉL debe ser anterior a hoy.',
            'el_email.required' => 'El email de ÉL es obligatorio.',
            'el_email.unique' => 'El email de ÉL ya está registrado.',

            // ELLA
            'ella_nombres.required' => 'Los nombres de ELLA son obligatorios.',
            'ella_apellidos.required' => 'Los apellidos de ELLA son obligatorios.',
            'ella_celular.required' => 'El celular de ELLA es obligatorio.',
            'ella_fecha_nacimiento.required' => 'La fecha de nacimiento de ELLA es obligatoria.',
            'ella_fecha_nacimiento.before' => 'La fecha de nacimiento de ELLA debe ser anterior a hoy.',
            'ella_email.required' => 'El email de ELLA es obligatorio.',
            'ella_email.unique' => 'El email de ELLA ya está registrado.',

            // Pareja
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'fecha_ingreso.date' => 'La fecha de ingreso debe ser una fecha válida.',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser futura.',
            'equipo_id.exists' => 'El equipo seleccionado no existe.',

            // Password
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
