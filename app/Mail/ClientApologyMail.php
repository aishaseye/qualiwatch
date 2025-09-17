<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Feedback;

class ClientApologyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback;
    public $company;
    public $client;
    public $apologyLevel;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
        $this->company = $feedback->company;
        $this->client = $feedback->client;
        $this->apologyLevel = $this->getApologyLevel($feedback->rating);
    }

    public function envelope(): Envelope
    {
        $subject = $this->getSubjectByType($this->feedback->type);

        return new Envelope(
            subject: $subject . " - {$this->company->name}",
        );
    }

    private function getSubjectByType($type)
    {
        return match($type) {
            'suggestion' => 'Votre suggestion a été implémentée',
            'incident' => 'Votre incident a été traité',
            'negatif' => 'Votre retour a été pris en compte',
            default => 'Nos excuses pour votre expérience'
        };
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client-apology',
            with: [
                'feedback' => $this->feedback,
                'company' => $this->company,
                'client' => $this->client,
                'ratingStars' => $this->getRatingStars($this->feedback->rating),
                'clientName' => $this->client->full_name ?? 'Cher client',
                'apologyLevel' => $this->apologyLevel,
                'ratingColor' => $this->getRatingColor($this->feedback->rating),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
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
        return $stars;
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

    private function getApologyLevel($rating)
    {
        if ($rating >= 4) {
            return [
                'intensity' => 'TRES ELEVEE',
                'title' => 'Nos excuses les plus sinceres',
                'message' => 'Nous comprenons parfaitement votre frustration et nous en assumons l entiere responsabilite.',
                'urgency' => 'Contact prioritaire dans l heure qui suit',
                'color' => '#DC2626'
            ];
        } elseif ($rating == 3) {
            return [
                'intensity' => 'ELEVEE', 
                'title' => 'Nos sinceres excuses',
                'message' => 'Nous regrettons sincerement cette experience decevante de votre part.',
                'urgency' => 'Contact dans les 2 heures',
                'color' => '#EA580C'
            ];
        } else {
            return [
                'intensity' => 'STANDARD',
                'title' => 'Nos excuses pour cette experience',
                'message' => 'Nous sommes desoles que votre experience n ait pas ete a la hauteur de vos attentes.',
                'urgency' => 'Contact dans les 24 heures',
                'color' => '#D97706'
            ];
        }
    }
}