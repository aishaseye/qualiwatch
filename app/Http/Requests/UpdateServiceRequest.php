<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->tokenCan('*');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du service est obligatoire',
            'name.min' => 'Le nom du service doit contenir au moins 2 caractères',
            'name.max' => 'Le nom du service ne doit pas dépasser 100 caractères',
            'description.max' => 'La description ne doit pas dépasser 500 caractères',
            'icon.max' => 'Le nom de l\'icône ne doit pas dépasser 50 caractères',
            'color.regex' => 'La couleur doit être au format hexadécimal (ex: #3B82F6)',
        ];
    }
}