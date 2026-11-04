<?php

namespace App\Http\Requests;

class LoginRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'email' => 'El campo :attribute debe ser un correo válido.',
        ]);
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo',
            'password' => 'contraseña',
            'device_name' => 'nombre del dispositivo',
        ];
    }
}