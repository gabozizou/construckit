<?php

namespace App\Http\Requests;

class StoreFuelLoadRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'loaded_at' => ['required', 'date', 'before_or_equal:now'],
            'liters' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'amount_clp' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'hourmeter' => ['required', 'numeric', 'min:0', 'max:999999999.9'],
        ];
    }

    public function attributes(): array
    {
        return [
            'loaded_at' => 'fecha y hora de carga',
            'liters' => 'litros',
            'amount_clp' => 'monto',
            'hourmeter' => 'horómetro',
        ];
    }
}