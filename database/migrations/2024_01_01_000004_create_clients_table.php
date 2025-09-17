<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->integer('total_feedbacks')->default(0);
            $table->integer('total_kalipoints')->default(0);
            $table->integer('bonus_kalipoints')->default(0);
            $table->enum('status', ['normal', 'vip', 'blocked'])->default('normal');
            $table->timestamp('first_feedback_at')->nullable();
            $table->timestamp('last_feedback_at')->nullable();
            $table->timestamps();
            
            $table->index(['email', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};