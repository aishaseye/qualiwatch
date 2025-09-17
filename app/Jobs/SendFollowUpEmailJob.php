<?php

namespace App\Jobs;

use App\Models\Feedback;
use App\Mail\FeedbackValidationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendFollowUpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $feedback;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
    }

    public function handle()
    {
        // Vérifier si le feedback n'est toujours pas résolu et si le client n'a pas encore validé
        // ET que le client n'a pas cliqué sur "non résolu"
        if ($this->feedback->status === 'treated' &&
            !$this->feedback->client_validated &&
            $this->feedback->status !== 'not_resolved' &&
            $this->isWithinOneWeekLimit() &&
            ($this->feedback->type === 'negatif' || $this->feedback->type === 'incident')) {

            try {
                // Envoyer un email de relance
                Mail::to($this->feedback->client->email)->send(
                    new FeedbackValidationMail($this->feedback, true) // true = isReminder
                );

                Log::info('Email de relance envoyé', [
                    'feedback_id' => $this->feedback->id,
                    'client_email' => $this->feedback->client->email
                ]);

                // Programmer une nouvelle relance dans 24h si toujours pas résolu
                $this->scheduleNextFollowUp();

            } catch (\Exception $e) {
                Log::error('Erreur envoi email de relance: ' . $e->getMessage(), [
                    'feedback_id' => $this->feedback->id
                ]);
            }
        }
    }

    private function scheduleNextFollowUp()
    {
        // Programmer une nouvelle relance dans 24h
        $nextFollowUp = now()->addDay();

        // Ne programmer que si on reste dans la limite d'une semaine depuis le traitement
        if ($this->isWithinOneWeekLimit($nextFollowUp)) {
            SendFollowUpEmailJob::dispatch($this->feedback)->delay($nextFollowUp);

            Log::info('Prochaine relance programmée', [
                'feedback_id' => $this->feedback->id,
                'next_followup_at' => $nextFollowUp
            ]);
        }
    }

    private function isWithinOneWeekLimit($date = null)
    {
        $checkDate = $date ?? now();
        $treatmentDate = $this->feedback->updated_at; // Date du passage en "treated"
        $oneWeekAfterTreatment = $treatmentDate->addWeek();

        return $checkDate <= $oneWeekAfterTreatment;
    }
}