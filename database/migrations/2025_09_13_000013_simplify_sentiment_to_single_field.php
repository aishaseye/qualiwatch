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
            // Supprimer les 3 champs séparés s'ils existent
            if (Schema::hasColumn('feedbacks', 'positive_sentiment_id')) {
                $table->dropForeign(['positive_sentiment_id']);
                $table->dropColumn('positive_sentiment_id');
            }
            if (Schema::hasColumn('feedbacks', 'negative_sentiment_id')) {
                $table->dropForeign(['negative_sentiment_id']);
                $table->dropColumn('negative_sentiment_id');
            }
            if (Schema::hasColumn('feedbacks', 'suggestion_sentiment_id')) {
                $table->dropForeign(['suggestion_sentiment_id']);
                $table->dropColumn('suggestion_sentiment_id');
            }
            
            // Ajouter un seul champ sentiment_id
            $table->unsignedBigInteger('sentiment_id')->nullable()->after('feedback_type_id');
            $table->string('sentiment_type', 20)->nullable()->after('sentiment_id'); // 'positive', 'negative', 'suggestion'
            
            // Index pour performance
            $table->index(['sentiment_id', 'sentiment_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            // Supprimer les nouveaux champs
            $table->dropIndex(['sentiment_id', 'sentiment_type']);
            $table->dropColumn(['sentiment_id', 'sentiment_type']);
            
            // Remettre les 3 champs séparés
            $table->unsignedBigInteger('positive_sentiment_id')->nullable();
            $table->unsignedBigInteger('negative_sentiment_id')->nullable();
            $table->unsignedBigInteger('suggestion_sentiment_id')->nullable();
        });
    }
};