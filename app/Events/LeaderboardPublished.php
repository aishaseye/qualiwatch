<?php

namespace App\Events;

use App\Models\Leaderboard;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaderboardPublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $leaderboard;
    public $user;
    public $company;

    public function __construct(Leaderboard $leaderboard)
    {
        $this->leaderboard = $leaderboard->load(['user', 'company', 'service']);
        $this->user = $this->leaderboard->user;
        $this->company = $this->leaderboard->company;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->user->id}"),
            new PrivateChannel("company.{$this->company->id}.leaderboard"),
            new Channel("public.rankings.{$this->company->id}")
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'leaderboard.published';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->leaderboard->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar_url,
            ],
            'ranking' => [
                'period_type' => $this->leaderboard->period_type,
                'period_label' => $this->leaderboard->period_label,
                'metric_type' => $this->leaderboard->metric_type,
                'metric_label' => $this->leaderboard->metric_label,
                'rank_overall' => $this->leaderboard->rank_overall,
                'rank_display' => $this->leaderboard->rank_display,
                'score' => $this->leaderboard->score_formatted,
                'points_earned' => $this->leaderboard->points_earned,
                'is_winner' => $this->leaderboard->is_winner,
                'podium_position' => $this->leaderboard->podium_position,
                'podium_color' => $this->leaderboard->podium_color,
                'improvement_percentage' => $this->leaderboard->improvement_percentage,
                'improvement_icon' => $this->leaderboard->improvement_icon,
                'improvement_color' => $this->leaderboard->improvement_color,
            ],
            'company_id' => $this->company->id,
            'published_at' => $this->leaderboard->published_at->toISOString(),
            'message' => $this->getRankingMessage(),
        ];
    }

    /**
     * Get ranking message
     */
    private function getRankingMessage(): string
    {
        if ($this->leaderboard->is_winner) {
            return "🎉 {$this->user->name} remporte le classement {$this->leaderboard->metric_label} {$this->leaderboard->period_label} !";
        }
        
        if ($this->leaderboard->podium_position <= 3) {
            return "🏅 {$this->user->name} termine {$this->leaderboard->rank_display} au classement {$this->leaderboard->metric_label} {$this->leaderboard->period_label}";
        }
        
        return "📊 Nouveau classement {$this->leaderboard->metric_label} publié - {$this->user->name} se classe {$this->leaderboard->rank_display}";
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
            'leaderboard-published',
            "user:{$this->user->id}",
            "company:{$this->company->id}"
        ];
    }
}