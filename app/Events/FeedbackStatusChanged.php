<?php

namespace App\Events;

use App\Models\Feedback;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeedbackStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $feedback;
    public $oldStatus;
    public $newStatus;
    public $changedBy;

    public function __construct(Feedback $feedback, $oldStatus, $newStatus, $changedBy = null)
    {
        $this->feedback = $feedback->load([
            'client', 'employee', 'service', 'company', 
            'feedbackType', 'feedbackStatus'
        ]);
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Canal de l'entreprise
            new PrivateChannel('company.' . $this->feedback->company_id),
            
            // Canal du client pour les mises à jour
            new PrivateChannel('client.' . $this->feedback->client_id),
            
            // Canal du service
            new PrivateChannel('service.' . $this->feedback->service_id),
            
            // Canal pour le suivi admin
            new PrivateChannel('admin.feedback-tracking'),
        ];
    }

    /**
     * Data to broadcast
     */
    public function broadcastWith(): array
    {
        $isResolved = in_array($this->newStatus, ['resolved', 'implemented']);
        $requiresClientValidation = in_array($this->newStatus, ['treated']) && 
                                   $this->feedback->requires_validation;

        return [
            'feedback_id' => $this->feedback->id,
            'reference' => $this->feedback->reference,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'new_status_label' => $this->feedback->status_label_new,
            'new_status_color' => $this->feedback->status_color_new,
            'is_resolved' => $isResolved,
            'requires_client_validation' => $requiresClientValidation,
            'changed_by' => $this->changedBy ? [
                'id' => $this->changedBy->id,
                'name' => $this->changedBy->name,
                'role' => $this->changedBy->role,
            ] : null,
            'feedback' => [
                'id' => $this->feedback->id,
                'type' => $this->feedback->type_label,
                'priority_level' => $this->feedback->priority_level,
                'escalation_status' => $this->feedback->escalation_status,
            ],
            'client' => [
                'id' => $this->feedback->client_id,
                'name' => $this->feedback->client?->name,
            ],
            'notification' => [
                'type' => 'status_changed',
                'title' => 'Statut mis à jour',
                'message' => "Le feedback #{$this->feedback->reference} est maintenant : {$this->feedback->status_label_new}",
                'icon' => 'refresh',
                'urgency' => $isResolved ? 'success' : 'normal',
                'timestamp' => now(),
                'action_required' => $requiresClientValidation,
            ]
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'feedback.status_changed';
    }
}