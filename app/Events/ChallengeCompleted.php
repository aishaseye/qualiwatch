<?php

namespace App\Events;

use App\Models\UserChallenge;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChallengeCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userChallenge;
    public $user;
    public $challenge;
    public $company;

    public function __construct(UserChallenge $userChallenge)
    {
        $this->userChallenge = $userChallenge->load(['user', 'challenge', 'challenge.company']);
        $this->user = $this->userChallenge->user;
        $this->challenge = $this->userChallenge->challenge;
        $this->company = $this->challenge->company;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->user->id}"),
            new PrivateChannel("company.{$this->company->id}.challenges"),
            new PrivateChannel("challenge.{$this->challenge->id}"),
            new Channel("public.achievements.{$this->company->id}")
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'challenge.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->userChallenge->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar_url,
            ],
            'challenge' => [
                'id' => $this->challenge->id,
                'title' => $this->challenge->title,
                'description' => $this->challenge->description,
                'icon' => $this->challenge->icon,
                'color' => $this->challenge->color,
                'type' => $this->challenge->type,
                'type_label' => $this->challenge->type_label,
                'category' => $this->challenge->category,
                'category_label' => $this->challenge->category_label,
                'target_formatted' => $this->challenge->target_formatted,
            ],
            'completion' => [
                'completed_at' => $this->userChallenge->completed_at->toISOString(),
                'completion_rank' => $this->userChallenge->completion_rank,
                'rank_display' => $this->userChallenge->rank_display,
                'points_earned' => $this->userChallenge->points_earned,
                'is_winner' => $this->userChallenge->is_winner,
                'final_score' => $this->userChallenge->final_score,
                'time_to_complete' => $this->userChallenge->time_to_complete,
                'progress_percentage' => $this->userChallenge->progress_percentage,
            ],
            'rewards' => $this->userChallenge->rewards_earned ?? [],
            'company_id' => $this->company->id,
            'timestamp' => now()->toISOString(),
            'message' => $this->getCompletionMessage(),
        ];
    }

    /**
     * Get completion message
     */
    private function getCompletionMessage(): string
    {
        $icon = $this->userChallenge->is_winner ? '🏆' : '✅';
        $achievement = $this->userChallenge->is_winner ? 'a remporté' : 'a terminé';
        
        $message = "{$icon} {$this->user->name} {$achievement} le défi \"{$this->challenge->title}\"";
        
        if ($this->userChallenge->completion_rank <= 3) {
            $message .= " ({$this->userChallenge->rank_display})";
        }
        
        if ($this->userChallenge->points_earned > 0) {
            $message .= " et gagne {$this->userChallenge->points_earned} KaliPoints !";
        }
        
        return $message;
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
            'challenge-completed',
            "user:{$this->user->id}",
            "company:{$this->company->id}",
            "challenge:{$this->challenge->id}"
        ];
    }
}