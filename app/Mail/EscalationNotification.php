<?php

namespace App\Mail;

use App\Models\Escalation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EscalationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $escalation;
    public $user;
    public $feedback;

    /**
     * Create a new message instance.
     */
    public function __construct(Escalation $escalation, User $user)
    {
        $this->escalation = $escalation;
        $this->user = $user;
        $this->feedback = $escalation->feedback;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $urgencyMap = [
            1 => 'NORMAL',
            2 => 'ÉLEVÉ',
            3 => 'CRITIQUE'
        ];

        $urgency = $urgencyMap[$this->escalation->escalation_level] ?? 'NORMAL';

        return new Envelope(
            subject: "🚨 ESCALADE NIVEAU {$this->escalation->escalation_level} - {$urgency} - Feedback #{$this->feedback->reference}"
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.escalation-notification-v2',
            with: [
                'escalation' => $this->escalation,
                'user' => $this->user,
                'feedback' => $this->feedback,
                'company' => $this->feedback->company,
                'client' => $this->feedback->client,
                'urgencyColor' => $this->getUrgencyColor(),
                'urgencyLabel' => $this->getUrgencyLabel(),
                'actionUrl' => $this->getActionUrl(),
            ]
        );
    }

    /**
     * Get the urgency color for styling
     */
    private function getUrgencyColor(): string
    {
        return match($this->escalation->escalation_level) {
            1 => '#F59E0B', // Orange
            2 => '#EF4444', // Rouge
            3 => '#DC2626', // Rouge foncé
            default => '#6B7280'
        };
    }

    /**
     * Get the urgency label
     */
    private function getUrgencyLabel(): string
    {
        return match($this->escalation->escalation_level) {
            1 => 'ESCALADE MANAGER',
            2 => 'ESCALADE DIRECTION',
            3 => 'ESCALADE PDG',
            default => 'ESCALADE'
        };
    }

    /**
     * Get the action URL for the feedback
     */
    private function getActionUrl(): string
    {
        // URL vers le dashboard pour traiter le feedback
        $baseUrl = config('app.url', 'https://app.qualywatch.com');
        return "{$baseUrl}/dashboard/feedback/{$this->feedback->id}";
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}