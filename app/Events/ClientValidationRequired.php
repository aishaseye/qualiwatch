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

class ClientValidationRequired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $feedback;
    public $validationToken;

    public function __construct(Feedback $feedback, $validationToken = null)
    {
        $this->feedback = $feedback->load(['client', 'company', 'feedbackType']);
        $this->validationToken = $validationToken ?? $feedback->validation_token;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Canal public pour le client (pas besoin d'auth)
            new Channel('client-validation.' . $this->validationToken),
            
            // Canal privé pour le suivi admin
            new PrivateChannel('company.' . $this->feedback->company_id),
            
            // Canal de notification client
            new PrivateChannel('client.' . $this->feedback->client_id),
        ];
    }

    /**
     * Data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'feedback_id' => $this->feedback->id,
            'reference' => $this->feedback->reference,
            'validation_token' => $this->validationToken,
            'validation_url' => $this->feedback->getValidationUrl(),
            'expires_at' => $this->feedback->validation_expires_at,
            'type' => $this->feedback->type_label,
            'type_color' => $this->feedback->feedbackType?->color,
            'client' => [
                'name' => $this->feedback->client?->name,
                'email' => $this->feedback->client?->email,
            ],
            'company' => [
                'name' => $this->feedback->company?->name,
            ],
            'notification' => [
                'type' => 'validation_required',
                'title' => 'Validation client requise',
                'message' => "Le client {$this->feedback->client?->name} doit valider la résolution du feedback #{$this->feedback->reference}",
                'icon' => 'shield-check',
                'urgency' => 'normal',
                'timestamp' => now(),
                'expires_in_hours' => 48,
            ]
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'client.validation_required';
    }
}