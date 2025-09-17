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

class ChallengeProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userChallenge;
    public $user;
    public $challenge;

    public function __construct(UserChallenge $userChallenge)
    {
        $this->userChallenge = $userChallenge->load(['user', 'challenge']);
        $this->user = $this->userChallenge->user;
        $this->challenge = $this->userChallenge->challenge;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->user->id}"),
            new PrivateChannel("challenge.{$this->challenge->id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'challenge.progress.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->userChallenge->id,
            'user_id' => $this->user->id,
            'challenge_id' => $this->challenge->id,
            'progress' => [
                'current_value' => $this->userChallenge->current_value,
                'progress_percentage' => $this->userChallenge->progress_percentage,
                'progress_status' => $this->userChallenge->progress_status,
                'progress_status_label' => $this->userChallenge->progress_status_label,
                'progress_status_color' => $this->userChallenge->progress_status_color,
                'last_updated_at' => $this->userChallenge->last_updated_at?->toISOString(),
            ],
            'challenge' => [
                'title' => $this->challenge->title,
                'target_value' => $this->challenge->target_value,
                'target_formatted' => $this->challenge->target_formatted,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Determine if this event should queue
     */
    public function shouldQueue(): bool
    {
        return false; // Progress updates should be immediate
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'gamification',
            'challenge-progress',
            "user:{$this->user->id}",
            "challenge:{$this->challenge->id}"
        ];
    }
}