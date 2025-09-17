<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feedback_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('feedback_id');
            $table->enum('severity', ['low', 'medium', 'high', 'critical', 'catastrophic'])->default('medium');
            $table->enum('alert_type', ['negative_sentiment', 'critical_keywords', 'low_rating', 'multiple_issues', 'vip_client']);
            $table->json('detected_keywords')->nullable(); // Mots-clés détectés
            $table->float('sentiment_score')->nullable(); // Score de sentiment (-1 à 1)
            $table->text('alert_reason'); // Raison de l'alerte
            $table->enum('status', ['new', 'acknowledged', 'in_progress', 'resolved', 'dismissed'])->default('new');
            $table->uuid('acknowledged_by')->nullable(); // Utilisateur qui a pris en charge
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->boolean('is_escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('feedback_id')->references('id')->on('feedbacks')->onDelete('cascade');
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['company_id', 'status']);
            $table->index(['severity', 'status']);
            $table->index(['alert_type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_alerts');
    }
};
