<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStepTwoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->tokenCan('registration');
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|min:2|max:100',
            'company_email' => 'required|email|unique:companies,email|max:255',
            'location' => 'required|string|min:5|max:200',
            'business_sector_id' => 'required|string|exists:business_sectors,id',
            'employee_count_id' => 'required|string|exists:employee_counts,id',
            'creation_year' => 'required|integer|min:1900|max:' . date('Y'),
            'company_phone' => 'required|string|min:10|max:20',
            'business_description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Le nom de l\'entreprise est obligatoire',
            'company_name.min' => 'Le nom de l\'entreprise doit contenir au moins 2 caractères',
            'company_email.required' => 'L\'email de l\'entreprise est obligatoire',
            'company_email.email' => 'L\'email de l\'entreprise doit être valide',
            'company_email.unique' => 'Cette adresse email d\'entreprise est déjà utilisée',
            'location.required' => 'L\'adresse est obligatoire',
            'location.min' => 'L\'adresse doit contenir au moins 5 caractères',
            'business_sector_id.required' => 'Le secteur d\'activité est obligatoire',
            'business_sector_id.exists' => 'Le secteur d\'activité sélectionné n\'est pas valide',
            'employee_count_id.required' => 'La tranche d\'employés est obligatoire',
            'employee_count_id.exists' => 'La tranche d\'employés sélectionnée n\'est pas valide',
            'creation_year.required' => 'L\'année de création est obligatoire',
            'creation_year.min' => 'L\'année de création ne peut pas être antérieure à 1900',
            'creation_year.max' => 'L\'année de création ne peut pas être dans le futur',
            'company_phone.required' => 'Le téléphone de l\'entreprise est obligatoire',
        ];
    }
}