<?php

namespace App\Events;

use App\Models\Escalation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeedbackEscalated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $escalation;

    public function __construct(Escalation $escalation)
    {
        $this->escalation = $escalation->load(['feedback', 'slaRule']);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->escalation->feedback->company_id),
            new PrivateChannel('escalations'),
        ];
    }

    /**
     * Data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'escalation_id' => $this->escalation->id,
            'feedback_id' => $this->escalation->feedback_id,
            'level' => $this->escalation->escalation_level,
            'level_label' => $this->escalation->level_label,
            'trigger_reason' => $this->escalation->trigger_reason,
            'trigger_reason_label' => $this->escalation->trigger_reason_label,
            'feedback' => [
                'id' => $this->escalation->feedback->id,
                'reference' => $this->escalation->feedback->reference,
                'type' => $this->escalation->feedback->type_label,
                'rating' => $this->escalation->feedback->rating,
                'sentiment' => $this->escalation->feedback->sentiment_label,
                'created_at' => $this->escalation->feedback->created_at,
            ],
            'sla_rule' => [
                'name' => $this->escalation->slaRule->name,
                'priority_level' => $this->escalation->slaRule->priority_level,
                'priority_label' => $this->escalation->slaRule->priority_label,
            ],
            'escalated_at' => $this->escalation->escalated_at,
            'company_id' => $this->escalation->feedback->company_id,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'feedback.escalated';
    }
}