<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventoCalendarioCreateRequest extends FormRequest
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
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
            'es_todo_el_dia' => ['nullable', 'boolean'],
            'tipo' => ['required', Rule::in(['general', 'formacion', 'retiro_espiritual', 'reunion_equipo'])],
            'alcance' => ['required', Rule::in(['equipo', 'global'])],
            'equipo_id' => ['nullable', 'required_if:alcance,equipo', 'exists:equipos,id'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icono' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fechaInicio = $this->input('fecha_inicio');
            $fechaFin = $this->input('fecha_fin');

            if ($fechaInicio && $fechaFin) {
                $inicio = \Carbon\Carbon::parse($fechaInicio);
                $fin = \Carbon\Carbon::parse($fechaFin);
                $diferenciaAnios = $inicio->diffInYears($fin, false);

                if ($diferenciaAnios > 1) {
                    $validator->errors()->add(
                        'fecha_fin',
                        'La diferencia entre la fecha de inicio y fin no puede ser mayor a un año.'
                    );
                }
            }

            // Si es todo el día, las horas deben ser null
            if ($this->input('es_todo_el_dia')) {
                if ($this->input('hora_inicio') !== null || $this->input('hora_fin') !== null) {
                    $validator->errors()->add(
                        'es_todo_el_dia',
                        'Los eventos de todo el día no deben tener hora de inicio o fin.'
                    );
                }
            } else {
                // Si no es todo el día, las horas son requeridas
                if (! $this->input('hora_inicio') || ! $this->input('hora_fin')) {
                    $validator->errors()->add(
                        'hora_inicio',
                        'Los eventos con hora específica deben tener hora de inicio y fin.'
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.required' => 'El título del evento es obligatorio.',
            'titulo.max' => 'El título no puede tener más de 255 caracteres.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'hora_fin.date_format' => 'La hora de fin debe tener el formato HH:MM.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'tipo.required' => 'El tipo de evento es obligatorio.',
            'tipo.in' => 'El tipo de evento seleccionado no es válido.',
            'alcance.required' => 'El alcance del evento es obligatorio.',
            'alcance.in' => 'El alcance seleccionado no es válido.',
            'equipo_id.required_if' => 'Debe seleccionar un equipo cuando el alcance es por equipo.',
            'equipo_id.exists' => 'El equipo seleccionado no existe.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (ej: #3b82f6).',
        ];
    }
}
