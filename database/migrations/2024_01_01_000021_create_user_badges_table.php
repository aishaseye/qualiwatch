<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // Employé qui a obtenu le badge
            $table->uuid('badge_id');
            $table->uuid('company_id');
            
            // Contexte d'obtention
            $table->date('earned_date'); // Date d'obtention
            $table->string('period')->nullable(); // "2024-03", "2024-W12", etc.
            $table->json('achievement_data')->nullable(); // Données sur l'exploit
            $table->integer('points_earned')->default(0); // KaliPoints gagnés
            
            // Métriques d'obtention
            $table->decimal('achievement_score', 5, 2)->nullable(); // Score qui a permis l'obtention
            $table->integer('rank_position')->nullable(); // Position dans le classement si applicable
            $table->json('metrics_snapshot')->nullable(); // Snapshot des métriques au moment de l'obtention
            
            // Reconnaissance
            $table->boolean('is_featured')->default(false); // Mis en avant
            $table->boolean('is_announced')->default(false); // Annoncé publiquement
            $table->timestamp('announced_at')->nullable();
            $table->uuid('awarded_by')->nullable(); // Qui a attribué le badge (si manuel)
            $table->text('award_message')->nullable(); // Message personnalisé
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('badge_id')->references('id')->on('badges')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('awarded_by')->references('id')->on('users')->onDelete('set null');
            
            $table->unique(['user_id', 'badge_id', 'period']); // Éviter les doublons pour badges périodiques
            $table->index(['company_id', 'earned_date']);
            $table->index(['user_id', 'earned_date']);
            $table->index(['badge_id', 'earned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
    }
};