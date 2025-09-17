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
        // Modifier la colonne sentiment pour utiliser des valeurs standard
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->enum('sentiment', [
                'positive', 'negative', 'neutral'
            ])->nullable()->default('neutral')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir aux anciennes valeurs
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->enum('sentiment', [
                'content', 'heureux', 'extremement_satisfait',
                'mecontent', 'en_colere', 'laisse_a_desirer',
                'constructif', 'amelioration', 'proposition'
            ])->nullable()->change();
        });
    }
};
