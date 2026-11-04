<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentRequest extends FormRequest
{
    public const STATUSES = ['operativo', 'en_mantencion', 'fuera_de_servicio'];

    /**
     * Determine if the user is authorized to make this request.
     * El acceso se controla con Sanctum a nivel de ruta.
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
            'code' => ['required', 'string', 'max:50', 'unique:equipment,code'],
            'type' => ['required', 'string', 'max:50'],
            'plate' => ['nullable', 'string', 'max:20'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'current_hourmeter' => ['sometimes', 'numeric', 'min:0', 'max:999999999.9'],
            'status' => ['sometimes', Rule::in(self::STATUSES)],
        ];
    }

    /**
     * Mensajes de error en español.
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no puede superar :max.',
            'min' => 'El campo :attribute no puede ser menor a :min.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'unique' => 'Ya existe un equipo con ese :attribute.',
            'in' => 'El :attribute no es válido. Valores permitidos: ' . implode(', ', self::STATUSES) . '.',
        ];
    }

    /**
     * Nombres de los campos en español para los mensajes.
     */
    public function attributes(): array
    {
        return [
            'code' => 'código',
            'type' => 'tipo',
            'plate' => 'patente',
            'brand' => 'marca',
            'model' => 'modelo',
            'current_hourmeter' => 'horómetro actual',
            'status' => 'estado',
        ];
    }
}