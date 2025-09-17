<?php

namespace App\Console\Commands;

use App\Services\EscalationService;
use Illuminate\Console\Command;

class CheckEscalationsCommand extends Command
{
    protected $signature = 'qualywatch:check-escalations';
    
    protected $description = 'Check all active feedbacks for SLA breaches and trigger escalations';

    public function handle()
    {
        $this->info('🚨 Vérification des escalades en cours...');

        $escalationService = app(EscalationService::class);
        
        $escalationsTriggered = $escalationService->checkAllFeedbacksForEscalation();
        
        if ($escalationsTriggered > 0) {
            $this->warn("⚠️  {$escalationsTriggered} nouvelle(s) escalade(s) déclenchée(s)");
        } else {
            $this->info('✅ Aucune escalade nécessaire pour le moment');
        }

        // Afficher les statistiques actuelles
        $stats = $escalationService->getEscalationStats();
        
        $this->newLine();
        $this->line('<fg=cyan>📊 Statistiques des escalades :</fg=cyan>');
        $this->line("• Total actives: {$stats['total_active']}");
        $this->line("• Niveau 1 (Manager): {$stats['level_1']}");
        $this->line("• Niveau 2 (Direction): {$stats['level_2']}");  
        $this->line("• Niveau 3 (PDG): {$stats['level_3']}");
        $this->line("• Résolues aujourd'hui: {$stats['resolved_today']}");
        
        if ($stats['avg_resolution_time']) {
            $avgHours = round($stats['avg_resolution_time'] / 60, 1);
            $this->line("• Temps moyen résolution: {$avgHours}h");
        }

        return Command::SUCCESS;
    }
}