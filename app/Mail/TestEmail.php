<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $testData;

    public function __construct($testData)
    {
        $this->testData = $testData;
    }

    public function build()
    {
        $template = $this->testData['template'] ?? 'feedback-validation-v2';
        $subject = isset($this->testData['template']) && $this->testData['template'] === 'suggestion-thank-you-simple' 
            ? '💡 Merci pour votre suggestion - QualyWatch'
            : '🧪 Test Email - Gradient Orange QualyWatch';
            
        return $this->subject($subject)
                    ->view('emails.' . $template)
                    ->with($this->testData);
    }
}