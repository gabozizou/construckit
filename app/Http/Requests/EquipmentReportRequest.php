<?php

namespace App\Http\Requests;

class EquipmentReportRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'date_format' => 'El campo :attribute debe tener el formato AAAA-MM-DD.',
            'to.after_or_equal' => 'La fecha de término no puede ser anterior a la fecha de inicio.',
        ]);
    }

    public function attributes(): array
    {
        return [
            'from' => 'fecha de inicio',
            'to' => 'fecha de término',
        ];
    }
}