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
        Schema::table('feedbacks', function (Blueprint $table) {
            // Supprimer l'ancien champ sentiment_id s'il existe
            if (Schema::hasColumn('feedbacks', 'sentiment_id')) {
                $table->dropColumn('sentiment_id');
            }
            
            // Ajouter les nouveaux champs pour les différents types de sentiment
            $table->unsignedBigInteger('positive_sentiment_id')->nullable()->after('feedback_type_id');
            $table->unsignedBigInteger('negative_sentiment_id')->nullable()->after('positive_sentiment_id');
            $table->unsignedBigInteger('suggestion_sentiment_id')->nullable()->after('negative_sentiment_id');
            
            // Ajouter les clés étrangères
            $table->foreign('positive_sentiment_id')->references('id')->on('positive_sentiments')->onDelete('set null');
            $table->foreign('negative_sentiment_id')->references('id')->on('negative_sentiments')->onDelete('set null');
            $table->foreign('suggestion_sentiment_id')->references('id')->on('suggestion_sentiments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            // Supprimer les clés étrangères
            $table->dropForeign(['positive_sentiment_id']);
            $table->dropForeign(['negative_sentiment_id']);
            $table->dropForeign(['suggestion_sentiment_id']);
            
            // Supprimer les colonnes
            $table->dropColumn(['positive_sentiment_id', 'negative_sentiment_id', 'suggestion_sentiment_id']);
            
            // Remettre l'ancien champ si nécessaire
            $table->unsignedBigInteger('sentiment_id')->nullable();
        });
    }
};