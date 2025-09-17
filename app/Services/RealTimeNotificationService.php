<?php

namespace App\Services;

use App\Events\FeedbackCreated;
use App\Events\FeedbackStatusChanged;
use App\Events\ClientValidationRequired;
use App\Events\DashboardMetricsUpdated;
use App\Events\FeedbackEscalated;
use App\Models\Feedback;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RealTimeNotificationService
{
    /**
     * Envoyer notification de nouveau feedback
     */
    public function notifyFeedbackCreated(Feedback $feedback)
    {
        try {
            event(new FeedbackCreated($feedback));
            
            // Envoyer aussi les métriques mises à jour
            $this->updateDashboardMetrics($feedback->company_id, 'feedback_created');
            
            Log::info("Notification temps réel envoyée : nouveau feedback {$feedback->id}");
            
        } catch (\Exception $e) {
            Log::error("Erreur notification feedback créé : " . $e->getMessage());
        }
    }

    /**
     * Envoyer notification de changement de statut
     */
    public function notifyStatusChanged(Feedback $feedback, $oldStatus, $newStatus, $changedBy = null)
    {
        try {
            event(new FeedbackStatusChanged($feedback, $oldStatus, $newStatus, $changedBy));
            
            // Si le statut nécessite une validation client
            if ($newStatus === 'treated' && $feedback->requires_validation) {
                $this->notifyClientValidationRequired($feedback);
            }
            
            // Mettre à jour les métriques
            $this->updateDashboardMetrics($feedback->company_id, 'status_changed');
            
            Log::info("Notification statut changé : {$feedback->id} de {$oldStatus} vers {$newStatus}");
            
        } catch (\Exception $e) {
            Log::error("Erreur notification statut changé : " . $e->getMessage());
        }
    }

    /**
     * Notifier qu'une validation client est requise
     */
    public function notifyClientValidationRequired(Feedback $feedback)
    {
        try {
            // Générer le token si pas déjà fait
            if (!$feedback->validation_token) {
                $feedback->generateValidationToken();
            }
            
            event(new ClientValidationRequired($feedback));
            
            Log::info("Notification validation client requise : {$feedback->id}");
            
        } catch (\Exception $e) {
            Log::error("Erreur notification validation client : " . $e->getMessage());
        }
    }

    /**
     * Notifier une escalade
     */
    public function notifyEscalation($escalation)
    {
        try {
            // L'événement FeedbackEscalated est déjà déclenché dans EscalationService
            // Ici on peut ajouter des notifications supplémentaires
            
            // Notification urgente pour escalades niveau 3
            if ($escalation->escalation_level >= 3) {
                $this->sendUrgentNotification($escalation);
            }
            
            // Mettre à jour les métriques d'escalade
            $this->updateDashboardMetrics($escalation->feedback->company_id, 'escalation');
            
        } catch (\Exception $e) {
            Log::error("Erreur notification escalade : " . $e->getMessage());
        }
    }

    /**
     * Mettre à jour les métriques du dashboard en temps réel
     */
    public function updateDashboardMetrics($companyId, $triggerEvent = null)
    {
        try {
            $metrics = $this->calculateRealTimeMetrics($companyId);
            
            event(new DashboardMetricsUpdated($companyId, $metrics, 'realtime'));
            
            Log::debug("Métriques dashboard mises à jour pour l'entreprise {$companyId}");
            
        } catch (\Exception $e) {
            Log::error("Erreur mise à jour métriques : " . $e->getMessage());
        }
    }

    /**
     * Calculer les métriques en temps réel
     */
    private function calculateRealTimeMetrics($companyId)
    {
        $company = Company::find($companyId);
        if (!$company) return [];

        // Feedbacks aujourd'hui
        $todayFeedbacks = Feedback::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->get();

        // Feedbacks cette semaine
        $weekFeedbacks = Feedback::where('company_id', $companyId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        // Escalades actives
        $activeEscalations = \App\Models\Escalation::whereHas('feedback', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('is_resolved', false)->get();

        // Feedbacks en attente de validation
        $pendingValidations = Feedback::where('company_id', $companyId)
            ->pendingValidation()
            ->count();

        // SLA breaches
        $slaBreaches = Feedback::where('company_id', $companyId)
            ->slaBreached()
            ->count();

        return [
            'totals' => [
                'today_feedbacks' => $todayFeedbacks->count(),
                'week_feedbacks' => $weekFeedbacks->count(),
                'active_escalations' => $activeEscalations->count(),
                'pending_validations' => $pendingValidations,
                'sla_breaches' => $slaBreaches,
            ],
            'today_breakdown' => [
                'positive' => $todayFeedbacks->where('type', 'appreciation')->count(),
                'negative' => $todayFeedbacks->whereIn('type', ['incident', 'negatif'])->count(),
                'suggestions' => $todayFeedbacks->where('type', 'suggestion')->count(),
            ],
            'ratings' => [
                'average_today' => round($todayFeedbacks->avg('rating'), 1),
                'average_week' => round($weekFeedbacks->avg('rating'), 1),
            ],
            'escalations' => [
                'level_1' => $activeEscalations->where('escalation_level', 1)->count(),
                'level_2' => $activeEscalations->where('escalation_level', 2)->count(),
                'level_3' => $activeEscalations->where('escalation_level', 3)->count(),
            ],
            'status_distribution' => [
                'new' => $weekFeedbacks->where('status', 'new')->count(),
                'in_progress' => $weekFeedbacks->where('status', 'in_progress')->count(),
                'treated' => $weekFeedbacks->where('status', 'treated')->count(),
                'resolved' => $weekFeedbacks->whereIn('status', ['resolved', 'implemented'])->count(),
            ],
            'timestamp' => now(),
            'trigger_event' => null, // Pas exposé pour la sécurité
        ];
    }

    /**
     * Envoyer notification urgente pour escalades critiques
     */
    private function sendUrgentNotification($escalation)
    {
        try {
            // Notification push, SMS, etc. pour escalades critiques
            $users = $this->getUrgentNotificationRecipients($escalation->feedback->company_id);
            
            foreach ($users as $user) {
                // Ici on pourrait intégrer avec des services comme :
                // - Firebase push notifications
                // - SMS via Twilio
                // - Slack webhooks
                // - Teams webhooks
                
                Log::warning("Notification urgente à envoyer à {$user->name} pour escalade niveau {$escalation->escalation_level}");
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur notification urgente : " . $e->getMessage());
        }
    }

    /**
     * Récupérer les destinataires des notifications urgentes
     */
    private function getUrgentNotificationRecipients($companyId)
    {
        return User::where('company_id', $companyId)
            ->whereIn('role', ['ceo', 'director', 'manager'])
            ->where('receives_urgent_notifications', true)
            ->get();
    }

    /**
     * Envoyer notification de nouvelle validation client
     */
    public function notifyClientValidationCompleted(Feedback $feedback, $validationStatus)
    {
        try {
            $notification = [
                'type' => 'validation_completed',
                'feedback_id' => $feedback->id,
                'reference' => $feedback->reference,
                'validation_status' => $validationStatus,
                'client_name' => $feedback->client?->name,
                'timestamp' => now(),
            ];

            // Diffuser sur le canal de l'entreprise
            broadcast(new \Illuminate\Broadcasting\InteractsWithBroadcasting)->toOthers()
                ->toPrivate('company.' . $feedback->company_id)
                ->event('client.validation_completed')
                ->with($notification);

            Log::info("Notification validation client terminée : {$feedback->id} - {$validationStatus}");

        } catch (\Exception $e) {
            Log::error("Erreur notification validation terminée : " . $e->getMessage());
        }
    }

    /**
     * Diffuser les statistiques en temps réel
     */
    public function broadcastLiveStats($companyId = null)
    {
        try {
            if ($companyId) {
                $this->updateDashboardMetrics($companyId, 'manual_refresh');
            } else {
                // Diffuser pour toutes les entreprises actives
                $activeCompanies = Company::whereHas('feedbacks', function ($query) {
                    $query->where('created_at', '>', now()->subDays(7));
                })->get();

                foreach ($activeCompanies as $company) {
                    $this->updateDashboardMetrics($company->id, 'manual_refresh');
                }
            }

        } catch (\Exception $e) {
            Log::error("Erreur diffusion stats live : " . $e->getMessage());
        }
    }

    /**
     * Vérifier la santé des connexions WebSocket
     */
    public function checkWebSocketHealth()
    {
        try {
            // Test de connexion Pusher
            $pusher = app('pusher');
            
            $testData = [
                'message' => 'Health check',
                'timestamp' => now(),
            ];

            $pusher->trigger('health-check', 'ping', $testData);
            
            Log::info("Test de santé WebSocket réussi");
            return true;

        } catch (\Exception $e) {
            Log::error("Échec test santé WebSocket : " . $e->getMessage());
            return false;
        }
    }
}