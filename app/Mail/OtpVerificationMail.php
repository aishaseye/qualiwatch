<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\UserOtp;

class OtpVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $userName;
    public $expiresAt;

    public function __construct(UserOtp $otpRecord, $userName = null)
    {
        $this->otp = $otpRecord->otp;
        $this->userName = $userName ?? 'Utilisateur';
        $this->expiresAt = $otpRecord->expires_at->format('H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vérification de votre adresse email - QualyWatch',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp-verification',
            with: [
                'otp' => $this->otp,
                'userName' => $this->userName,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}