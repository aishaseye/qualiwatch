<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->tokenCan('*');
    }

    public function rules(): array
    {
        return [
            'admin_resolution_description' => 'required|string|max:2000',
            'admin_comments' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'admin_resolution_description.required' => 'La description de la résolution est obligatoire',
            'admin_resolution_description.max' => 'La description ne doit pas dépasser 2000 caractères',
            'admin_comments.max' => 'Les commentaires ne doivent pas dépasser 1000 caractères',
        ];
    }
}