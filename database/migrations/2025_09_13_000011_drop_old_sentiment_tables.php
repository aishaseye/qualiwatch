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
        // Supprimer les anciennes tables
        Schema::dropIfExists('feedback_type_sentiments');
        Schema::dropIfExists('sentiments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recréer la table sentiments si on veut revenir en arrière
        Schema::create('sentiments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('name');
        });
    }
};