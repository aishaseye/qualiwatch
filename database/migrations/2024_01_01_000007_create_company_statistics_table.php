<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_statistics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('period_type')->default('daily'); // daily, weekly, monthly, yearly
            $table->date('period_date'); // Date de la période
            
            // Feedbacks généraux
            $table->integer('total_feedbacks')->default(0);
            $table->integer('new_feedbacks')->default(0);
            $table->integer('positive_feedbacks')->default(0);
            $table->integer('negative_feedbacks')->default(0);
            $table->integer('suggestions_count')->default(0);
            
            // Scores et taux (POURCENTAGES)
            $table->decimal('satisfaction_score', 5, 2)->default(0); // % satisfaction
            $table->decimal('positive_feedback_percentage', 5, 2)->default(0); // % feedbacks positifs
            $table->decimal('negative_feedback_percentage', 5, 2)->default(0); // % feedbacks négatifs  
            $table->decimal('suggestions_percentage', 5, 2)->default(0); // % suggestions
            $table->decimal('growth_rate', 5, 2)->default(0); // % croissance vs période précédente
            $table->decimal('resolution_rate', 5, 2)->default(0); // % incidents résolus
            $table->decimal('implementation_rate', 5, 2)->default(0); // % suggestions implémentées
            $table->decimal('incident_resolution_percentage', 5, 2)->default(0); // % incidents résolus sur total incidents
            $table->decimal('suggestion_implementation_percentage', 5, 2)->default(0); // % suggestions implémentées sur total suggestions
            
            // Statistiques clients
            $table->integer('total_clients')->default(0);
            $table->integer('new_clients')->default(0);
            $table->integer('recurring_clients')->default(0);
            $table->decimal('client_retention_rate', 5, 2)->default(0);
            $table->decimal('average_feedbacks_per_client', 5, 2)->default(0);
            
            // KaliPoints
            $table->integer('total_kalipoints_distributed')->default(0);
            $table->integer('bonus_kalipoints_distributed')->default(0);
            $table->decimal('average_kalipoints_per_feedback', 5, 2)->default(0);
            
            // Validation client
            $table->integer('validation_links_sent')->default(0);
            $table->integer('validations_completed')->default(0);
            $table->decimal('validation_completion_rate', 5, 2)->default(0);
            $table->decimal('average_satisfaction_rating', 3, 2)->default(0);
            
            // Temps de traitement
            $table->decimal('average_response_time_hours', 8, 2)->default(0); // Temps moyen de traitement
            $table->decimal('average_resolution_time_hours', 8, 2)->default(0); // Temps moyen de résolution
            
            // Heures de pointe (JSON)
            $table->json('peak_hours')->nullable(); // {"08": 5, "14": 12, "20": 8}
            $table->json('peak_days')->nullable(); // {"monday": 15, "friday": 25}
            
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'period_type', 'period_date']);
            $table->unique(['company_id', 'period_type', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_statistics');
    }
};