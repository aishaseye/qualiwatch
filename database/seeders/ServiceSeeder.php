<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // Services par défaut selon le secteur d'activité  
            $sectorCode = $company->businessSector?->code ?? 'autres';
            $services = $this->getServicesForSector($sectorCode);
            
            foreach ($services as $serviceData) {
                Service::create([
                    'company_id' => $company->id,
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'],
                    'color' => $serviceData['color'],
                    'icon' => $serviceData['icon'],
                    'is_active' => true,
                ]);
            }
        }
    }

    private function getServicesForSector($sector): array
    {
        return match($sector) {
            'restauration' => [
                ['name' => 'Service en salle', 'description' => 'Accueil et service des clients en salle', 'color' => '#3B82F6', 'icon' => 'utensils'],
                ['name' => 'Cuisine', 'description' => 'Préparation et qualité des plats', 'color' => '#EF4444', 'icon' => 'fire'],
                ['name' => 'Réception', 'description' => 'Accueil téléphonique et réservations', 'color' => '#10B981', 'icon' => 'phone'],
                ['name' => 'Bar', 'description' => 'Service des boissons et cocktails', 'color' => '#F59E0B', 'icon' => 'wine-glass'],
                ['name' => 'Livraison', 'description' => 'Service de livraison à domicile', 'color' => '#8B5CF6', 'icon' => 'truck'],
            ],
            'hotellerie' => [
                ['name' => 'Réception', 'description' => 'Accueil et check-in/check-out', 'color' => '#3B82F6', 'icon' => 'key'],
                ['name' => 'Conciergerie', 'description' => 'Services et informations clients', 'color' => '#10B981', 'icon' => 'bell'],
                ['name' => 'Housekeeping', 'description' => 'Ménage et entretien des chambres', 'color' => '#F59E0B', 'icon' => 'broom'],
                ['name' => 'Restaurant', 'description' => 'Service de restauration', 'color' => '#EF4444', 'icon' => 'utensils'],
                ['name' => 'Spa/Wellness', 'description' => 'Services bien-être et détente', 'color' => '#8B5CF6', 'icon' => 'spa'],
                ['name' => 'Maintenance', 'description' => 'Entretien technique de l\'hôtel', 'color' => '#6B7280', 'icon' => 'tools'],
            ],
            'commerce_retail' => [
                ['name' => 'Vente', 'description' => 'Conseil et vente produits', 'color' => '#3B82F6', 'icon' => 'shopping-cart'],
                ['name' => 'Service Client', 'description' => 'Support et assistance clientèle', 'color' => '#10B981', 'icon' => 'headphones'],
                ['name' => 'Caisse', 'description' => 'Encaissement et facturation', 'color' => '#F59E0B', 'icon' => 'credit-card'],
                ['name' => 'Service Technique', 'description' => 'Installation et réparation', 'color' => '#EF4444', 'icon' => 'wrench'],
                ['name' => 'Logistique', 'description' => 'Gestion des stocks et livraisons', 'color' => '#8B5CF6', 'icon' => 'box'],
            ],
            default => [
                ['name' => 'Réception', 'description' => 'Accueil clientèle', 'color' => '#3B82F6', 'icon' => 'phone'],
                ['name' => 'Service Client', 'description' => 'Support et assistance', 'color' => '#10B981', 'icon' => 'headphones'],
                ['name' => 'Administration', 'description' => 'Gestion administrative', 'color' => '#F59E0B', 'icon' => 'briefcase'],
                ['name' => 'Direction', 'description' => 'Direction et management', 'color' => '#8B5CF6', 'icon' => 'user-tie'],
            ]
        };
    }
}