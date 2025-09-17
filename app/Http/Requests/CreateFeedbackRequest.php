<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Accessible à tous pour les QR codes publics
    }

    public function rules(): array
    {
        $rules = [
            'type' => 'required|in:appreciation,incident,suggestion',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'service_id' => 'nullable|uuid|exists:services,id',
            'employee_id' => 'nullable|uuid|exists:employees,id',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120', // 5MB max
        ];

        // Règles spécifiques selon le type de feedback
        switch ($this->type) {
            case 'appreciation':
                $rules = array_merge($rules, [
                    'kalipoints' => 'required|integer|min:1|max:5',
                    'first_name' => 'nullable|string|max:50',
                    'last_name' => 'nullable|string|max:50',
                    'email' => 'nullable|email|max:255',
                    'phone' => 'nullable|string|max:20',
                ]);
                break;

            case 'incident':
            case 'suggestion':
                $rules = array_merge($rules, [
                    'first_name' => 'required|string|max:50',
                    'last_name' => 'required|string|max:50',
                    'email' => 'required|email|max:255',
                    'phone' => 'required|string|max:20',
                ]);
                break;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de feedback est obligatoire',
            'type.in' => 'Le type de feedback doit être: appréciation, incident ou suggestion',
            'title.required' => 'Le titre est obligatoire',
            'title.max' => 'Le titre ne doit pas dépasser 255 caractères',
            'description.required' => 'La description est obligatoire',
            'description.max' => 'La description ne doit pas dépasser 2000 caractères',
            'kalipoints.required' => 'La note est obligatoire pour les appréciations',
            'kalipoints.min' => 'La note doit être au minimum de 1',
            'kalipoints.max' => 'La note doit être au maximum de 5',
            'first_name.required' => 'Le prénom est obligatoire',
            'last_name.required' => 'Le nom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'phone.required' => 'Le téléphone est obligatoire',
            'attachment.mimes' => 'Le fichier doit être au format: JPEG, PNG, JPG, GIF, PDF, DOC, DOCX',
            'attachment.max' => 'Le fichier ne doit pas dépasser 5MB',
        ];
    }

    public function prepareForValidation()
    {
        // Nettoyer les données avant validation
        if ($this->type === 'appreciation' && !$this->kalipoints) {
            $this->merge(['kalipoints' => 3]); // Valeur par défaut
        }
    }
}