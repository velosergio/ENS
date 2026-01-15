<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StepTwoRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // El sexo es siempre requerido cuando se envía el formulario
        // Si viene de un guardado automático (preserve), solo validar si se envía sexo
        $isAutoSave = $this->has('_preserve');
        
        return [
            'sexo' => $isAutoSave 
                ? ['nullable', Rule::in(['masculino', 'femenino'])] 
                : ['required', Rule::in(['masculino', 'femenino'])],
            'foto_base64' => ['nullable', 'string'],
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
            'sexo.required' => 'Debes seleccionar tu sexo.',
            'sexo.in' => 'El sexo seleccionado no es válido.',
        ];
    }
}
