<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixFeedbackStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:feedback-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix feedback statuses with proper UUIDs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing feedback statuses...');
        
        // Temporairement désactiver les vérifications de FK
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Définir les statuts corrects selon la structure de la table
        $statuses = [
            ['name' => 'new', 'label' => 'Nouveau', 'color' => '#3B82F6', 'sort_order' => 1],
            ['name' => 'in_progress', 'label' => 'En cours', 'color' => '#F97316', 'sort_order' => 2],
            ['name' => 'treated', 'label' => 'Traité', 'color' => '#8B5CF6', 'sort_order' => 3],
            ['name' => 'resolved', 'label' => 'Résolu', 'color' => '#10B981', 'sort_order' => 4],
            ['name' => 'partially_resolved', 'label' => 'Partiellement résolu', 'color' => '#F59E0B', 'sort_order' => 5],
            ['name' => 'not_resolved', 'label' => 'Non résolu', 'color' => '#DC2626', 'sort_order' => 6],
            ['name' => 'implemented', 'label' => 'Implémenté', 'color' => '#059669', 'sort_order' => 7],
            ['name' => 'partially_implemented', 'label' => 'Partiellement implémenté', 'color' => '#D97706', 'sort_order' => 8],
            ['name' => 'rejected', 'label' => 'Rejeté', 'color' => '#EF4444', 'sort_order' => 9],
            ['name' => 'archived', 'label' => 'Archivé', 'color' => '#6B7280', 'sort_order' => 10],
        ];

        // Mapper les UUIDs des statuts existants corrompus vers de nouveaux UUIDs
        $existingStatuses = \DB::table('feedback_statuses')->get();
        $statusMapping = [];

        foreach ($statuses as $statusData) {
            $existingStatus = $existingStatuses->where('name', $statusData['name'])->first();
            
            if ($existingStatus) {
                // Générer un nouvel UUID pour remplacer l'ancien
                $newUuid = \Illuminate\Support\Str::uuid();
                $oldId = $existingStatus->id;
                
                // Mettre à jour le statut avec le nouvel UUID et les bonnes données
                \DB::table('feedback_statuses')
                    ->where('id', $oldId)
                    ->update([
                        'id' => $newUuid,
                        'label' => $statusData['label'],
                        'color' => $statusData['color'],
                        'sort_order' => $statusData['sort_order'],
                        'is_active' => true
                    ]);
                
                // Mettre à jour les références dans feedbacks
                \DB::table('feedbacks')
                    ->where('feedback_status_id', $oldId)
                    ->update(['feedback_status_id' => $newUuid]);
                
                $statusMapping[$statusData['name']] = ['old' => $oldId, 'new' => $newUuid];
                $this->info("Updated status '{$statusData['name']}': {$oldId} → {$newUuid}");
            } else {
                // Créer le statut s'il n'existe pas
                $newUuid = \Illuminate\Support\Str::uuid();
                \App\Models\FeedbackStatus::create([
                    'id' => $newUuid,
                    'name' => $statusData['name'],
                    'label' => $statusData['label'],
                    'color' => $statusData['color'],
                    'sort_order' => $statusData['sort_order'],
                    'is_active' => true
                ]);
                $this->info("Created new status '{$statusData['name']}': {$newUuid}");
            }
        }
        
        // Réactiver les vérifications de FK
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('Feedback statuses fixed successfully!');
        
        // Afficher les nouveaux UUIDs
        $newStatuses = \App\Models\FeedbackStatus::all(['id', 'name']);
        foreach ($newStatuses as $status) {
            $this->line($status->name . ': ' . $status->id);
        }
    }
}
