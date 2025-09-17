<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter le statut "seen" entre "new" et "in_progress"
        $seenStatus = [
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'seen',
            'label' => 'Vu',
            'color' => '#8B5CF6', // Violet clair
            'icon' => 'eye',
            'description' => 'Feedback vu par l\'admin, en attente de prise en charge',
            'is_final' => false,
            'requires_admin_action' => true,
            'sort_order' => 1.5, // Entre new (1) et in_progress (2)
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('feedback_statuses')->insert($seenStatus);

        // Mettre à jour l'ordre de tri des autres statuts pour faire de la place
        DB::table('feedback_statuses')
            ->where('sort_order', '>=', 2)
            ->increment('sort_order', 1);
    }

    public function down(): void
    {
        // Supprimer le statut "seen"
        DB::table('feedback_statuses')->where('name', 'seen')->delete();
        
        // Restaurer l'ordre de tri original
        DB::table('feedback_statuses')
            ->where('sort_order', '>=', 3)
            ->decrement('sort_order', 1);
    }
};