<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends ApiRequest
{
    public const TYPES = ['preventiva', 'correctiva'];

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(self::TYPES)],
            'performed_on' => ['required', 'date', 'before_or_equal:today'],
            'hourmeter' => ['required', 'numeric', 'min:0', 'max:999999999.9'],
            'cost_clp' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'type.in' => 'El tipo no es válido. Valores permitidos: ' . implode(', ', self::TYPES) . '.',
        ]);
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo',
            'performed_on' => 'fecha',
            'hourmeter' => 'horómetro',
            'cost_clp' => 'costo',
            'description' => 'descripción',
        ];
    }
}