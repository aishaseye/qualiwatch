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
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('client_id')->nullable();
            $table->string('session_id');
            $table->string('user_identifier')->nullable(); // email, phone, or anonymous ID
            $table->enum('status', ['active', 'resolved', 'transferred', 'closed'])->default('active');
            $table->enum('context', ['feedback', 'support', 'info', 'complaint', 'suggestion'])->default('support');
            $table->json('metadata')->nullable(); // Context data
            $table->timestamp('last_activity_at');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->index(['company_id', 'status']);
            $table->index(['session_id']);
            $table->index(['last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_conversations');
    }
};
