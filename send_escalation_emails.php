<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Escalation;
use App\Models\User;
use App\Mail\EscalationNotification;
use Illuminate\Support\Facades\Mail;

class EscalationEmailSender
{
    private $companyId = '9fde0f86-211a-46ce-91db-8672e878797b';

    public function __construct()
    {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function sendEmails()
    {
        echo "📧 ENVOI DES EMAILS D'ESCALATION\n";
        echo "===============================\n\n";

        $this->testSingleEscalation();
        $this->sendPendingNotifications();
    }

    private function testSingleEscalation()
    {
        echo "🧪 Test d'envoi d'un email unique:\n";
        echo "----------------------------------\n";

        // Prendre une escalation de niveau 3 non notifiée
        $escalation = Escalation::whereHas('feedback', function($q) {
                                    $q->where('company_id', $this->companyId);
                                })
                                ->where('is_resolved', false)
                                ->whereNull('notified_at')
                                ->where('escalation_level', 3)
                                ->with('feedback', 'slaRule')
                                ->first();

        if (!$escalation) {
            echo "❌ Aucune escalation niveau 3 non notifiée trouvée\n\n";
            return;
        }

        echo "🚨 Escalation sélectionnée:\n";
        echo "   ID: {$escalation->id}\n";
        echo "   Feedback: {$escalation->feedback->reference}\n";
        echo "   Niveau: {$escalation->escalation_level}\n";
        echo "   Créée: {$escalation->escalated_at->format('d/m/Y H:i')}\n\n";

        // Trouver le CEO
        $ceo = User::where('company_id', $this->companyId)
                  ->where('role', 'ceo')
                  ->first();

        if (!$ceo) {
            echo "❌ Aucun CEO trouvé\n\n";
            return;
        }

        echo "👤 Destinataire: {$ceo->full_name} ({$ceo->email})\n";

        try {
            // Envoyer l'email
            echo "📤 Envoi de l'email...\n";

            Mail::to($ceo->email)->send(new EscalationNotification($escalation, $ceo));

            // Marquer comme notifiée
            $escalation->markAsNotified([$ceo->id], ['email']);

            echo "✅ EMAIL ENVOYÉ AVEC SUCCÈS !\n";
            echo "📧 Email envoyé à: {$ceo->email}\n";
            echo "⏰ Notifiée le: {$escalation->fresh()->notified_at}\n\n";

        } catch (Exception $e) {
            echo "❌ Erreur lors de l'envoi: {$e->getMessage()}\n\n";
        }
    }

    private function sendPendingNotifications()
    {
        echo "📬 Envoi des notifications en attente:\n";
        echo "-------------------------------------\n";

        $escalations = Escalation::whereHas('feedback', function($q) {
                                     $q->where('company_id', $this->companyId);
                                 })
                                 ->where('is_resolved', false)
                                 ->whereNull('notified_at')
                                 ->with('feedback', 'slaRule')
                                 ->get();

        echo "📊 Total escalations non notifiées: {$escalations->count()}\n\n";

        $users = User::where('company_id', $this->companyId)->get();

        $sent = 0;
        $errors = 0;

        foreach ($escalations->take(5) as $escalation) { // Limiter à 5 pour le test
            $recipients = $this->getRecipients($escalation->escalation_level, $users);

            if ($recipients->isEmpty()) {
                echo "⚠️  Niveau {$escalation->escalation_level} - Aucun destinataire\n";
                continue;
            }

            foreach ($recipients as $user) {
                try {
                    echo "📤 Envoi à {$user->full_name} (Niveau {$escalation->escalation_level})...\n";

                    Mail::to($user->email)->send(new EscalationNotification($escalation, $user));

                    $sent++;
                    echo "   ✅ Envoyé\n";

                } catch (Exception $e) {
                    $errors++;
                    echo "   ❌ Erreur: {$e->getMessage()}\n";
                }
            }

            // Marquer l'escalation comme notifiée
            $escalation->markAsNotified(
                $recipients->pluck('id')->toArray(),
                ['email']
            );

            echo "   ✅ Escalation {$escalation->id} marquée comme notifiée\n\n";
        }

        echo "📊 Résumé:\n";
        echo "   Emails envoyés: {$sent}\n";
        echo "   Erreurs: {$errors}\n\n";
    }

    private function getRecipients($level, $users)
    {
        return match($level) {
            1 => $users->where('role', 'manager'),
            2 => $users->where('role', 'director'),
            3 => $users->where('role', 'ceo'),
            default => collect()
        };
    }
}

if (php_sapi_name() === 'cli') {
    $sender = new EscalationEmailSender();
    $sender->sendEmails();
}