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

class FeedbackCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $feedback;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback->load([
            'client', 'employee', 'service', 'company', 
            'feedbackType', 'feedbackStatus'
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Canal principal de l'entreprise
            new PrivateChannel('company.' . $this->feedback->company_id),
            
            // Canal du service concerné
            new PrivateChannel('service.' . $this->feedback->service_id),
            
            // Canal global admin
            new PrivateChannel('admin.notifications'),
            
            // Canal pour les managers
            new PrivateChannel('managers.' . $this->feedback->company_id),
        ];
    }

    /**
     * Data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'feedback' => [
                'id' => $this->feedback->id,
                'reference' => $this->feedback->reference,
                'type' => $this->feedback->type_label,
                'type_color' => $this->feedback->type_color_new,
                'status' => $this->feedback->status_label_new,
                'status_color' => $this->feedback->status_color_new,
                'rating' => $this->feedback->rating,
                'sentiment' => $this->feedback->sentiment_label,
                'sentiment_color' => $this->feedback->sentiment_color,
                'priority_level' => $this->feedback->priority_level,
                'priority_label' => $this->feedback->priority_label,
                'priority_color' => $this->feedback->priority_color,
                'title' => $this->feedback->title,
                'description' => $this->feedback->description,
                'has_media' => $this->feedback->has_media,
                'media_type' => $this->feedback->media_type,
                'created_at' => $this->feedback->created_at,
                'escalation_status' => $this->feedback->escalation_status,
                'escalation_color' => $this->feedback->escalation_color,
            ],
            'client' => [
                'name' => $this->feedback->client?->name,
                'email' => $this->feedback->client?->email,
                'is_recurrent' => $this->feedback->client?->is_recurrent ?? false,
            ],
            'employee' => [
                'name' => $this->feedback->employee?->name,
                'service' => $this->feedback->service?->name,
            ],
            'company' => [
                'id' => $this->feedback->company_id,
                'name' => $this->feedback->company?->name,
            ],
            'notification' => [
                'type' => 'feedback_created',
                'title' => 'Nouveau feedback reçu',
                'message' => "Nouveau feedback {$this->feedback->type_label} de {$this->feedback->client?->name}",
                'icon' => $this->feedback->feedbackType?->icon ?? 'chat',
                'urgency' => $this->feedback->priority_level >= 4 ? 'high' : 'normal',
                'timestamp' => now(),
            ]
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'feedback.created';
    }

    /**
     * Determine if this event should broadcast
     */
    public function broadcastWhen(): bool
    {
        return $this->feedback->company_id !== null;
    }
}