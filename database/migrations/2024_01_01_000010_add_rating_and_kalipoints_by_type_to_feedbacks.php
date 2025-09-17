<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            // Note générale du feedback (1-5) pour tous les types
            $table->integer('rating')->default(3)->after('description'); // Note de 1 à 5
            
            // KaliPoints séparés par type de feedback
            $table->integer('positive_kalipoints')->default(0)->after('bonus_kalipoints');
            $table->integer('negative_kalipoints')->default(0)->after('positive_kalipoints');
            $table->integer('suggestion_kalipoints')->default(0)->after('negative_kalipoints');
            
            // Bonus KaliPoints séparés par type
            $table->integer('positive_bonus_kalipoints')->default(0)->after('suggestion_kalipoints');
            $table->integer('negative_bonus_kalipoints')->default(0)->after('positive_bonus_kalipoints');
            $table->integer('suggestion_bonus_kalipoints')->default(0)->after('negative_bonus_kalipoints');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn([
                'rating',
                'positive_kalipoints',
                'negative_kalipoints', 
                'suggestion_kalipoints',
                'positive_bonus_kalipoints',
                'negative_bonus_kalipoints',
                'suggestion_bonus_kalipoints'
            ]);
        });
    }
};