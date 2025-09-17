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
        // 1. Créer la table des sentiments positifs
        Schema::create('positive_sentiments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#10B981'); // Vert par défaut
            $table->string('icon', 50)->default('smile');
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_default', 'sort_order']);
        });

        // 2. Créer la table des sentiments négatifs
        Schema::create('negative_sentiments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#EF4444'); // Rouge par défaut
            $table->string('icon', 50)->default('frown');
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_default', 'sort_order']);
        });

        // 3. Créer la table des sentiments pour suggestions
        Schema::create('suggestion_sentiments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#F59E0B'); // Orange par défaut
            $table->string('icon', 50)->default('lightbulb');
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_default', 'sort_order']);
        });

        // 4. Peupler les tables avec les données
        $this->seedSentimentTables();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_sentiments');
        Schema::dropIfExists('negative_sentiments');
        Schema::dropIfExists('positive_sentiments');
    }

    /**
     * Peupler les 3 tables de sentiments
     */
    private function seedSentimentTables(): void
    {
        // Sentiments positifs
        $positiveSentiments = [
            ['name' => 'tres_satisfait', 'label' => 'Très satisfait', 'description' => 'Client très content du service reçu', 'color' => '#059669', 'icon' => 'star', 'is_default' => true, 'sort_order' => 1],
            ['name' => 'content', 'label' => 'Content', 'description' => 'Client satisfait de son expérience', 'color' => '#10B981', 'icon' => 'smile', 'is_default' => false, 'sort_order' => 2],
            ['name' => 'heureux', 'label' => 'Heureux', 'description' => 'Client joyeux et satisfait', 'color' => '#34D399', 'icon' => 'laugh', 'is_default' => false, 'sort_order' => 3],
            ['name' => 'reconnaissant', 'label' => 'Reconnaissant', 'description' => 'Client qui apprécie le service', 'color' => '#6EE7B7', 'icon' => 'heart', 'is_default' => false, 'sort_order' => 4],
            ['name' => 'impressionne', 'label' => 'Impressionné', 'description' => 'Client impressionné par la qualité', 'color' => '#A7F3D0', 'icon' => 'thumbs-up', 'is_default' => false, 'sort_order' => 5],
        ];

        // Sentiments négatifs
        $negativeSentiments = [
            ['name' => 'mecontent', 'label' => 'Mécontent', 'description' => 'Client pas satisfait du service', 'color' => '#EF4444', 'icon' => 'frown', 'is_default' => true, 'sort_order' => 1],
            ['name' => 'frustre', 'label' => 'Frustré', 'description' => 'Client frustré par une situation', 'color' => '#DC2626', 'icon' => 'angry', 'is_default' => false, 'sort_order' => 2],
            ['name' => 'decu', 'label' => 'Déçu', 'description' => 'Client déçu par le service reçu', 'color' => '#B91C1C', 'icon' => 'sad', 'is_default' => false, 'sort_order' => 3],
            ['name' => 'irrite', 'label' => 'Irrité', 'description' => 'Client agacé ou contrarié', 'color' => '#991B1B', 'icon' => 'meh', 'is_default' => false, 'sort_order' => 4],
            ['name' => 'insatisfait', 'label' => 'Insatisfait', 'description' => 'Client non satisfait globalement', 'color' => '#7F1D1D', 'icon' => 'thumbs-down', 'is_default' => false, 'sort_order' => 5],
        ];

        // Sentiments pour suggestions
        $suggestionSentiments = [
            ['name' => 'constructif', 'label' => 'Constructif', 'description' => 'Retour constructif pour améliorer', 'color' => '#F59E0B', 'icon' => 'lightbulb', 'is_default' => true, 'sort_order' => 1],
            ['name' => 'propositionnel', 'label' => 'Propositionnel', 'description' => 'Propose des améliorations', 'color' => '#D97706', 'icon' => 'idea', 'is_default' => false, 'sort_order' => 2],
            ['name' => 'amelioration', 'label' => 'Amélioration', 'description' => 'Suggère des améliorations', 'color' => '#B45309', 'icon' => 'tools', 'is_default' => false, 'sort_order' => 3],
            ['name' => 'innovation', 'label' => 'Innovation', 'description' => 'Propose des innovations', 'color' => '#92400E', 'icon' => 'rocket', 'is_default' => false, 'sort_order' => 4],
            ['name' => 'optimisation', 'label' => 'Optimisation', 'description' => 'Suggère des optimisations', 'color' => '#78350F', 'icon' => 'cog', 'is_default' => false, 'sort_order' => 5],
        ];

        // Insérer les données avec timestamps
        $timestamp = now();

        foreach ($positiveSentiments as &$sentiment) {
            $sentiment['created_at'] = $timestamp;
            $sentiment['updated_at'] = $timestamp;
        }

        foreach ($negativeSentiments as &$sentiment) {
            $sentiment['created_at'] = $timestamp;
            $sentiment['updated_at'] = $timestamp;
        }

        foreach ($suggestionSentiments as &$sentiment) {
            $sentiment['created_at'] = $timestamp;
            $sentiment['updated_at'] = $timestamp;
        }

        DB::table('positive_sentiments')->insert($positiveSentiments);
        DB::table('negative_sentiments')->insert($negativeSentiments);
        DB::table('suggestion_sentiments')->insert($suggestionSentiments);
    }
};