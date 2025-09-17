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
        // Supprimer icon et color de positive_sentiments
        Schema::table('positive_sentiments', function (Blueprint $table) {
            $table->dropColumn(['color', 'icon']);
        });
        
        // Supprimer icon et color de negative_sentiments
        Schema::table('negative_sentiments', function (Blueprint $table) {
            $table->dropColumn(['color', 'icon']);
        });
        
        // Supprimer icon et color de suggestion_sentiments
        Schema::table('suggestion_sentiments', function (Blueprint $table) {
            $table->dropColumn(['color', 'icon']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre les colonnes si nécessaire
        Schema::table('positive_sentiments', function (Blueprint $table) {
            $table->string('color', 7)->default('#10B981');
            $table->string('icon', 50)->default('smile');
        });
        
        Schema::table('negative_sentiments', function (Blueprint $table) {
            $table->string('color', 7)->default('#EF4444');
            $table->string('icon', 50)->default('frown');
        });
        
        Schema::table('suggestion_sentiments', function (Blueprint $table) {
            $table->string('color', 7)->default('#F59E0B');
            $table->string('icon', 50)->default('lightbulb');
        });
    }
};