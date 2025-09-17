<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_statistics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('service_id');
            $table->uuid('employee_id');
            $table->string('period_type')->default('daily'); // daily, weekly, monthly, yearly
            $table->date('period_date');
            
            // Feedbacks de l'employé
            $table->integer('total_feedbacks')->default(0);
            $table->integer('positive_feedbacks')->default(0);
            $table->integer('negative_feedbacks')->default(0);
            $table->integer('suggestions_received')->default(0);
            
            // Performance individuelle (POURCENTAGES)
            $table->decimal('satisfaction_score', 5, 2)->default(0); // % satisfaction individuelle
            $table->decimal('positive_feedback_percentage', 5, 2)->default(0); // % feedbacks positifs de l'employé
            $table->decimal('negative_feedback_percentage', 5, 2)->default(0); // % feedbacks négatifs de l'employé
            $table->decimal('suggestions_percentage', 5, 2)->default(0); // % suggestions concernant l'employé
            $table->decimal('incident_resolution_percentage', 5, 2)->default(0); // % incidents résolus par l'employé
            $table->decimal('performance_score', 5, 2)->default(0); // Score performance calculé
            $table->integer('rank_in_service')->default(0); // Classement dans le service
            $table->integer('rank_in_company')->default(0); // Classement dans l'entreprise
            
            // Comparaisons
            $table->decimal('vs_service_average', 5, 2)->default(0); // % vs moyenne service
            $table->decimal('vs_company_average', 5, 2)->default(0); // % vs moyenne entreprise
            $table->decimal('growth_rate', 5, 2)->default(0); // % croissance vs période précédente
            
            // KaliPoints générés par l'employé
            $table->integer('total_kalipoints_generated')->default(0);
            $table->decimal('average_kalipoints_per_feedback', 5, 2)->default(0);
            
            // Résolutions impliquant cet employé
            $table->integer('incidents_assigned')->default(0);
            $table->integer('incidents_resolved')->default(0);
            $table->integer('suggestions_about_employee')->default(0);
            
            // Temps de traitement
            $table->decimal('average_response_time_hours', 8, 2)->default(0);
            $table->decimal('average_resolution_time_hours', 8, 2)->default(0);
            
            // Badges et récompenses de la période
            $table->json('badges_earned')->nullable(); // ["top_performer", "quick_resolver", "client_favorite"]
            $table->boolean('employee_of_period')->default(false); // Employé de la période
            
            // Analyses comportementales
            $table->decimal('consistency_score', 5, 2)->default(0); // Régularité des performances
            $table->decimal('improvement_trend', 5, 2)->default(0); // Tendance d'amélioration
            $table->json('strengths')->nullable(); // ["communication", "problem_solving"]
            $table->json('areas_for_improvement')->nullable(); // ["response_time", "follow_up"]
            
            // Validation stats pour cet employé
            $table->integer('validations_related')->default(0); // Validations liées à ses feedbacks
            $table->decimal('validation_satisfaction_avg', 3, 2)->default(0);
            
            // Recommandations automatiques
            $table->json('training_recommendations')->nullable(); // Formations suggérées
            $table->json('recognition_suggestions')->nullable(); // Suggestions de reconnaissance
            
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->index(['company_id', 'employee_id', 'period_type', 'period_date'], 'emp_stats_lookup_idx');
            $table->index(['service_id', 'period_type', 'period_date']);
            $table->unique(['employee_id', 'period_type', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_statistics');
    }
};