<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Reward;

class RewardsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer quelques entreprises pour créer des récompenses
        $companies = Company::take(3)->get();

        foreach ($companies as $company) {
            // Créer des récompenses variées pour chaque entreprise
            $rewards = [
                [
                    'name' => 'Café Gratuit',
                    'description' => 'Un café offert à votre prochaine visite',
                    'type' => 'gift',
                    'kalipoints_cost' => 50,
                    'details' => [
                        'size' => 'medium',
                        'validity_days' => 30,
                        'locations' => 'Toutes nos succursales'
                    ],
                    'stock' => 100,
                ],
                [
                    'name' => 'Remise 10%',
                    'description' => 'Remise de 10% sur votre prochaine commande',
                    'type' => 'discount',
                    'kalipoints_cost' => 100,
                    'details' => [
                        'percentage' => 10,
                        'max_discount' => 20,
                        'validity_days' => 60,
                        'minimum_purchase' => 50
                    ],
                    'stock' => 50,
                ],
                [
                    'name' => 'Service Prioritaire',
                    'description' => 'Accès au service client prioritaire pendant 3 mois',
                    'type' => 'service',
                    'kalipoints_cost' => 200,
                    'details' => [
                        'duration_months' => 3,
                        'benefits' => [
                            'File d\'attente prioritaire',
                            'Conseiller dédié',
                            'Support téléphonique étendu'
                        ]
                    ],
                    'stock' => 20,
                ],
                [
                    'name' => 'Visite Guidée Exclusive',
                    'description' => 'Visite exclusive des coulisses de notre entreprise',
                    'type' => 'experience',
                    'kalipoints_cost' => 300,
                    'details' => [
                        'duration_hours' => 2,
                        'max_participants' => 4,
                        'includes' => [
                            'Visite guidée',
                            'Rencontre avec l\'équipe',
                            'Collation offerte'
                        ]
                    ],
                    'stock' => 10,
                ],
                [
                    'name' => 'E-book Offert',
                    'description' => 'Téléchargement gratuit de notre guide exclusif',
                    'type' => 'digital',
                    'kalipoints_cost' => 75,
                    'details' => [
                        'format' => 'PDF',
                        'pages' => 50,
                        'language' => 'Français',
                        'download_link' => 'https://example.com/download'
                    ],
                    'stock' => null, // Illimité
                ],
            ];

            foreach ($rewards as $rewardData) {
                Reward::create([
                    'company_id' => $company->id,
                    'name' => $rewardData['name'],
                    'description' => $rewardData['description'],
                    'type' => $rewardData['type'],
                    'kalipoints_cost' => $rewardData['kalipoints_cost'],
                    'details' => $rewardData['details'],
                    'stock' => $rewardData['stock'],
                    'is_active' => true,
                    'valid_from' => now(),
                    'valid_until' => now()->addMonths(6),
                ]);
            }
        }
    }
}
