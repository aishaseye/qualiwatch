<?php

namespace App\Listeners;

use App\Events\FeedbackCreated;
use App\Events\FeedbackStatusChanged;
use App\Services\GamificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateGamificationProgress implements ShouldQueue
{
    use InteractsWithQueue;

    protected $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    /**
     * Handle the FeedbackCreated event.
     */
    public function handle(FeedbackCreated $event): void
    {
        try {
            $feedback = $event->feedback;
            $employee = $feedback->employee;
            
            if (!$employee) return;

            // Mettre à jour les défis en cours
            $this->updateChallengeProgress($employee, $feedback, 'feedback_created');
            
            // Vérifier et attribuer les badges
            $this->gamificationService->checkAndAwardBadges($employee, 'feedback_received');
            
            Log::info("Gamification progress updated for feedback creation", [
                'feedback_id' => $feedback->id,
                'employee_id' => $employee->id,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to update gamification progress for feedback creation", [
                'feedback_id' => $event->feedback->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the FeedbackStatusChanged event.
     */
    public function handleStatusChange(FeedbackStatusChanged $event): void
    {
        try {
            $feedback = $event->feedback;
            $oldStatus = $event->oldStatus;
            $newStatus = $event->newStatus;
            $employee = $feedback->employee;
            
            if (!$employee) return;

            // Mettre à jour les défis selon le changement de statut
            $this->updateChallengeProgress($employee, $feedback, 'status_changed', [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
            
            // Vérifier les badges spécifiques aux statuts
            if ($newStatus->name === 'resolved') {
                $this->gamificationService->checkAndAwardBadges($employee, 'feedback_resolved');
            } elseif ($newStatus->name === 'treated') {
                $this->gamificationService->checkAndAwardBadges($employee, 'feedback_treated');
            }
            
            Log::info("Gamification progress updated for status change", [
                'feedback_id' => $feedback->id,
                'employee_id' => $employee->id,
                'status_change' => "{$oldStatus->name} -> {$newStatus->name}",
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to update gamification progress for status change", [
                'feedback_id' => $event->feedback->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mettre à jour la progression des défis
     */
    private function updateChallengeProgress($employee, $feedback, $triggerType, $context = []): void
    {
        // Récupérer les défis actifs de l'utilisateur
        $activeChallenges = \App\Models\UserChallenge::where('user_id', $employee->id)
                                                   ->where('is_active', true)
                                                   ->where('is_completed', false)
                                                   ->with('challenge')
                                                   ->get();

        foreach ($activeChallenges as $userChallenge) {
            $challenge = $userChallenge->challenge;
            $newValue = $this->calculateNewChallengeValue($challenge, $employee, $feedback, $triggerType, $context);
            
            if ($newValue !== null && $newValue > $userChallenge->current_value) {
                $progressData = [
                    'trigger_type' => $triggerType,
                    'feedback_id' => $feedback->id,
                    'feedback_type' => $feedback->feedbackType->name,
                    'feedback_rating' => $feedback->rating,
                    'context' => $context,
                    'updated_at' => now()->toISOString(),
                ];
                
                $userChallenge->updateProgress($newValue, $progressData);
            }
        }
    }

    /**
     * Calculer la nouvelle valeur pour un défi
     */
    private function calculateNewChallengeValue($challenge, $employee, $feedback, $triggerType, $context): ?int
    {
        $objectives = $challenge->objectives;
        
        if (!$objectives || !isset($objectives['type'])) {
            return null;
        }

        switch ($objectives['type']) {
            case 'total_feedbacks':
                return $this->calculateTotalFeedbacks($employee, $challenge);
                
            case 'positive_feedbacks':
                return $this->calculatePositiveFeedbacks($employee, $challenge);
                
            case 'satisfaction_score':
                return $this->calculateSatisfactionScore($employee, $challenge);
                
            case 'resolution_count':
                if ($triggerType === 'status_changed' && 
                    isset($context['new_status']) && 
                    $context['new_status']->name === 'resolved') {
                    return $this->calculateResolutionCount($employee, $challenge);
                }
                break;
                
            case 'response_speed':
                if ($triggerType === 'status_changed' && 
                    isset($context['new_status']) && 
                    $context['new_status']->name === 'treated') {
                    return $this->calculateAverageResponseTime($employee, $challenge);
                }
                break;
                
            case 'kalipoints_earned':
                return $this->calculateKaliPointsEarned($employee, $challenge);
        }
        
        return null;
    }

    /**
     * Calculer le total de feedbacks
     */
    private function calculateTotalFeedbacks($employee, $challenge): int
    {
        $query = \App\Models\Feedback::where('employee_id', $employee->id);
        
        // Filtrer par période du défi
        $this->applyChallengePeriodFilter($query, $challenge);
        
        return $query->count();
    }

    /**
     * Calculer les feedbacks positifs
     */
    private function calculatePositiveFeedbacks($employee, $challenge): int
    {
        $query = \App\Models\Feedback::where('employee_id', $employee->id)
                                   ->where('rating', '>=', 4);
        
        $this->applyChallengePeriodFilter($query, $challenge);
        
        return $query->count();
    }

    /**
     * Calculer le score de satisfaction
     */
    private function calculateSatisfactionScore($employee, $challenge): int
    {
        $query = \App\Models\Feedback::where('employee_id', $employee->id)
                                   ->whereNotNull('rating');
        
        $this->applyChallengePeriodFilter($query, $challenge);
        
        $feedbacks = $query->get();
        
        if ($feedbacks->isEmpty()) return 0;
        
        return (int) (($feedbacks->avg('rating') / 5) * 100);
    }

    /**
     * Calculer le nombre de résolutions
     */
    private function calculateResolutionCount($employee, $challenge): int
    {
        $query = \App\Models\Feedback::where('employee_id', $employee->id)
                                   ->whereNotNull('resolved_at');
        
        $this->applyChallengePeriodFilter($query, $challenge);
        
        return $query->count();
    }

    /**
     * Calculer le temps de réponse moyen (inversé pour le défi)
     */
    private function calculateAverageResponseTime($employee, $challenge): int
    {
        $query = \App\Models\Feedback::where('employee_id', $employee->id)
                                   ->whereNotNull('treated_at');
        
        $this->applyChallengePeriodFilter($query, $challenge);
        
        $feedbacks = $query->get();
        
        if ($feedbacks->isEmpty()) return 0;
        
        $avgHours = $feedbacks->map(function ($feedback) {
            return $feedback->created_at->diffInHours($feedback->treated_at);
        })->avg();
        
        // Inverser pour le défi (plus c'est rapide, plus c'est élevé)
        return (int) max(0, 100 - $avgHours);
    }

    /**
     * Calculer les KaliPoints gagnés
     */
    private function calculateKaliPointsEarned($employee, $challenge): int
    {
        $query = \App\Models\Feedback::where('employee_id', $employee->id);
        
        $this->applyChallengePeriodFilter($query, $challenge);
        
        return $query->sum('kalipoints');
    }

    /**
     * Appliquer le filtre de période du défi
     */
    private function applyChallengePeriodFilter($query, $challenge): void
    {
        $query->whereBetween('created_at', [$challenge->start_date, $challenge->end_date]);
    }

    /**
     * Handle a job failure.
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error("Gamification progress update failed", [
            'event_type' => get_class($event),
            'feedback_id' => $event->feedback->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}