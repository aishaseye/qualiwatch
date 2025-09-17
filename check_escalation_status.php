<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Feedback;
use App\Models\Escalation;
use App\Models\User;

class EscalationChecker
{
    private $companyId = '9fde0f86-211a-46ce-91db-8672e878797b';

    public function __construct()
    {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function check()
    {
        echo "🚨 VÉRIFICATION DES ESCALATIONS\n";
        echo "==============================\n\n";

        $this->showEscalationStatus();
        $this->testFeedbackEscalation();
    }

    private function showEscalationStatus()
    {
        echo "📊 Escalations actuelles:\n";

        $escalations = Escalation::whereHas('feedback', function($q) {
                                    $q->where('company_id', $this->companyId);
                                })
                                ->where('is_resolved', false)
                                ->with('feedback')
                                ->orderBy('escalation_level', 'desc')
                                ->take(10)
                                ->get();

        foreach ($escalations as $escalation) {
            $notified = $escalation->notified_at ? '✅ Notifiée' : '❌ PAS notifiée';
            echo "🔹 Niveau {$escalation->escalation_level} | {$escalation->feedback->reference} | {$notified}\n";
        }
        echo "\n";
    }

    private function testFeedbackEscalation()
    {
        echo "🔍 Test avec un feedback spécifique:\n";

        $feedback = Feedback::where('company_id', $this->companyId)
                           ->whereHas('escalations')
                           ->with('escalations')
                           ->first();

        if (!$feedback) {
            echo "❌ Aucun feedback avec escalation\n";
            return;
        }

        echo "📝 Feedback: {$feedback->reference}\n";
        echo "⭐ Rating: {$feedback->rating}/5\n";
        echo "📅 Créé: {$feedback->created_at->format('d/m/Y H:i')}\n\n";

        foreach ($feedback->escalations as $escalation) {
            echo "🚨 Escalation Niveau {$escalation->escalation_level}:\n";
            echo "   Créée: {$escalation->escalated_at->format('d/m/Y H:i')}\n";
            echo "   Raison: {$escalation->trigger_reason}\n";

            if ($escalation->notified_at) {
                echo "   ✅ Notifiée le: {$escalation->notified_at->format('d/m/Y H:i')}\n";
                $channels = $escalation->notification_channels ?? [];
                echo "   📺 Canaux: " . implode(', ', $channels) . "\n";
            } else {
                echo "   ❌ PAS NOTIFIÉE\n";
            }
            echo "\n";
        }

        $this->showWhoGetsNotified($escalation->escalation_level ?? 1);
    }

    private function showWhoGetsNotified($level)
    {
        echo "👥 Qui devrait être notifié (Niveau {$level}):\n";

        $users = User::where('company_id', $this->companyId)->get();

        $recipients = match($level) {
            1 => ['manager'],
            2 => ['director'],
            3 => ['ceo'],
            default => ['manager']
        };

        foreach ($recipients as $role) {
            $user = $users->where('role', $role)->first();
            if ($user) {
                echo "   📧 {$user->full_name} ({$user->email}) - {$role}\n";
            } else {
                echo "   ❌ Aucun {$role} trouvé\n";
            }
        }
        echo "\n";
    }
}

if (php_sapi_name() === 'cli') {
    $checker = new EscalationChecker();
    $checker->check();
}