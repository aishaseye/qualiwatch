<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('feedback_id');
            $table->string('token', 100);
            $table->string('client_ip', 45)->nullable();
            $table->text('client_user_agent')->nullable();
            $table->string('validation_status', 50);
            $table->integer('satisfaction_rating')->nullable();
            $table->text('comment')->nullable();
            $table->integer('bonus_points_awarded')->default(0);
            $table->timestamp('validated_at');
            $table->timestamps();
            
            $table->foreign('feedback_id')->references('id')->on('feedbacks')->onDelete('cascade');
            $table->index(['token']);
            $table->index(['validated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_logs');
    }
};