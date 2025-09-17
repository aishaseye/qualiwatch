<?php

namespace App\Listeners;

use App\Events\BadgeEarned;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendBadgeEarnedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(BadgeEarned $event): void
    {
        try {
            $userBadge = $event->userBadge;
            $user = $event->user;
            $badge = $event->badge;
            $company = $event->company;

            // Notification à l'utilisateur
            $this->notificationService->create([
                'user_id' => $user->id,
                'type' => 'badge_earned',
                'title' => '🏅 Nouveau badge obtenu !',
                'message' => "Félicitations ! Vous avez obtenu le badge \"{$badge->title}\"",
                'data' => [
                    'badge_id' => $badge->id,
                    'badge_title' => $badge->title,
                    'badge_description' => $badge->description,
                    'badge_icon' => $badge->icon,
                    'badge_color' => $badge->color,
                    'badge_rarity' => $badge->rarity,
                    'points_earned' => $userBadge->points_earned,
                    'achievement_data' => $userBadge->achievement_data,
                ],
                'priority' => $this->getPriorityByRarity($badge->rarity),
                'channels' => ['database', 'pusher', 'email'],
                'scheduled_for' => now(),
            ]);

            // Notification aux managers si badge rare ou légendaire
            if (in_array($badge->rarity, ['epic', 'legendary'])) {
                $this->notifyManagers($user, $badge, $userBadge, $company);
            }

            // Notification équipe si badge de collaboration
            if ($badge->category === 'teamwork') {
                $this->notifyTeamMembers($user, $badge, $userBadge, $company);
            }

            // Mettre à jour les statistiques de gamification
            $this->updateGamificationStats($company, $badge);

            Log::info("Badge earned notification sent", [
                'user_id' => $user->id,
                'badge_id' => $badge->id,
                'rarity' => $badge->rarity,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send badge earned notification", [
                'user_id' => $event->user->id,
                'badge_id' => $event->badge->id,
                'error' => $e->getMessage(),
            ]);
            
            // Re-throw si c'est critique
            if ($event->badge->rarity === 'legendary') {
                throw $e;
            }
        }
    }

    /**
     * Notifier les managers pour les badges rares
     */
    private function notifyManagers($user, $badge, $userBadge, $company): void
    {
        $managers = $this->getManagers($user, $company);

        foreach ($managers as $manager) {
            $this->notificationService->create([
                'user_id' => $manager->id,
                'type' => 'team_badge_earned',
                'title' => "🌟 Badge {$badge->rarity_label} obtenu !",
                'message' => "{$user->name} a obtenu le badge {$badge->rarity_label} \"{$badge->title}\"",
                'data' => [
                    'employee_id' => $user->id,
                    'employee_name' => $user->name,
                    'badge_id' => $badge->id,
                    'badge_title' => $badge->title,
                    'badge_rarity' => $badge->rarity,
                    'points_earned' => $userBadge->points_earned,
                ],
                'priority' => 'high',
                'channels' => ['database', 'pusher'],
                'scheduled_for' => now(),
            ]);
        }
    }

    /**
     * Notifier les membres de l'équipe
     */
    private function notifyTeamMembers($user, $badge, $userBadge, $company): void
    {
        $teamMembers = $this->getTeamMembers($user, $company);

        foreach ($teamMembers as $member) {
            if ($member->id === $user->id) continue; // Skip self

            $this->notificationService->create([
                'user_id' => $member->id,
                'type' => 'team_achievement',
                'title' => '👥 Réussite d\'équipe !',
                'message' => "{$user->name} a obtenu le badge \"{$badge->title}\" pour l'esprit d'équipe",
                'data' => [
                    'teammate_id' => $user->id,
                    'teammate_name' => $user->name,
                    'badge_id' => $badge->id,
                    'badge_title' => $badge->title,
                ],
                'priority' => 'medium',
                'channels' => ['database', 'pusher'],
                'scheduled_for' => now(),
            ]);
        }
    }

    /**
     * Obtenir les managers
     */
    private function getManagers($user, $company)
    {
        // Récupérer les managers du service de l'utilisateur
        return \App\Models\User::where('company_id', $company->id)
                              ->whereHas('employee', function ($query) use ($user) {
                                  $query->where('service_id', $user->employee?->service_id)
                                        ->where('role', 'manager');
                              })
                              ->get();
    }

    /**
     * Obtenir les membres de l'équipe
     */
    private function getTeamMembers($user, $company)
    {
        // Récupérer les collègues du même service
        return \App\Models\User::where('company_id', $company->id)
                              ->whereHas('employee', function ($query) use ($user) {
                                  $query->where('service_id', $user->employee?->service_id);
                              })
                              ->limit(10) // Limiter pour éviter le spam
                              ->get();
    }

    /**
     * Mettre à jour les stats de gamification
     */
    private function updateGamificationStats($company, $badge): void
    {
        // Incrémenter le compteur de badges par catégorie
        $company->increment("badges_{$badge->category}_count");
        $company->increment("badges_{$badge->rarity}_count");
        $company->increment('total_badges_earned');
    }

    /**
     * Obtenir la priorité selon la rareté
     */
    private function getPriorityByRarity($rarity): string
    {
        return match($rarity) {
            'legendary' => 'critical',
            'epic' => 'high',
            'rare' => 'medium',
            default => 'low'
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed(BadgeEarned $event, \Throwable $exception): void
    {
        Log::error("Badge earned notification failed", [
            'user_id' => $event->user->id,
            'badge_id' => $event->badge->id,
            'error' => $exception->getMessage(),
        ]);

        // Essayer d'envoyer une notification minimale
        try {
            Notification::create([
                'user_id' => $event->user->id,
                'type' => 'badge_earned',
                'title' => 'Badge obtenu',
                'message' => "Vous avez obtenu un nouveau badge !",
                'data' => [
                    'badge_id' => $event->badge->id,
                ],
                'read_at' => null,
            ]);
        } catch (\Exception $e) {
            Log::critical("Failed to create fallback badge notification", [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}