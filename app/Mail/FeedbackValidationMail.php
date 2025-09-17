<?php

namespace App\Mail;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FeedbackValidationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback;
    public $isReminder;

    /**
     * Create a new message instance.
     */
    public function __construct(Feedback $feedback, $isReminder = false)
    {
        $this->feedback = $feedback;
        $this->isReminder = $isReminder;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->isReminder
            ? 'Rappel: Validez votre ' . ($this->feedback->type === 'incident' ? 'incident' : 'suggestion')
            : 'Votre ' . ($this->feedback->type === 'incident' ? 'incident a été traité' : 'suggestion a été implémentée');

        // Récupérer les statuts "resolved" et "not_resolved"
        $resolvedStatus = \App\Models\FeedbackStatus::where('name', 'resolved')->first();
        $notResolvedStatus = \App\Models\FeedbackStatus::where('name', 'not_resolved')->first();

        // Construire les URLs avec les status_id corrects
        $baseUrl = config('app.url') . '/api/validate/' . $this->feedback->validation_token;
        $resolvedUrl = $baseUrl . '?status_id=' . ($resolvedStatus ? $resolvedStatus->id : '');
        $notResolvedUrl = $baseUrl . '?status_id=' . ($notResolvedStatus ? $notResolvedStatus->id : '');

        // Choisir le template selon si c'est un rappel ou pas
        $template = $this->isReminder ? 'emails.feedback-validation-reminder' : 'emails.feedback-validation-v2';

        return $this->subject($subject)
                    ->view($template)
                    ->with([
                        'feedback' => $this->feedback,
                        'company' => $this->feedback->company,
                        'client' => $this->feedback->client,
                        'validationUrl' => $this->feedback->getValidationUrl(),
                        'isReminder' => $this->isReminder,
                        'expiresAt' => $this->feedback->validation_expires_at,
                        'hoursRemaining' => $this->feedback->validation_expires_at->diffInHours(now()),
                        // Variables pour le template
                        'client_name' => $this->feedback->client->full_name ?? 'Client',
                        'company_name' => $this->feedback->company->name,
                        'feedback_reference' => $this->feedback->reference ?? $this->feedback->id,
                        'feedback_title' => $this->feedback->title,
                        'feedback_type' => $this->feedback->type,
                        'admin_resolution' => $this->feedback->admin_resolution_description ?? 'Nous avons traité votre demande.',
                        'expires_at' => $this->feedback->validation_expires_at->format('d/m/Y à H:i'),
                        'resolved_url' => $resolvedUrl,
                        'not_resolved_url' => $notResolvedUrl,
                        'company_phone' => $this->feedback->company->phone ?? '',
                    ]);
    }
}