<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Service;
use App\Models\Company;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    private $company;
    private $importedCount = 0;
    private $errors = [];

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function model(array $row)
    {
        try {
            // Trouver ou créer le service
            $service = null;
            if (!empty($row['service_name'])) {
                $service = $this->company->services()
                    ->where('name', $row['service_name'])
                    ->first();
                
                if (!$service) {
                    // Créer le service s'il n'existe pas
                    $service = $this->company->services()->create([
                        'name' => $row['service_name'],
                        'description' => 'Service créé automatiquement lors de l\'import',
                        'color' => '#3B82F6',
                        'icon' => 'briefcase',
                        'is_active' => true,
                    ]);
                }
            }

            // Vérifier si l'employé existe déjà (par email)
            $existingEmployee = $this->company->employees()
                ->where('email', $row['email'])
                ->first();

            if ($existingEmployee) {
                $this->errors[] = "Employé avec l'email {$row['email']} existe déjà";
                return null;
            }

            // Créer l'employé
            $employee = $this->company->employees()->create([
                'service_id' => $service?->id,
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'] ?? null,
                'position' => $row['position'] ?? null,
                'hire_date' => !empty($row['hire_date']) ? \Carbon\Carbon::parse($row['hire_date']) : null,
                'is_active' => true,
            ]);

            $this->importedCount++;

            return $employee;

        } catch (\Exception $e) {
            $this->errors[] = "Erreur ligne " . ($this->importedCount + 2) . ": " . $e->getMessage();
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'service_name' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire',
            'last_name.required' => 'Le nom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'hire_date.date' => 'La date d\'embauche doit être une date valide',
        ];
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = $e->getMessage();
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Ligne {$failure->row()}: " . implode(', ', $failure->errors());
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}