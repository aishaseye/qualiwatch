<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FeedbackType;
use Illuminate\Support\Str;

class FeedbackTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Appréciation',
                'label' => 'appreciation',
                'description' => 'Commentaire positif sur le service reçu',
                'color' => '#10B981', // Vert
                'icon' => 'thumb-up',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Réclamation',
                'label' => 'complaint',
                'description' => 'Plainte concernant un problème ou dysfonctionnement',
                'color' => '#EF4444', // Rouge
                'icon' => 'exclamation-triangle',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Suggestion',
                'label' => 'suggestion',
                'description' => 'Proposition d\'amélioration du service',
                'color' => '#3B82F6', // Bleu
                'icon' => 'lightbulb',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Question',
                'label' => 'question',
                'description' => 'Demande d\'information ou de clarification',
                'color' => '#8B5CF6', // Violet
                'icon' => 'question-mark-circle',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Incident',
                'label' => 'incident',
                'description' => 'Signalement d\'un problème technique ou sécuritaire',
                'color' => '#F59E0B', // Orange
                'icon' => 'exclamation',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            FeedbackType::firstOrCreate(
                ['label' => $type['label']], // Critère de recherche
                $type // Données à créer si pas trouvé
            );
        }
    }
}
