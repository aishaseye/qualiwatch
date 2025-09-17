<?php

namespace App\Observers;

use App\Models\Feedback;
use App\Services\NotificationService;
use App\Services\EscalationService;

class FeedbackObserver
{
    protected $notificationService;
    protected $escalationService;

    public function __construct(NotificationService $notificationService, EscalationService $escalationService)
    {
        $this->notificationService = $notificationService;
        $this->escalationService = $escalationService;
    }

    public function created(Feedback $feedback)
    {
        // Envoyer les notifications (emails)
        $this->notificationService->sendFeedbackNotification($feedback);

        // Initier le suivi SLA et escalations si applicable
        $this->escalationService->checkFeedbackForEscalation($feedback);
    }

    public function updated(Feedback $feedback)
    {
        // Si le statut change, envoyer une notification
        if ($feedback->isDirty('status')) {
            $this->handleStatusChange($feedback);
        }
    }

    private function handleStatusChange(Feedback $feedback)
    {
        $oldStatus = $feedback->getOriginal('status');
        $newStatus = $feedback->status;

        // Si le statut passe à "treated", envoyer un email de validation AU CLIENT SEULEMENT
        if ($newStatus === 'treated' && $oldStatus !== 'treated') {
            $this->notificationService->sendTreatedNotification($feedback);
            return; // Pas d'autres notifications
        }

        // Pour les autres changements de statut, pas d'email automatique
        // (seulement des notifications in-app si nécessaire)
    }
}