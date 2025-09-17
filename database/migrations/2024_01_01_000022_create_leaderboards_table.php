<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('user_id'); // Employé
            $table->uuid('service_id')->nullable(); // Service de l'employé
            
            // Période et type de classement
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly'])->default('monthly');
            $table->date('period_date'); // Date de la période (ex: 2024-03-01 pour mars 2024)
            $table->enum('metric_type', [
                'satisfaction_score', 'total_feedbacks', 'positive_feedbacks',
                'resolution_time', 'response_time', 'kalipoints_earned',
                'badges_earned', 'consistency_score', 'overall_performance'
            ])->default('satisfaction_score');
            
            // Scores et classement
            $table->decimal('score', 8, 2); // Score calculé pour cette métrique
            $table->integer('rank_overall')->nullable(); // Classement général dans l'entreprise
            $table->integer('rank_in_service')->nullable(); // Classement dans le service
            $table->integer('total_participants')->nullable(); // Total des participants
            
            // Métriques détaillées
            $table->json('detailed_metrics')->nullable(); // Détail des métriques
            $table->decimal('improvement_percentage', 5, 2)->nullable(); // % d'amélioration vs période précédente
            $table->boolean('is_improvement')->default(false); // S'améliore vs période précédente
            
            // Récompenses et reconnaissance
            $table->integer('points_earned')->default(0); // KaliPoints gagnés pour ce classement
            $table->json('badges_eligible')->nullable(); // Badges pour lesquels il est éligible
            $table->boolean('is_winner')->default(false); // Winner de cette période
            $table->enum('podium_position', [1, 2, 3])->nullable(); // Position sur le podium
            
            // Statut
            $table->boolean('is_published')->default(false); // Classement publié
            $table->timestamp('published_at')->nullable();
            $table->timestamp('calculated_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            
            $table->unique(['user_id', 'period_type', 'period_date', 'metric_type'], 'leaderboards_user_period_metric_unique');
            $table->index(['company_id', 'period_type', 'period_date', 'metric_type'], 'leaderboards_company_period_idx');
            $table->index(['service_id', 'period_type', 'period_date'], 'leaderboards_service_period_idx');
            $table->index(['rank_overall', 'period_type', 'period_date'], 'leaderboards_rank_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboards');
    }
};