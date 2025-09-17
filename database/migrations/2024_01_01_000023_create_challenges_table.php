<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->string('title');
            $table->text('description');
            $table->string('icon')->default('target');
            $table->string('color')->default('#3B82F6');
            
            // Type et objectif
            $table->enum('type', ['individual', 'team', 'company'])->default('individual');
            $table->enum('category', ['performance', 'satisfaction', 'speed', 'consistency', 'collaboration']);
            $table->json('objectives'); // Objectifs à atteindre
            $table->integer('target_value'); // Valeur cible
            $table->string('target_unit')->default('points'); // Unité (points, %, nombre, etc.)
            
            // Période
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('duration_type', ['daily', 'weekly', 'monthly', 'custom'])->default('weekly');
            
            // Récompenses
            $table->integer('reward_points')->default(0); // KaliPoints pour completion
            $table->json('reward_badges')->nullable(); // Badges à gagner
            $table->text('reward_description')->nullable(); // Description des récompenses
            
            // Participants
            $table->integer('max_participants')->nullable(); // Limite de participants
            $table->integer('current_participants')->default(0);
            $table->json('participant_criteria')->nullable(); // Critères pour participer
            
            // Statut
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('auto_enroll')->default(false); // Inscription automatique
            
            // Créateur et modération
            $table->uuid('created_by');
            $table->boolean('requires_approval')->default(false);
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['company_id', 'status', 'start_date']);
            $table->index(['type', 'category', 'status']);
        });

        // Insérer quelques défis par défaut
        $this->insertDefaultChallenges();
    }

    private function insertDefaultChallenges()
    {
        // On ne peut pas insérer de défis sans company_id valide
        // Ces défis seront créés lors de l'onboarding des entreprises
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};