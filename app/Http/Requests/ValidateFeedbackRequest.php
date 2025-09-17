<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Accessible à tous via token public
    }

    public function rules(): array
    {
        return [
            'validation_status' => 'required|in:satisfied,partially_satisfied,not_satisfied',
            'satisfaction_rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'validation_status.required' => 'Le statut de validation est obligatoire',
            'validation_status.in' => 'Le statut de validation doit être: satisfait, partiellement satisfait ou non satisfait',
            'satisfaction_rating.integer' => 'La note de satisfaction doit être un nombre entier',
            'satisfaction_rating.min' => 'La note de satisfaction doit être au minimum de 1',
            'satisfaction_rating.max' => 'La note de satisfaction doit être au maximum de 5',
            'comment.max' => 'Le commentaire ne doit pas dépasser 1000 caractères',
        ];
    }
}