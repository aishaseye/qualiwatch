<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Créer la table feedback_statuses
        Schema::create('feedback_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // new, in_progress, treated, resolved, etc.
            $table->string('label'); // Nouveau, En cours, Traité, Résolu, etc.
            $table->string('color')->default('#6B7280'); // Couleur hex
            $table->string('icon')->default('circle'); // Icône
            $table->text('description')->nullable();
            $table->boolean('is_final')->default(false); // Si c'est un statut final (résolu, archivé)
            $table->boolean('requires_admin_action')->default(false); // Si nécessite action admin
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Insérer les statuses par défaut
        $statuses = [
            // Statuts généraux
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'new',
                'label' => 'Nouveau',
                'color' => '#3B82F6', // Bleu
                'icon' => 'plus-circle',
                'description' => 'Feedback nouvellement créé, en attente de traitement',
                'is_final' => false,
                'requires_admin_action' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'in_progress',
                'label' => 'En cours',
                'color' => '#F97316', // Orange
                'icon' => 'clock',
                'description' => 'Feedback en cours de traitement',
                'is_final' => false,
                'requires_admin_action' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'treated',
                'label' => 'Traité',
                'color' => '#8B5CF6', // Violet
                'icon' => 'check-circle',
                'description' => 'Feedback traité, en attente de validation client',
                'is_final' => false,
                'requires_admin_action' => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Statuts pour incidents/négatifs
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'resolved',
                'label' => 'Résolu',
                'color' => '#10B981', // Vert
                'icon' => 'check-circle-solid',
                'description' => 'Problème complètement résolu et validé par le client',
                'is_final' => true,
                'requires_admin_action' => false,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'partially_resolved',
                'label' => 'Partiellement résolu',
                'color' => '#F59E0B', // Jaune
                'icon' => 'exclamation-circle',
                'description' => 'Problème partiellement résolu selon le client',
                'is_final' => false,
                'requires_admin_action' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'not_resolved',
                'label' => 'Non résolu',
                'color' => '#DC2626', // Rouge foncé
                'icon' => 'x-circle',
                'description' => 'Problème non résolu selon le client',
                'is_final' => false,
                'requires_admin_action' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Statuts pour suggestions
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'implemented',
                'label' => 'Implémenté',
                'color' => '#059669', // Vert foncé
                'icon' => 'check-badge',
                'description' => 'Suggestion implémentée et validée par le client',
                'is_final' => true,
                'requires_admin_action' => false,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'partially_implemented',
                'label' => 'Partiellement implémenté',
                'color' => '#D97706', // Orange foncé
                'icon' => 'clock-exclamation',
                'description' => 'Suggestion partiellement implémentée',
                'is_final' => false,
                'requires_admin_action' => true,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'rejected',
                'label' => 'Rejeté',
                'color' => '#EF4444', // Rouge
                'icon' => 'x-circle-solid',
                'description' => 'Suggestion rejetée',
                'is_final' => true,
                'requires_admin_action' => false,
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Statuts généraux finaux
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => 'archived',
                'label' => 'Archivé',
                'color' => '#6B7280', // Gris
                'icon' => 'archive',
                'description' => 'Feedback archivé',
                'is_final' => true,
                'requires_admin_action' => false,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('feedback_statuses')->insert($statuses);
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_statuses');
    }
};