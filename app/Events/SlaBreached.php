<?php

namespace App\Events;

use App\Models\Feedback;
use App\Models\SlaRule;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlaBreached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $feedback;
    public $slaRule;
    public $breachType; // 'first_response' or 'resolution'

    public function __construct(Feedback $feedback, SlaRule $slaRule, string $breachType)
    {
        $this->feedback = $feedback->load(['client', 'employee', 'feedbackType', 'feedbackStatus']);
        $this->slaRule = $slaRule;
        $this->breachType = $breachType;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->feedback->company_id),
            new PrivateChannel('sla-breaches'),
        ];
    }

    /**
     * Data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'feedback_id' => $this->feedback->id,
            'feedback_reference' => $this->feedback->reference,
            'breach_type' => $this->breachType,
            'breach_label' => $this->breachType === 'first_response' ? 'Première réponse' : 'Résolution',
            'sla_rule' => [
                'name' => $this->slaRule->name,
                'priority_level' => $this->slaRule->priority_level,
                'priority_label' => $this->slaRule->priority_label,
            ],
            'feedback' => [
                'id' => $this->feedback->id,
                'reference' => $this->feedback->reference,
                'type' => $this->feedback->type_label,
                'rating' => $this->feedback->rating,
                'sentiment' => $this->feedback->sentiment_label,
                'created_at' => $this->feedback->created_at,
                'client' => $this->feedback->client?->name,
                'employee' => $this->feedback->employee?->name,
            ],
            'company_id' => $this->feedback->company_id,
            'breached_at' => now(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sla.breached';
    }
}