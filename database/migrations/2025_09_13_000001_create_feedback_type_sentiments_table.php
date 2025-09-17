<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Créer la table intermédiaire feedback_type_sentiments
        Schema::create('feedback_type_sentiments', function (Blueprint $table) {
            $table->id();
            $table->uuid('feedback_type_id');
            $table->unsignedBigInteger('sentiment_id');
            $table->boolean('is_default')->default(false); // Sentiment par défaut pour ce type
            $table->integer('sort_order')->default(0); // Ordre d'affichage
            $table->timestamps();
            
            $table->foreign('feedback_type_id')->references('id')->on('feedback_types')->onDelete('cascade');
            $table->foreign('sentiment_id')->references('id')->on('sentiments')->onDelete('cascade');
            
            // Une seule relation par type/sentiment
            $table->unique(['feedback_type_id', 'sentiment_id']);
            
            // Index pour les requêtes
            $table->index(['feedback_type_id', 'is_default']);
        });
        
        // Peupler la table avec les associations correctes
        $this->seedFeedbackTypeSentiments();
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_type_sentiments');
    }
    
    /**
     * Peupler les associations feedback_type -> sentiment
     */
    private function seedFeedbackTypeSentiments(): void
    {
        // Récupérer les types de feedback
        $feedbackTypes = DB::table('feedback_types')->get()->keyBy('name');
        
        // Récupérer les sentiments
        $sentiments = DB::table('sentiments')->get()->keyBy('name');
        
        // Définir les associations
        $associations = [
            'positif' => [
                'Très satisfait' => ['is_default' => true, 'sort_order' => 1],
                'Content' => ['is_default' => false, 'sort_order' => 2],
                'Heureux' => ['is_default' => false, 'sort_order' => 3],
                'Reconnaissant' => ['is_default' => false, 'sort_order' => 4],
                'Impressionné' => ['is_default' => false, 'sort_order' => 5],
            ],
            'negatif' => [
                'Mécontent' => ['is_default' => true, 'sort_order' => 1],
                'Frustré' => ['is_default' => false, 'sort_order' => 2],
                'Déçu' => ['is_default' => false, 'sort_order' => 3],
                'Irrité' => ['is_default' => false, 'sort_order' => 4],
                'Insatisfait' => ['is_default' => false, 'sort_order' => 5],
            ],
            'incident' => [
                'Frustré' => ['is_default' => true, 'sort_order' => 1],
                'Irrité' => ['is_default' => false, 'sort_order' => 2],
                'Insatisfait' => ['is_default' => false, 'sort_order' => 3],
            ]
        ];
        
        // Insérer les associations
        foreach ($associations as $feedbackTypeName => $sentimentAssocs) {
            $feedbackType = $feedbackTypes->get($feedbackTypeName);
            if (!$feedbackType) continue;
            
            foreach ($sentimentAssocs as $sentimentName => $config) {
                $sentiment = $sentiments->get($sentimentName);
                if (!$sentiment) continue;
                
                DB::table('feedback_type_sentiments')->insert([
                    'feedback_type_id' => $feedbackType->id,
                    'sentiment_id' => $sentiment->id,
                    'is_default' => $config['is_default'],
                    'sort_order' => $config['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};