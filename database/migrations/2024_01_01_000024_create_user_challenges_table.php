<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('challenge_id');
            
            // Participation
            $table->timestamp('joined_at');
            $table->boolean('is_active')->default(true);
            
            // Progression
            $table->integer('current_value')->default(0); // Valeur actuelle
            $table->decimal('progress_percentage', 5, 2)->default(0); // % de progression
            $table->json('progress_data')->nullable(); // Données détaillées de progression
            $table->timestamp('last_updated_at')->nullable();
            
            // Completion
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->integer('completion_rank')->nullable(); // Rang de completion si pertinent
            $table->integer('points_earned')->default(0);
            $table->json('rewards_earned')->nullable(); // Récompenses obtenues
            
            // Statut
            $table->boolean('is_winner')->default(false); // Gagnant du défi
            $table->integer('final_rank')->nullable(); // Classement final
            $table->decimal('final_score', 8, 2)->nullable(); // Score final
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('challenge_id')->references('id')->on('challenges')->onDelete('cascade');
            
            $table->unique(['user_id', 'challenge_id'], 'user_challenges_user_challenge_unique');
            $table->index(['challenge_id', 'is_completed', 'progress_percentage'], 'user_challenges_progress_idx');
            $table->index(['user_id', 'is_active', 'joined_at'], 'user_challenges_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_challenges');
    }
};