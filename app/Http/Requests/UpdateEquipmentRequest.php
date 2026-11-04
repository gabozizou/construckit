<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends StoreEquipmentRequest
{
    /**
     * Al editar, todos los campos son opcionales (se puede mandar solo lo que cambia).
     */
    public function rules(): array
    {
        return [
            'code' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('equipment', 'code')->ignore($this->route('equipment')),
            ],
            'type' => ['sometimes', 'required', 'string', 'max:50'],
            'plate' => ['sometimes', 'nullable', 'string', 'max:20'],
            'brand' => ['sometimes', 'required', 'string', 'max:100'],
            'model' => ['sometimes', 'required', 'string', 'max:100'],
            'current_hourmeter' => ['prohibited'],
            'status' => ['sometimes', Rule::in(self::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'current_hourmeter.prohibited' => 'El horómetro no se edita directamente: se actualiza al registrar cargas o mantenciones.',
        ]);
    }
}