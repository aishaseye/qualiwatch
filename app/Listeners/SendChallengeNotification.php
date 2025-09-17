<?php

namespace App\Listeners;

use App\Events\ChallengeCompleted;
use App\Events\ChallengeProgressUpdated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendChallengeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the ChallengeCompleted event.
     */
    public function handleCompleted(ChallengeCompleted $event): void
    {
        try {
            $userChallenge = $event->userChallenge;
            $user = $event->user;
            $challenge = $event->challenge;

            // Notification à l'utilisateur qui a terminé
            $this->notificationService->create([
                'user_id' => $user->id,
                'type' => 'challenge_completed',
                'title' => $this->getCompletionTitle($userChallenge),
                'message' => $this->getCompletionMessage($userChallenge),
                'data' => [
                    'challenge_id' => $challenge->id,
                    'challenge_title' => $challenge->title,
                    'completion_rank' => $userChallenge->completion_rank,
                    'points_earned' => $userChallenge->points_earned,
                    'is_winner' => $userChallenge->is_winner,
                    'time_to_complete' => $userChallenge->time_to_complete,
                    'rewards_earned' => $userChallenge->rewards_earned,
                ],
                'priority' => $this->getPriorityByRank($userChallenge->completion_rank),
                'channels' => $this->getChannelsByRank($userChallenge->completion_rank),
                'scheduled_for' => now(),
            ]);

            // Notifier les autres participants du défi
            $this->notifyOtherParticipants($event);

            // Notifier les managers si c'est un gagnant
            if ($userChallenge->is_winner) {
                $this->notifyManagers($event);
            }

            Log::info("Challenge completed notification sent", [
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'rank' => $userChallenge->completion_rank,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send challenge completed notification", [
                'user_id' => $event->user->id,
                'challenge_id' => $event->challenge->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the ChallengeProgressUpdated event.
     */
    public function handleProgress(ChallengeProgressUpdated $event): void
    {
        try {
            $userChallenge = $event->userChallenge;
            $user = $event->user;
            $challenge = $event->challenge;

            // Notifications de milestone (25%, 50%, 75%)
            if ($this->isProgressMilestone($userChallenge->progress_percentage)) {
                $this->notificationService->create([
                    'user_id' => $user->id,
                    'type' => 'challenge_progress',
                    'title' => '📈 Progression du défi',
                    'message' => "Vous avez atteint {$userChallenge->progress_percentage}% du défi \"{$challenge->title}\" !",
                    'data' => [
                        'challenge_id' => $challenge->id,
                        'challenge_title' => $challenge->title,
                        'progress_percentage' => $userChallenge->progress_percentage,
                        'current_value' => $userChallenge->current_value,
                        'target_value' => $challenge->target_value,
                    ],
                    'priority' => 'medium',
                    'channels' => ['database', 'pusher'],
                    'scheduled_for' => now(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to send challenge progress notification", [
                'user_id' => $event->user->id,
                'challenge_id' => $event->challenge->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifier les autres participants
     */
    private function notifyOtherParticipants(ChallengeCompleted $event): void
    {
        $challenge = $event->challenge;
        $winner = $event->user;
        $userChallenge = $event->userChallenge;

        $otherParticipants = $challenge->participants()
                                     ->where('user_id', '!=', $winner->id)
                                     ->wherePivot('is_active', true)
                                     ->get();

        foreach ($otherParticipants as $participant) {
            $this->notificationService->create([
                'user_id' => $participant->id,
                'type' => 'challenge_competitor_finished',
                'title' => $userChallenge->is_winner ? '🏆 Défi remporté !' : '✅ Participant terminé',
                'message' => $userChallenge->is_winner 
                    ? "{$winner->name} a remporté le défi \"{$challenge->title}\" !"
                    : "{$winner->name} a terminé le défi \"{$challenge->title}\" ({$userChallenge->rank_display})",
                'data' => [
                    'challenge_id' => $challenge->id,
                    'challenge_title' => $challenge->title,
                    'competitor_name' => $winner->name,
                    'competitor_rank' => $userChallenge->completion_rank,
                    'is_winner' => $userChallenge->is_winner,
                ],
                'priority' => 'medium',
                'channels' => ['database', 'pusher'],
                'scheduled_for' => now(),
            ]);
        }
    }

    /**
     * Notifier les managers
     */
    private function notifyManagers(ChallengeCompleted $event): void
    {
        $user = $event->user;
        $challenge = $event->challenge;
        $userChallenge = $event->userChallenge;

        $managers = \App\Models\User::where('company_id', $challenge->company_id)
                                  ->whereHas('employee', function ($query) use ($user) {
                                      $query->where('service_id', $user->employee?->service_id)
                                            ->whereIn('role', ['manager', 'director']);
                                  })
                                  ->get();

        foreach ($managers as $manager) {
            $this->notificationService->create([
                'user_id' => $manager->id,
                'type' => 'team_challenge_won',
                'title' => '🎯 Défi remporté par votre équipe !',
                'message' => "{$user->name} a remporté le défi \"{$challenge->title}\" !",
                'data' => [
                    'employee_id' => $user->id,
                    'employee_name' => $user->name,
                    'challenge_id' => $challenge->id,
                    'challenge_title' => $challenge->title,
                    'points_earned' => $userChallenge->points_earned,
                    'completion_time' => $userChallenge->time_to_complete,
                ],
                'priority' => 'high',
                'channels' => ['database', 'pusher', 'email'],
                'scheduled_for' => now(),
            ]);
        }
    }

    /**
     * Titre selon le rang de completion
     */
    private function getCompletionTitle($userChallenge): string
    {
        if ($userChallenge->is_winner) {
            return '🏆 Félicitations, vous avez gagné !';
        }
        
        if ($userChallenge->completion_rank <= 3) {
            return "🏅 Excellent ! Vous êtes {$userChallenge->rank_display} !";
        }
        
        return '✅ Défi terminé avec succès !';
    }

    /**
     * Message selon le rang de completion
     */
    private function getCompletionMessage($userChallenge): string
    {
        $challenge = $userChallenge->challenge;
        $message = "Vous avez terminé le défi \"{$challenge->title}\"";
        
        if ($userChallenge->completion_rank) {
            $message .= " en {$userChallenge->rank_display} position";
        }
        
        if ($userChallenge->points_earned > 0) {
            $message .= " et gagné {$userChallenge->points_earned} KaliPoints";
        }
        
        if ($userChallenge->time_to_complete) {
            $message .= " en {$userChallenge->time_to_complete}";
        }
        
        return $message . " !";
    }

    /**
     * Vérifier si c'est un milestone de progression
     */
    private function isProgressMilestone($percentage): bool
    {
        $milestones = [25, 50, 75];
        
        // Vérifier si on vient de dépasser un milestone
        foreach ($milestones as $milestone) {
            if ($percentage >= $milestone && $percentage < $milestone + 5) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Priorité selon le rang
     */
    private function getPriorityByRank($rank): string
    {
        return match(true) {
            $rank === 1 => 'critical',
            $rank <= 3 => 'high',
            $rank <= 10 => 'medium',
            default => 'low'
        };
    }

    /**
     * Canaux selon le rang
     */
    private function getChannelsByRank($rank): array
    {
        return match(true) {
            $rank === 1 => ['database', 'pusher', 'email', 'sms'],
            $rank <= 3 => ['database', 'pusher', 'email'],
            default => ['database', 'pusher']
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error("Challenge notification failed", [
            'event_type' => get_class($event),
            'user_id' => $event->user->id ?? null,
            'challenge_id' => $event->challenge->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}