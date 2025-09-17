<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => 'required|string', // Email ou téléphone
            'password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'L\'email ou le téléphone est obligatoire',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
        ];
    }
}