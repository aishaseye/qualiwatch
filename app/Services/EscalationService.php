<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\SlaRule;
use App\Models\Escalation;
use App\Events\FeedbackEscalated;
use App\Events\SlaBreached;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EscalationService
{
    /**
     * Vérifier tous les feedbacks en cours pour les escalades
     */
    public function checkAllFeedbacksForEscalation()
    {
        $activeFeedbacks = Feedback::whereHas('feedbackStatus', function ($query) {
            $query->whereIn('name', ['new', 'in_progress', 'treated']);
        })->where('created_at', '>', now()->subDays(7)) // Seulement les feedbacks récents
        ->get();

        $escalationsCount = 0;

        foreach ($activeFeedbacks as $feedback) {
            if ($this->checkFeedbackForEscalation($feedback)) {
                $escalationsCount++;
            }
        }

        Log::info("Escalation check completed. {$escalationsCount} escalations triggered.");
        
        return $escalationsCount;
    }

    /**
     * Vérifier un feedback spécifique pour escalade
     */
    public function checkFeedbackForEscalation(Feedback $feedback)
    {
        // Trouver la règle SLA applicable
        $slaRule = SlaRule::findApplicableRule($feedback);
        
        if (!$slaRule) {
            return false; // Pas de règle SLA applicable
        }

        // Vérifier quel niveau d'escalade est nécessaire
        $requiredLevel = $slaRule->getEscalationLevel($feedback->created_at);
        
        if ($requiredLevel == 0) {
            return false; // Pas d'escalade nécessaire
        }

        // Vérifier si cette escalade existe déjà
        $existingEscalation = Escalation::where('feedback_id', $feedback->id)
            ->where('escalation_level', $requiredLevel)
            ->where('is_resolved', false)
            ->first();

        if ($existingEscalation) {
            return false; // Escalade déjà créée
        }

        // Créer l'escalade
        return $this->createEscalation($feedback, $slaRule, $requiredLevel);
    }

    /**
     * Créer une nouvelle escalade
     */
    public function createEscalation(Feedback $feedback, SlaRule $slaRule, $level, $reason = 'sla_breach')
    {
        // Déterminer la raison spécifique
        $reason = $this->determineEscalationReason($feedback, $slaRule, $level);

        // Créer l'escalade
        $escalation = Escalation::createForFeedback($feedback, $slaRule, $level, $reason);

        // Notifier les personnes concernées
        $this->notifyEscalation($escalation);

        // Déclencher l'événement d'escalade
        event(new FeedbackEscalated($escalation));

        Log::warning("Escalation créée pour le feedback {$feedback->id} - Niveau {$level} - Raison: {$reason}");

        return $escalation;
    }

    /**
     * Déterminer la raison de l'escalade
     */
    private function determineEscalationReason(Feedback $feedback, SlaRule $slaRule, $level)
    {
        // Vérifier les conditions critiques
        if ($feedback->rating >= 4 && in_array($feedback->sentiment, ['en_colere', 'critique'])) {
            return 'critical_rating';
        }


        // Vérifier s'il y a plusieurs incidents du même client
        $recentIncidents = Feedback::where('client_id', $feedback->client_id)
            ->where('created_at', '>', now()->subDays(7))
            ->whereHas('feedbackType', function ($query) {
                $query->whereIn('name', ['negatif', 'incident']);
            })
            ->count();

        if ($recentIncidents >= 3) {
            return 'multiple_incidents';
        }

        if (in_array($feedback->sentiment, ['en_colere', 'urgent', 'critique'])) {
            return 'urgent_sentiment';
        }

        return 'sla_breach';
    }

    /**
     * Notifier les personnes concernées par l'escalade
     */
    private function notifyEscalation(Escalation $escalation)
    {
        $slaRule = $escalation->slaRule;
        $level = $escalation->escalation_level;

        // Récupérer les destinataires selon le niveau
        $recipients = match($level) {
            1 => $slaRule->level_1_recipients ?? [],
            2 => $slaRule->level_2_recipients ?? [],
            3 => $slaRule->level_3_recipients ?? [],
            default => []
        };

        $channels = $slaRule->notification_channels ?? ['email', 'app'];

        // Récupérer les utilisateurs à notifier
        $usersToNotify = $this->getUsersToNotify($escalation->feedback->company_id, $recipients);

        if (empty($usersToNotify)) {
            Log::warning("Aucun utilisateur à notifier pour l'escalade {$escalation->id}");
            return;
        }

        $notifiedUserIds = [];
        $usedChannels = [];

        foreach ($usersToNotify as $user) {
            foreach ($channels as $channel) {
                switch ($channel) {
                    case 'email':
                        $this->sendEmailNotification($user, $escalation);
                        $usedChannels[] = 'email';
                        break;
                        
                    case 'app':
                        $this->sendAppNotification($user, $escalation);
                        $usedChannels[] = 'app';
                        break;
                        
                    case 'sms':
                        $this->sendSmsNotification($user, $escalation);
                        $usedChannels[] = 'sms';
                        break;
                }
            }
            $notifiedUserIds[] = $user->id;
        }

        // Marquer les notifications comme envoyées
        $escalation->markAsNotified($notifiedUserIds, array_unique($usedChannels));
    }

    /**
     * Récupérer les utilisateurs à notifier
     */
    private function getUsersToNotify($companyId, array $recipientTypes)
    {
        $users = collect();

        foreach ($recipientTypes as $type) {
            switch ($type) {
                case 'manager':
                    $managers = \App\Models\User::where('company_id', $companyId)
                        ->where('role', 'manager')
                        ->get();
                    $users = $users->merge($managers);
                    break;
                    
                case 'service_head':
                    $serviceHeads = \App\Models\User::where('company_id', $companyId)
                        ->where('role', 'service_head')
                        ->get();
                    $users = $users->merge($serviceHeads);
                    break;
                    
                case 'director':
                    $directors = \App\Models\User::where('company_id', $companyId)
                        ->where('role', 'director')
                        ->get();
                    $users = $users->merge($directors);
                    break;
                    
                case 'ceo':
                    $ceos = \App\Models\User::where('company_id', $companyId)
                        ->where('role', 'ceo')
                        ->get();
                    $users = $users->merge($ceos);
                    break;
            }
        }

        return $users->unique('id');
    }

    /**
     * Envoyer notification par email
     */
    private function sendEmailNotification($user, Escalation $escalation)
    {
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\EscalationNotification($escalation, $user)
            );
        } catch (\Exception $e) {
            Log::error("Erreur envoi email escalade: " . $e->getMessage());
        }
    }

    /**
     * Envoyer notification dans l'app
     */
    private function sendAppNotification($user, Escalation $escalation)
    {
        try {
            $user->notifications()->create([
                'type' => 'escalation',
                'title' => "Escalade Niveau {$escalation->escalation_level}",
                'message' => "Feedback #{$escalation->feedback->reference} nécessite votre attention",
                'data' => [
                    'escalation_id' => $escalation->id,
                    'feedback_id' => $escalation->feedback_id,
                    'level' => $escalation->escalation_level,
                    'reason' => $escalation->trigger_reason
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur notification app escalade: " . $e->getMessage());
        }
    }

    /**
     * Envoyer notification par SMS
     */
    private function sendSmsNotification($user, Escalation $escalation)
    {
        if (!$user->phone) return;

        try {
            // Intégration avec service SMS (Twilio, etc.)
            $message = "URGENT: Escalade Niveau {$escalation->escalation_level} - Feedback #{$escalation->feedback->reference} nécessite votre attention immédiate. QualyWatch";
            
            // TODO: Implémenter l'envoi SMS réel
            Log::info("SMS à envoyer à {$user->phone}: {$message}");
            
        } catch (\Exception $e) {
            Log::error("Erreur envoi SMS escalade: " . $e->getMessage());
        }
    }

    /**
     * Résoudre une escalade
     */
    public function resolveEscalation(Escalation $escalation, $notes = null)
    {
        $escalation->resolve($notes);
        
        Log::info("Escalade {$escalation->id} résolue");
        
        return $escalation;
    }

    /**
     * Résoudre toutes les escalades d'un feedback
     */
    public function resolveAllEscalationsForFeedback(Feedback $feedback, $notes = null)
    {
        $escalations = Escalation::where('feedback_id', $feedback->id)
            ->where('is_resolved', false)
            ->get();

        foreach ($escalations as $escalation) {
            $escalation->resolve($notes);
        }

        return $escalations->count();
    }

    /**
     * Obtenir les statistiques d'escalade
     */
    public function getEscalationStats($companyId = null)
    {
        $query = Escalation::query();
        
        if ($companyId) {
            $query->whereHas('feedback', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        return [
            'total_active' => $query->clone()->active()->count(),
            'level_1' => $query->clone()->active()->byLevel(1)->count(),
            'level_2' => $query->clone()->active()->byLevel(2)->count(),
            'level_3' => $query->clone()->active()->byLevel(3)->count(),
            'resolved_today' => $query->clone()->resolved()
                ->whereDate('resolved_at', today())
                ->count(),
            'avg_resolution_time' => $query->clone()->resolved()
                ->whereDate('resolved_at', '>', now()->subDays(30))
                ->avg(\DB::raw('TIMESTAMPDIFF(MINUTE, escalated_at, resolved_at)'))
        ];
    }
}