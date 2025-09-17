<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\BusinessSector;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // Créer le gérant de test
        $manager = User::create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'manager@qualywatch-demo.com',
            'phone' => '+33123456789',
            'password' => Hash::make('password123'),
            'role' => 'manager',
        ]);

        // Récupérer les secteurs d'activité
        $restaurationSector = BusinessSector::getByCode('restauration');
        $hotellerieSector = BusinessSector::getByCode('hotellerie');
        $commerceSector = BusinessSector::getByCode('commerce_retail');

        // Créer l'entreprise de test
        Company::create([
            'manager_id' => $manager->id,
            'name' => 'Restaurant Le Gourmet',
            'email' => 'contact@legourmet.com',
            'location' => 'Paris, France',
            'business_sector_id' => $restaurationSector->id,
            'employees_count' => 25,
            'creation_year' => 2018,
            'phone' => '+33145678901',
            'business_description' => 'Restaurant gastronomique proposant une cuisine française raffinée',
        ]);

        // Créer une deuxième entreprise de test
        $manager2 = User::create([
            'first_name' => 'Marie',
            'last_name' => 'Martin',
            'email' => 'marie@hotel-paris.com',
            'phone' => '+33987654321',
            'password' => Hash::make('password123'),
            'role' => 'manager',
        ]);

        Company::create([
            'manager_id' => $manager2->id,
            'name' => 'Hôtel Paris Étoile',
            'email' => 'contact@hotel-paris-etoile.com',
            'location' => 'Paris 17ème, France',
            'business_sector_id' => $hotellerieSector->id,
            'employees_count' => 45,
            'creation_year' => 2015,
            'phone' => '+33156789012',
            'business_description' => 'Hôtel 4 étoiles au cœur de Paris près des Champs-Élysées',
        ]);

        // Créer une troisième entreprise (Commerce)
        $manager3 = User::create([
            'first_name' => 'Pierre',
            'last_name' => 'Durand',
            'email' => 'pierre@tech-store.com',
            'phone' => '+33147258369',
            'password' => Hash::make('password123'),
            'role' => 'manager',
        ]);

        Company::create([
            'manager_id' => $manager3->id,
            'name' => 'TechStore Pro',
            'email' => 'contact@techstore-pro.com',
            'location' => 'Lyon, France',
            'business_sector_id' => $commerceSector->id,
            'employees_count' => 15,
            'creation_year' => 2020,
            'phone' => '+33478963214',
            'business_description' => 'Magasin spécialisé en équipements informatiques et électroniques',
        ]);
    }
}