<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreEquipmentRequest extends ApiRequest
{
    public const STATUSES = ['operativo', 'en_mantencion', 'fuera_de_servicio'];

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
     * Mensajes propios de equipos (los genéricos vienen de ApiRequest).
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'unique' => 'Ya existe un equipo con ese :attribute.',
            'status.in' => 'El estado no es válido. Valores permitidos: ' . implode(', ', self::STATUSES) . '.',
        ]);
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