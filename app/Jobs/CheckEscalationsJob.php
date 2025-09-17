<?php

namespace App\Jobs;

use App\Services\EscalationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckEscalationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function handle()
    {
        Log::info('🚨 Job escalation démarré');

        try {
            $escalationService = app(EscalationService::class);
            $escalationsTriggered = $escalationService->checkAllFeedbacksForEscalation();
            
            if ($escalationsTriggered > 0) {
                Log::warning("⚠️ {$escalationsTriggered} nouvelle(s) escalade(s) déclenchée(s)");
            } else {
                Log::info('✅ Aucune escalade nécessaire');
            }

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la vérification des escalades: ' . $e->getMessage());
            throw $e;
        }

        Log::info('✅ Job escalation terminé');
    }

    public function failed(\Throwable $exception)
    {
        Log::error('❌ Job escalation échoué: ' . $exception->getMessage());
    }
}