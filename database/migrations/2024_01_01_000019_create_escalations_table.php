<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escalations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('feedback_id');
            $table->uuid('sla_rule_id');
            $table->integer('escalation_level'); // 1, 2, 3
            $table->string('trigger_reason'); // "sla_breach", "critical_rating", "multiple_incidents", "urgent_sentiment"
            $table->timestamp('escalated_at');
            $table->json('notified_users')->nullable(); // IDs des utilisateurs notifiés
            $table->json('notification_channels_used')->nullable(); // ["email", "sms"]
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            
            $table->foreign('feedback_id')->references('id')->on('feedbacks')->onDelete('cascade');
            $table->foreign('sla_rule_id')->references('id')->on('sla_rules')->onDelete('cascade');
            $table->index(['feedback_id', 'escalation_level']);
            $table->index(['escalated_at', 'is_resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalations');
    }
};