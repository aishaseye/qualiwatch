<?php

namespace App\Jobs;

use App\Models\Feedback;
use App\Services\RealTimeNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendValidationReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $feedback;
    public $tries = 3;
    public $timeout = 60;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
    }

    public function handle()
    {
        try {
            // Vérifier si la validation est encore nécessaire
            if ($this->feedback->client_validated || 
                $this->feedback->is_validation_expired || 
                !$this->feedback->validation_token) {
                Log::info("Rappel de validation annulé - Feedback {$this->feedback->id} déjà traité");
                return;
            }

            // Envoyer email de rappel
            Mail::to($this->feedback->client->email)->send(
                new \App\Mail\ValidationReminderMail($this->feedback)
            );

            // Notification temps réel
            $notificationService = app(RealTimeNotificationService::class);
            
            broadcast(new \Illuminate\Broadcasting\InteractsWithBroadcasting)->toOthers()
                ->toPrivate('company.' . $this->feedback->company_id)
                ->event('validation.reminder_sent')
                ->with([
                    'feedback_id' => $this->feedback->id,
                    'reference' => $this->feedback->reference,
                    'client_name' => $this->feedback->client->name,
                    'sent_at' => now(),
                    'expires_at' => $this->feedback->validation_expires_at,
                ]);

            // Mettre à jour la date du dernier rappel
            $this->feedback->update([
                'validation_reminded_at' => now()
            ]);

            Log::info("Rappel de validation envoyé pour le feedback {$this->feedback->id}");

        } catch (\Exception $e) {
            Log::error("Erreur envoi rappel validation : " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("Job SendValidationReminderJob échoué pour feedback {$this->feedback->id} : " . $exception->getMessage());
    }
}