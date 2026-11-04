<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base para las validaciones de la API: autorización y mensajes en español.
 * El acceso se controla con Sanctum a nivel de ruta.
 */
abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no puede superar :max.',
            'min' => 'El campo :attribute no puede ser menor a :min.',
            'gt' => 'El campo :attribute debe ser mayor a :value.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'before_or_equal' => 'El campo :attribute no puede ser una fecha futura.',
            'in' => 'El valor de :attribute no es válido.',
        ];
    }
}