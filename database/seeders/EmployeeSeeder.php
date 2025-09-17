<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Service;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $services = Service::where('company_id', $company->id)->get();
            
            // Employés pour Restaurant Le Gourmet
            if (str_contains($company->name, 'Restaurant')) {
                $employees = [
                    ['first_name' => 'Antoine', 'last_name' => 'Dubois', 'email' => 'antoine@legourmet.com', 'phone' => '+33601020304', 'position' => 'Serveur', 'service_name' => 'Service en salle'],
                    ['first_name' => 'Sophie', 'last_name' => 'Moreau', 'email' => 'sophie@legourmet.com', 'phone' => '+33601020305', 'position' => 'Chef Cuisine', 'service_name' => 'Cuisine'],
                    ['first_name' => 'Lucas', 'last_name' => 'Bernard', 'email' => 'lucas@legourmet.com', 'phone' => '+33601020306', 'position' => 'Sommelier', 'service_name' => 'Bar'],
                    ['first_name' => 'Emma', 'last_name' => 'Petit', 'email' => 'emma@legourmet.com', 'phone' => '+33601020307', 'position' => 'Hôtesse', 'service_name' => 'Réception'],
                    ['first_name' => 'Thomas', 'last_name' => 'Roux', 'email' => 'thomas@legourmet.com', 'phone' => '+33601020308', 'position' => 'Commis', 'service_name' => 'Cuisine'],
                    ['first_name' => 'Céline', 'last_name' => 'Leroy', 'email' => 'celine@legourmet.com', 'phone' => '+33601020309', 'position' => 'Serveuse', 'service_name' => 'Service en salle'],
                ];
            }
            // Employés pour Hôtel Paris Étoile
            elseif (str_contains($company->name, 'Hôtel')) {
                $employees = [
                    ['first_name' => 'Julien', 'last_name' => 'Garnier', 'email' => 'julien@hotel-paris.com', 'phone' => '+33602030405', 'position' => 'Réceptionniste', 'service_name' => 'Réception'],
                    ['first_name' => 'Marina', 'last_name' => 'Blanc', 'email' => 'marina@hotel-paris.com', 'phone' => '+33602030406', 'position' => 'Concierge', 'service_name' => 'Conciergerie'],
                    ['first_name' => 'David', 'last_name' => 'Simon', 'email' => 'david@hotel-paris.com', 'phone' => '+33602030407', 'position' => 'Gouvernante', 'service_name' => 'Housekeeping'],
                    ['first_name' => 'Amélie', 'last_name' => 'Michel', 'email' => 'amelie@hotel-paris.com', 'phone' => '+33602030408', 'position' => 'Chef Restaurant', 'service_name' => 'Restaurant'],
                    ['first_name' => 'Nicolas', 'last_name' => 'Laurent', 'email' => 'nicolas@hotel-paris.com', 'phone' => '+33602030409', 'position' => 'Technicien', 'service_name' => 'Maintenance'],
                    ['first_name' => 'Sarah', 'last_name' => 'Lefebvre', 'email' => 'sarah@hotel-paris.com', 'phone' => '+33602030410', 'position' => 'Spa Manager', 'service_name' => 'Spa/Wellness'],
                ];
            }
            // Employés pour TechStore Pro
            else {
                $employees = [
                    ['first_name' => 'Kevin', 'last_name' => 'Martinez', 'email' => 'kevin@techstore-pro.com', 'phone' => '+33603040506', 'position' => 'Vendeur', 'service_name' => 'Vente'],
                    ['first_name' => 'Jessica', 'last_name' => 'Garcia', 'email' => 'jessica@techstore-pro.com', 'phone' => '+33603040507', 'position' => 'Conseillère', 'service_name' => 'Service Client'],
                    ['first_name' => 'Maxime', 'last_name' => 'Rodriguez', 'email' => 'maxime@techstore-pro.com', 'phone' => '+33603040508', 'position' => 'Technicien', 'service_name' => 'Service Technique'],
                    ['first_name' => 'Camille', 'last_name' => 'Lopez', 'email' => 'camille@techstore-pro.com', 'phone' => '+33603040509', 'position' => 'Caissière', 'service_name' => 'Caisse'],
                    ['first_name' => 'Alexandre', 'last_name' => 'Wilson', 'email' => 'alex@techstore-pro.com', 'phone' => '+33603040510', 'position' => 'Responsable Logistique', 'service_name' => 'Logistique'],
                ];
            }

            foreach ($employees as $employeeData) {
                // Trouver le service correspondant au département
                $service = $services->where('name', $employeeData['service_name'])->first();
                
                Employee::create([
                    'company_id' => $company->id,
                    'service_id' => $service?->id,
                    'first_name' => $employeeData['first_name'],
                    'last_name' => $employeeData['last_name'],
                    'email' => $employeeData['email'],
                    'phone' => $employeeData['phone'],
                    'position' => $employeeData['position'],
                    'hire_date' => fake()->dateTimeBetween('-2 years', 'now'),
                    'is_active' => true,
                ]);
            }
        }
    }
}