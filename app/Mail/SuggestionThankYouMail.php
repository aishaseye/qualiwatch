<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Feedback;

class SuggestionThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback;
    public $company;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
        $this->company = $feedback->company;
    }

    public function build()
    {
        return $this->subject("💡 Merci pour votre suggestion - {$this->company->name}")
                    ->view('emails.client-suggestion-v2')
                    ->with([
                        'client_name' => $this->feedback->client ? $this->feedback->client->full_name : 'Client',
                        'company_name' => $this->company->name,
                        'feedback_reference' => $this->feedback->reference ?? $this->feedback->id,
                        'rating' => $this->feedback->rating,
                        'description' => $this->feedback->description,
                        'created_at' => $this->feedback->created_at->format('d/m/Y à H:i'),
                    ]);
    }
}