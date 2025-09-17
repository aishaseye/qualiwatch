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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable(); // null = global template
            $table->string('name');
            $table->enum('type', ['feedback', 'reward', 'escalation', 'system', 'promotion', 'milestone']);
            $table->enum('channel', ['email', 'sms', 'push', 'in_app', 'webhook']);
            $table->string('subject')->nullable(); // for email/push
            $table->text('title_template');
            $table->text('message_template');
            $table->json('variables')->nullable(); // Available template variables
            $table->json('settings')->nullable(); // Channel-specific settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'type', 'channel', 'name']);
            $table->index(['type', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
