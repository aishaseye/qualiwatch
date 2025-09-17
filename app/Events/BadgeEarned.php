<?php

namespace App\Events;

use App\Models\UserBadge;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BadgeEarned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userBadge;
    public $user;
    public $badge;
    public $company;

    public function __construct(UserBadge $userBadge)
    {
        $this->userBadge = $userBadge->load(['user', 'badge', 'company']);
        $this->user = $this->userBadge->user;
        $this->badge = $this->userBadge->badge;
        $this->company = $this->userBadge->company;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->user->id}"),
            new PrivateChannel("company.{$this->company->id}.gamification"),
            new Channel("public.achievements.{$this->company->id}")
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'badge.earned';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->userBadge->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar_url,
            ],
            'badge' => [
                'id' => $this->badge->id,
                'title' => $this->badge->title,
                'description' => $this->badge->description,
                'icon' => $this->badge->icon,
                'color' => $this->badge->color,
                'rarity' => $this->badge->rarity,
                'rarity_label' => $this->badge->rarity_label,
                'rarity_color' => $this->badge->rarity_color,
                'category' => $this->badge->category,
                'category_label' => $this->badge->category_label,
                'points_reward' => $this->badge->points_reward,
            ],
            'achievement' => [
                'earned_date' => $this->userBadge->earned_date->format('Y-m-d'),
                'points_earned' => $this->userBadge->points_earned,
                'achievement_score' => $this->userBadge->achievement_score,
                'rank_position' => $this->userBadge->rank_position,
                'period' => $this->userBadge->period,
            ],
            'company_id' => $this->company->id,
            'timestamp' => now()->toISOString(),
            'message' => $this->getShareableMessage(),
        ];
    }

    /**
     * Get shareable message for the badge
     */
    private function getShareableMessage(): string
    {
        $rarity = match($this->badge->rarity) {
            'legendary' => '🏆',
            'epic' => '💜',
            'rare' => '💎',
            'uncommon' => '✨',
            default => '🏅'
        };

        return "{$rarity} {$this->user->name} vient d'obtenir le badge \"{$this->badge->title}\" ! {$this->badge->description}";
    }

    /**
     * Determine if this event should queue
     */
    public function shouldQueue(): bool
    {
        return true;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'gamification',
            'badge-earned',
            "user:{$this->user->id}",
            "company:{$this->company->id}",
            "badge:{$this->badge->id}"
        ];
    }
}