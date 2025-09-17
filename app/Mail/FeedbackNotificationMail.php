<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Feedback;

class FeedbackNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback;
    public $company;
    public $client;
    public $feedbackType;
    public $isNegative;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
        $this->company = $feedback->company;
        $this->client = $feedback->client;
        $this->feedbackType = $feedback->feedbackType ? $feedback->feedbackType->name : 'Feedback';
        $this->isNegative = $feedback->rating <= 2;
    }

    public function envelope(): Envelope
    {
        $subject = match($this->feedback->type) {
            'suggestion' => "💡 Nouvelle suggestion reçue - {$this->company->name}",
            'negatif' => "🚨 Feedback négatif reçu - {$this->company->name}",
            'incident' => "⚠️ Incident signalé - {$this->company->name}",
            default => "📝 Nouveau feedback - {$this->company->name}"
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        // Choisir le template selon le type de feedback
        $template = match($this->feedback->type) {
            'suggestion' => 'emails.feedback-notification-suggestion',
            'negatif', 'incident' => 'emails.feedback-notification-negative',
            default => 'emails.feedback-notification-suggestion'
        };

        return new Content(
            view: $template,
            with: [
                'feedback' => $this->feedback,
                'company' => $this->company,
                'client' => $this->client,
                'feedbackType' => $this->feedbackType,
                'isNegative' => $this->isNegative,
                'ratingStars' => $this->getRatingStars($this->feedback->rating),
                'urgencyLevel' => $this->getUrgencyLevel($this->feedback->rating),
                'ratingColor' => $this->getRatingColor($this->feedback->rating),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function getFeedbackTypeLabel($type)
    {
        return match($type) {
            'appreciation' => 'Appréciation',
            'incident' => 'Incident',
            'suggestion' => 'Suggestion',
            'negatif' => 'Feedback Négatif',
            default => ucfirst($type)
        };
    }

    private function getRatingStars($rating)
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $stars .= '⭐';
            } else {
                $stars .= '☆';
            }
        }
        return $stars . " ({$rating}/5)";
    }

    private function getRatingColor($rating)
    {
        // Couleurs selon le type de feedback
        if ($this->feedback->type === 'negatif' || $this->feedback->type === 'incident') {
            return '#991B1B'; // Rouge foncé
        } elseif ($this->feedback->type === 'suggestion') {
            return '#2563EB'; // Bleu
        }
        return '#EA580C'; // Orange (couleur de l'app pour appreciation)
    }

    private function getUrgencyLevel($rating)
    {
        // Pour les feedbacks négatifs, plus la note est haute, plus le client est énervé
        if ($this->feedback->type === 'negatif' || $this->feedback->type === 'incident') {
            return match(true) {
                $rating >= 4 => ['level' => 'CRITIQUE', 'color' => '#DC2626', 'bg' => '#FEE2E2'], // 4-5/5 = Très énervé
                $rating == 3 => ['level' => 'URGENT', 'color' => '#EA580C', 'bg' => '#FED7AA'],     // 3/5 = Énervé
                $rating <= 2 => ['level' => 'MODÉRÉ', 'color' => '#D97706', 'bg' => '#FEF3C7'],     // 1-2/5 = Déçu
                default => ['level' => 'NÉGATIF', 'color' => '#DC2626', 'bg' => '#FEE2E2']
            };
        }
        
        // Pour les feedbacks positifs (logique normale)
        return match(true) {
            $rating <= 2 => ['level' => 'FAIBLE', 'color' => '#EF4444', 'bg' => '#FEE2E2'],
            $rating == 3 => ['level' => 'MOYEN', 'color' => '#F59E0B', 'bg' => '#FEF3C7'],
            $rating == 4 => ['level' => 'BON', 'color' => '#10B981', 'bg' => '#D1FAE5'],
            default => ['level' => 'EXCELLENT', 'color' => '#059669', 'bg' => '#ECFDF5']
        };
    }
}