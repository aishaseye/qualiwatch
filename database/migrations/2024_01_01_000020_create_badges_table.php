<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // "top_performer", "client_favorite", "quick_resolver"
            $table->string('title'); // "Top Performer", "Favori Client", "Résolveur Rapide"
            $table->text('description');
            $table->string('icon')->default('star'); // Icône du badge
            $table->string('color')->default('#F59E0B'); // Couleur du badge
            $table->enum('category', [
                'performance', 'satisfaction', 'speed', 'consistency', 
                'leadership', 'innovation', 'teamwork', 'special'
            ])->default('performance');
            
            // Critères d'obtention
            $table->json('criteria'); // Conditions pour obtenir le badge
            $table->enum('frequency', ['once', 'daily', 'weekly', 'monthly', 'yearly'])->default('once');
            $table->integer('points_reward')->default(0); // KaliPoints bonus
            
            // Rareté et niveau
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary'])->default('common');
            $table->integer('level')->default(1); // Niveau du badge (1-5)
            
            // Configuration
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true); // Visible par tous
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            $table->index(['category', 'rarity', 'level']);
            $table->index(['is_active', 'is_public']);
        });

        // Insérer les badges par défaut
        $this->insertDefaultBadges();
    }

    private function insertDefaultBadges()
    {
        $badges = [
            // Badges de performance
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'top_performer',
                'title' => 'Top Performer',
                'description' => 'Employé avec le meilleur score de satisfaction du mois',
                'icon' => 'trophy',
                'color' => '#FFD700', // Or
                'category' => 'performance',
                'criteria' => json_encode([
                    'type' => 'monthly_ranking',
                    'metric' => 'satisfaction_score',
                    'position' => 1,
                    'min_feedbacks' => 10
                ]),
                'frequency' => 'monthly',
                'points_reward' => 500,
                'rarity' => 'epic',
                'level' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'client_favorite',
                'title' => 'Favori des Clients',
                'description' => 'Plus de 90% de satisfaction client sur 30 feedbacks',
                'icon' => 'heart',
                'color' => '#EF4444', // Rouge
                'category' => 'satisfaction',
                'criteria' => json_encode([
                    'type' => 'satisfaction_rate',
                    'threshold' => 90,
                    'min_feedbacks' => 30,
                    'period' => 'monthly'
                ]),
                'frequency' => 'monthly',
                'points_reward' => 300,
                'rarity' => 'rare',
                'level' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'quick_resolver',
                'title' => 'Résolveur Rapide',
                'description' => 'Résout les incidents en moins de 2 heures en moyenne',
                'icon' => 'lightning-bolt',
                'color' => '#10B981', // Vert
                'category' => 'speed',
                'criteria' => json_encode([
                    'type' => 'avg_resolution_time',
                    'max_hours' => 2,
                    'min_incidents' => 20,
                    'period' => 'monthly'
                ]),
                'frequency' => 'monthly',
                'points_reward' => 250,
                'rarity' => 'uncommon',
                'level' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'consistency_king',
                'title' => 'Roi de la Régularité',
                'description' => 'Performance constante pendant 3 mois consécutifs',
                'icon' => 'trending-up',
                'color' => '#8B5CF6', // Violet
                'category' => 'consistency',
                'criteria' => json_encode([
                    'type' => 'consistency_months',
                    'months' => 3,
                    'min_score' => 80,
                    'variance_max' => 10
                ]),
                'frequency' => 'once',
                'points_reward' => 800,
                'rarity' => 'legendary',
                'level' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'feedback_magnet',
                'title' => 'Aimant à Feedback',
                'description' => 'A reçu plus de 100 feedbacks positifs',
                'icon' => 'chat-alt-2',
                'color' => '#F59E0B', // Orange
                'category' => 'performance',
                'criteria' => json_encode([
                    'type' => 'total_positive_feedbacks',
                    'threshold' => 100
                ]),
                'frequency' => 'once',
                'points_reward' => 400,
                'rarity' => 'rare',
                'level' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'problem_solver',
                'title' => 'Solutionneur',
                'description' => '100% de résolution d\'incidents sur le mois',
                'icon' => 'puzzle',
                'color' => '#06B6D4', // Cyan
                'category' => 'performance',
                'criteria' => json_encode([
                    'type' => 'resolution_rate',
                    'threshold' => 100,
                    'min_incidents' => 5,
                    'period' => 'monthly'
                ]),
                'frequency' => 'monthly',
                'points_reward' => 200,
                'rarity' => 'uncommon',
                'level' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'first_responder',
                'title' => 'Premier Répondeur',
                'description' => 'Répond aux feedbacks en moins de 30 minutes',
                'icon' => 'clock',
                'color' => '#F97316', // Orange foncé
                'category' => 'speed',
                'criteria' => json_encode([
                    'type' => 'avg_response_time',
                    'max_minutes' => 30,
                    'min_feedbacks' => 15,
                    'period' => 'monthly'
                ]),
                'frequency' => 'monthly',
                'points_reward' => 150,
                'rarity' => 'common',
                'level' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'team_player',
                'title' => 'Esprit d\'Équipe',
                'description' => 'Service avec la meilleure collaboration',
                'icon' => 'user-group',
                'color' => '#14B8A6', // Teal
                'category' => 'teamwork',
                'criteria' => json_encode([
                    'type' => 'service_ranking',
                    'metric' => 'team_score',
                    'position' => 1,
                    'period' => 'monthly'
                ]),
                'frequency' => 'monthly',
                'points_reward' => 300,
                'rarity' => 'rare',
                'level' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('badges')->insert($badges);
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};