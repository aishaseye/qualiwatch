<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_statistics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('service_id');
            $table->string('period_type')->default('daily'); // daily, weekly, monthly, yearly
            $table->date('period_date');
            
            // Feedbacks du service
            $table->integer('total_feedbacks')->default(0);
            $table->integer('positive_feedbacks')->default(0);
            $table->integer('negative_feedbacks')->default(0);
            $table->integer('suggestions_count')->default(0);
            
            // Performance du service (POURCENTAGES)
            $table->decimal('satisfaction_score', 5, 2)->default(0); // % satisfaction du service
            $table->decimal('positive_feedback_percentage', 5, 2)->default(0); // % feedbacks positifs du service
            $table->decimal('negative_feedback_percentage', 5, 2)->default(0); // % feedbacks négatifs du service
            $table->decimal('suggestions_percentage', 5, 2)->default(0); // % suggestions du service
            $table->decimal('performance_score', 5, 2)->default(0); // Score performance global
            $table->integer('rank_in_company')->default(0); // Classement dans l'entreprise
            
            // Comparaison
            $table->decimal('vs_company_average', 5, 2)->default(0); // % vs moyenne entreprise
            $table->decimal('growth_rate', 5, 2)->default(0); // % croissance vs période précédente
            
            // KaliPoints du service
            $table->integer('total_kalipoints_generated')->default(0);
            $table->decimal('average_kalipoints', 5, 2)->default(0);
            
            // Résolutions
            $table->integer('incidents_resolved')->default(0);
            $table->integer('suggestions_implemented')->default(0);
            $table->decimal('resolution_rate', 5, 2)->default(0);
            
            // Temps de traitement du service
            $table->decimal('average_response_time_hours', 8, 2)->default(0);
            $table->decimal('average_resolution_time_hours', 8, 2)->default(0);
            
            // Employés du service
            $table->integer('active_employees_count')->default(0);
            $table->decimal('average_feedbacks_per_employee', 5, 2)->default(0);
            $table->uuid('top_employee_id')->nullable(); // Meilleur employé de la période
            
            // Validation stats pour ce service
            $table->integer('validations_sent')->default(0);
            $table->integer('validations_completed')->default(0);
            $table->decimal('validation_rate', 5, 2)->default(0);
            $table->decimal('average_validation_rating', 3, 2)->default(0);
            
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('top_employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->index(['company_id', 'service_id', 'period_type', 'period_date'], 'service_stats_lookup_idx');
            $table->unique(['service_id', 'period_type', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_statistics');
    }
};