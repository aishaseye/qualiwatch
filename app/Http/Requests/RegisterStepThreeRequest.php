<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStepThreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->tokenCan('registration');
    }

    public function rules(): array
    {
        return [
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'manager_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'company_logo.image' => 'Le logo de l\'entreprise doit être une image',
            'company_logo.mimes' => 'Le logo doit être au format JPEG, PNG, JPG, GIF ou SVG',
            'company_logo.max' => 'Le logo ne doit pas dépasser 2MB',
            'manager_photo.image' => 'La photo du gérant doit être une image',
            'manager_photo.mimes' => 'La photo doit être au format JPEG, PNG, JPG, GIF ou SVG',
            'manager_photo.max' => 'La photo ne doit pas dépasser 2MB',
        ];
    }
}