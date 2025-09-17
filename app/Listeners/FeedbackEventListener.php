<?php

namespace App\Listeners;

use App\Events\FeedbackCreated;
use App\Events\FeedbackStatusChanged;
use App\Services\RealTimeNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class FeedbackEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(RealTimeNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event when feedback is created
     */
    public function handleFeedbackCreated($event)
    {
        try {
            // La notification temps réel est déjà envoyée dans l'événement
            // Ici on peut ajouter d'autres actions
            
            // Vérifier si c'est un feedback critique nécessitant escalade immédiate
            if ($event->feedback->priority_level >= 4) {
                $event->feedback->triggerEscalationCheck();
            }

            // Log pour audit
            Log::info("Feedback créé - ID: {$event->feedback->id}, Type: {$event->feedback->type}, Priorité: {$event->feedback->priority_level}");

        } catch (\Exception $e) {
            Log::error("Erreur dans FeedbackEventListener::handleFeedbackCreated - " . $e->getMessage());
        }
    }

    /**
     * Handle the event when feedback status changes
     */
    public function handleFeedbackStatusChanged($event)
    {
        try {
            // Actions selon le nouveau statut
            switch ($event->newStatus) {
                case 'treated':
                    // Si c'est un feedback nécessitant validation
                    if ($event->feedback->requires_validation) {
                        // Générer token et envoyer email
                        if (!$event->feedback->validation_token) {
                            $event->feedback->generateValidationToken();
                        }
                        
                        // Programmer l'envoi d'email de rappel après 24h
                        \App\Jobs\SendValidationReminderJob::dispatch($event->feedback)
                            ->delay(now()->addHours(24));
                    }
                    break;

                case 'resolved':
                case 'implemented':
                    // Résoudre toutes les escalades actives
                    $event->feedback->resolveAllEscalations("Feedback résolu");
                    break;

                case 'archived':
                    // Nettoyer les tokens de validation expirés
                    $event->feedback->update([
                        'validation_token' => null,
                        'validation_expires_at' => null,
                    ]);
                    break;
            }

            Log::info("Statut feedback changé - ID: {$event->feedback->id}, {$event->oldStatus} → {$event->newStatus}");

        } catch (\Exception $e) {
            Log::error("Erreur dans FeedbackEventListener::handleFeedbackStatusChanged - " . $e->getMessage());
        }
    }

    /**
     * Register the listeners for the subscriber
     */
    public function subscribe($events)
    {
        $events->listen(
            FeedbackCreated::class,
            [FeedbackEventListener::class, 'handleFeedbackCreated']
        );

        $events->listen(
            FeedbackStatusChanged::class,
            [FeedbackEventListener::class, 'handleFeedbackStatusChanged']
        );
    }
}