<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Créer la table feedback_types
        Schema::create('feedback_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // positif, negatif, incident
            $table->string('label'); // Positif, Négatif, Incident  
            $table->string('color')->default('#6B7280'); // Couleur hex
            $table->string('icon')->default('chat'); // Icône
            $table->text('description')->nullable();
            $table->json('available_sentiments')->nullable(); // Sentiments disponibles pour ce type
            $table->boolean('requires_validation')->default(false); // Si validation client nécessaire
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Insérer les 3 types par défaut
        $types = [
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'positif',
                'label' => 'Positif',
                'color' => '#F97316', // Orange
                'icon' => 'star',
                'description' => 'Feedback positif - Appréciation du service',
                'available_sentiments' => json_encode([
                    'content' => 'Content',
                    'heureux' => 'Heureux',
                    'extremement_satisfait' => 'Extrêmement satisfait'
                ]),
                'requires_validation' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'negatif', 
                'label' => 'Négatif',
                'color' => '#EF4444', // Rouge
                'icon' => 'exclamation-triangle',
                'description' => 'Feedback négatif - Problème ou mécontentement',
                'available_sentiments' => json_encode([
                    'mecontent' => 'Mécontent',
                    'en_colere' => 'En colère',
                    'laisse_a_desirer' => 'Laisse à désirer'
                ]),
                'requires_validation' => true, // Les feedbacks négatifs nécessitent validation
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'incident',
                'label' => 'Incident', 
                'color' => '#DC2626', // Rouge foncé
                'icon' => 'exclamation-circle',
                'description' => 'Incident grave nécessitant une intervention rapide',
                'available_sentiments' => json_encode([
                    'urgent' => 'Urgent',
                    'critique' => 'Critique',
                    'problematique' => 'Problématique'
                ]),
                'requires_validation' => true, // Les incidents nécessitent validation
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('feedback_types')->insert($types);
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_types');
    }
};