<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->tokenCan('*');
    }

    public function rules(): array
    {
        return [
            'service_id' => 'nullable|uuid|exists:services,id',
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => 'nullable|email|max:255|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'hire_date' => 'nullable|date|before_or_equal:today',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.exists' => 'Le service sélectionné n\'existe pas',
            'first_name.required' => 'Le prénom est obligatoire',
            'first_name.min' => 'Le prénom doit contenir au moins 2 caractères',
            'last_name.required' => 'Le nom est obligatoire',
            'last_name.min' => 'Le nom doit contenir au moins 2 caractères',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cette adresse email est déjà utilisée',
            'photo.image' => 'Le fichier doit être une image',
            'photo.mimes' => 'L\'image doit être au format JPEG, PNG, JPG ou GIF',
            'photo.max' => 'L\'image ne doit pas dépasser 2MB',
            'hire_date.date' => 'La date d\'embauche doit être une date valide',
            'hire_date.before_or_equal' => 'La date d\'embauche ne peut pas être dans le futur',
        ];
    }
}