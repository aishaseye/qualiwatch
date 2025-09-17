<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('client_id')->nullable();
            $table->uuid('employee_id')->nullable();
            $table->uuid('service_id')->nullable();
            
            // Type et statut du feedback
            $table->enum('type', ['appreciation', 'incident', 'suggestion']);
            $table->enum('status', [
                'new', 'in_progress', 'treated', 'resolved', 
                'partially_resolved', 'not_resolved', 
                'implemented', 'partially_implemented', 
                'rejected', 'archived'
            ])->default('new');
            
            // Contenu du feedback
            $table->string('title');
            $table->text('description');
            $table->integer('kalipoints')->default(0);
            $table->integer('bonus_kalipoints')->default(0);
            $table->string('attachment_url')->nullable();
            
            // Système de validation client pour feedbacks négatifs
            $table->string('validation_token', 100)->nullable();
            $table->timestamp('validation_expires_at')->nullable();
            $table->boolean('client_validated')->default(false);
            $table->enum('client_validation_status', [
                'satisfied', 'partially_satisfied', 'not_satisfied'
            ])->nullable();
            $table->text('client_validation_comment')->nullable();
            $table->integer('client_satisfaction_rating')->nullable(); // Note 1-5 étoiles
            $table->timestamp('validation_reminded_at')->nullable();
            
            // Traitement par l'admin
            $table->text('admin_comments')->nullable();
            $table->text('admin_resolution_description')->nullable();
            $table->uuid('treated_by_user_id')->nullable();
            $table->timestamp('treated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign('treated_by_user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['validation_token']);
            $table->index(['type', 'status']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};