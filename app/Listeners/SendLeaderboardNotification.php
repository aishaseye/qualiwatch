<?php

namespace App\Listeners;

use App\Events\LeaderboardUpdated;
use App\Events\LeaderboardPublished;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendLeaderboardNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the LeaderboardUpdated event.
     */
    public function handle(LeaderboardUpdated $event): void
    {
        try {
            // Notifier tous les participants des nouveaux classements
            $this->notifyAllParticipants($event);
            
            // Notifier spécialement les gagnants et podium
            $this->notifyTopPerformers($event);
            
            Log::info("Leaderboard updated notifications sent", [
                'company_id' => $event->companyId,
                'period_type' => $event->periodType,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send leaderboard notifications", [
                'company_id' => $event->companyId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the LeaderboardPublished event.
     */
    public function handlePublished(LeaderboardPublished $event): void
    {
        try {
            $leaderboard = $event->leaderboard;
            $user = $event->user;

            // Notification personnelle à l'utilisateur
            $this->notificationService->create([
                'user_id' => $user->id,
                'type' => 'leaderboard_published',
                'title' => $this->getPersonalTitle($leaderboard),
                'message' => $this->getPersonalMessage($leaderboard),
                'data' => [
                    'leaderboard_id' => $leaderboard->id,
                    'metric_type' => $leaderboard->metric_type,
                    'metric_label' => $leaderboard->metric_label,
                    'rank_overall' => $leaderboard->rank_overall,
                    'score' => $leaderboard->score,
                    'points_earned' => $leaderboard->points_earned,
                    'is_winner' => $leaderboard->is_winner,
                    'podium_position' => $leaderboard->podium_position,
                ],
                'priority' => $this->getPriorityByRank($leaderboard->rank_overall),
                'channels' => $this->getChannelsByRank($leaderboard->rank_overall),
                'scheduled_for' => now(),
            ]);

            Log::info("Leaderboard published notification sent", [
                'user_id' => $user->id,
                'rank' => $leaderboard->rank_overall,
                'metric' => $leaderboard->metric_type,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send leaderboard published notification", [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifier tous les participants
     */
    private function notifyAllParticipants(LeaderboardUpdated $event): void
    {
        $participants = \App\Models\User::where('company_id', $event->companyId)
                                      ->whereHas('employee')
                                      ->get();

        foreach ($participants as $user) {
            $this->notificationService->create([
                'user_id' => $user->id,
                'type' => 'leaderboard_updated',
                'title' => '📊 Classements mis à jour',
                'message' => "Les classements {$event->periodType} sont maintenant disponibles !",
                'data' => [
                    'period_type' => $event->periodType,
                    'period_date' => $event->periodDate,
                    'metrics_available' => array_keys($event->metrics ?? []),
                ],
                'priority' => 'medium',
                'channels' => ['database', 'pusher'],
                'scheduled_for' => now(),
            ]);
        }
    }

    /**
     * Notifier spécialement les top performers
     */
    private function notifyTopPerformers(LeaderboardUpdated $event): void
    {
        foreach ($event->topPerformers ?? [] as $performer) {
            $this->notificationService->create([
                'user_id' => $performer['user']['id'],
                'type' => 'top_performer',
                'title' => '🏆 Excellent résultat !',
                'message' => "Félicitations ! Vous êtes {$performer['rank']} en {$performer['metric']} !",
                'data' => [
                    'metric' => $performer['metric'],
                    'rank' => $performer['rank'],
                    'score' => $performer['score'],
                    'points' => $performer['points'],
                ],
                'priority' => 'high',
                'channels' => ['database', 'pusher', 'email'],
                'scheduled_for' => now(),
            ]);
        }
    }

    /**
     * Titre personnel selon le rang
     */
    private function getPersonalTitle($leaderboard): string
    {
        if ($leaderboard->is_winner) {
            return '🏆 Bravo, vous êtes Premier !';
        }
        
        if ($leaderboard->podium_position <= 3) {
            return "🏅 Félicitations pour votre {$leaderboard->rank_display} place !";
        }
        
        if ($leaderboard->rank_overall <= 10) {
            return '⭐ Excellent classement !';
        }
        
        return '📈 Votre classement est disponible';
    }

    /**
     * Message personnel selon le rang
     */
    private function getPersonalMessage($leaderboard): string
    {
        $baseMessage = "Votre classement {$leaderboard->metric_label} {$leaderboard->period_label} : {$leaderboard->rank_display}";
        
        if ($leaderboard->points_earned > 0) {
            $baseMessage .= " (+{$leaderboard->points_earned} KaliPoints)";
        }
        
        if ($leaderboard->improvement_percentage > 0) {
            $baseMessage .= " 📈 Amélioration de {$leaderboard->improvement_percentage}% !";
        } elseif ($leaderboard->improvement_percentage < 0) {
            $baseMessage .= " 📉 Baisse de " . abs($leaderboard->improvement_percentage) . "%";
        }
        
        return $baseMessage;
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
            $rank <= 10 => ['database', 'pusher'],
            default => ['database']
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error("Leaderboard notification failed", [
            'event_type' => get_class($event),
            'error' => $exception->getMessage(),
        ]);
    }
}