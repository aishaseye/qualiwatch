<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Feedback;
use App\Models\SlaRule;
use App\Models\Escalation;
use App\Models\User;
use App\Models\Company;
use App\Services\EscalationService;
use Carbon\Carbon;

// Test manuel pour forcer l'escalation d'un feedback spécifique

class ManualEscalationTest
{
    private $escalationService;
    private $companyId = '9fde0f86-211a-46ce-91db-8672e878797b';
    private $feedbackTypeId = '9fce4bff-a06f-45d6-a371-479b7b0df575';

    public function __construct()
    {
        $this->escalationService = new EscalationService();

        // Initialiser Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function testEscalation()
    {
        echo "🔥 TEST MANUEL D'ESCALATION SLA\n";
        echo "==============================\n\n";

        // 1. Créer ou modifier un feedback pour test
        $feedback = $this->createTestFeedbackForEscalation();

        // 2. Vérifier la règle SLA
        $slaRule = $this->checkSlaRule($feedback);

        // 3. Forcer l'escalation
        $this->forceEscalation($feedback, $slaRule);

        // 4. Vérifier les notifications
        $this->checkNotifications($feedback);

        echo "\n✅ Test manuel terminé\n";
    }

    private function createTestFeedbackForEscalation()
    {
        echo "1. Création d'un feedback critique pour test\n";
        echo "-------------------------------------------\n";

        // Chercher un feedback récent
        $feedback = Feedback::where('company_id', $this->companyId)
                          ->where('feedback_type_id', $this->feedbackTypeId)
                          ->orderBy('created_at', 'desc')
                          ->first();

        if (!$feedback) {
            echo "❌ Aucun feedback trouvé\n";
            return null;
        }

        echo "✅ Feedback trouvé: #{$feedback->reference}\n";
        echo "   ID: {$feedback->id}\n";
        echo "   Créé le: {$feedback->created_at}\n";
        echo "   Rating: {$feedback->rating}/5\n";
        echo "   Sentiment: {$feedback->sentiment}\n";
        echo "   Statut: {$feedback->feedbackStatus->name}\n";

        // Modifier le feedback pour simuler un cas critique
        try {
            // Simuler un feedback très ancien pour déclencher l'escalation
            $oldCreatedAt = Carbon::now()->subHours(6); // 6 heures dans le passé

            echo "\n🔧 Simulation: modification de la date de création\n";
            echo "   Ancienne date: {$feedback->created_at}\n";
            echo "   Nouvelle date: {$oldCreatedAt}\n";

            // Note: en production, on ne modifierait pas la date
            // Ici c'est pour tester l'escalation

            $feedback->update([
                'created_at' => $oldCreatedAt,
                'rating' => 1, // Rating très négatif
                'sentiment' => 'en_colere'
            ]);

            $feedback->refresh();
            echo "✅ Feedback modifié pour test\n";

        } catch (Exception $e) {
            echo "⚠️  Erreur lors de la modification: {$e->getMessage()}\n";
        }

        return $feedback;
    }

    private function checkSlaRule($feedback)
    {
        echo "\n2. Vérification de la règle SLA\n";
        echo "-------------------------------\n";

        $slaRule = SlaRule::findApplicableRule($feedback);

        if (!$slaRule) {
            echo "❌ Aucune règle SLA applicable\n";
            return null;
        }

        echo "✅ Règle SLA: {$slaRule->name}\n";
        echo "   Priorité: {$slaRule->priority_level}\n";

        // Calculer les échéances
        $deadlines = $slaRule->calculateDeadlines($feedback->created_at);
        $now = Carbon::now();

        echo "\n⏰ Échéances SLA:\n";
        foreach ($deadlines as $type => $deadline) {
            $diff = $now->diffInMinutes($deadline, false);
            $status = $diff <= 0 ? "❌ DÉPASSÉ" : "✅ OK";
            echo "   {$type}: {$deadline->format('H:i:s')} ({$status})\n";
        }

        // Vérifier quel niveau d'escalation est nécessaire
        $escalationLevel = $slaRule->getEscalationLevel($feedback->created_at);
        echo "\n🚨 Niveau d'escalation requis: {$escalationLevel}\n";

        return $slaRule;
    }

    private function forceEscalation($feedback, $slaRule)
    {
        echo "\n3. Test d'escalation forcée\n";
        echo "---------------------------\n";

        if (!$slaRule) {
            echo "❌ Impossible sans règle SLA\n";
            return;
        }

        try {
            // Vérifier les escalations existantes
            $existingEscalations = Escalation::where('feedback_id', $feedback->id)->get();
            echo "📊 Escalations existantes: {$existingEscalations->count()}\n";

            // Tester le service d'escalation
            echo "\n🔄 Test du service d'escalation...\n";
            $result = $this->escalationService->checkFeedbackForEscalation($feedback);

            if ($result) {
                echo "✅ Escalation créée avec succès\n";

                // Recharger les escalations
                $newEscalations = Escalation::where('feedback_id', $feedback->id)
                                          ->orderBy('created_at', 'desc')
                                          ->get();

                echo "📈 Nouvelles escalations: {$newEscalations->count()}\n";

                foreach ($newEscalations as $escalation) {
                    echo "   - Niveau {$escalation->escalation_level}\n";
                    echo "     Raison: {$escalation->trigger_reason}\n";
                    echo "     Créée: {$escalation->escalated_at}\n";
                    echo "     Notifiée: " . ($escalation->notified_at ? 'Oui' : 'Non') . "\n";
                }

            } else {
                echo "ℹ️  Aucune escalation nécessaire ou déjà existante\n";
            }

        } catch (Exception $e) {
            echo "❌ Erreur lors de l'escalation: {$e->getMessage()}\n";
        }
    }

    private function checkNotifications($feedback)
    {
        echo "\n4. Vérification des notifications\n";
        echo "---------------------------------\n";

        // Chercher les utilisateurs de l'entreprise
        $managers = User::where('company_id', $this->companyId)
                       ->where('role', 'manager')
                       ->get();

        echo "👥 Managers de l'entreprise ({$managers->count()}):\n";
        foreach ($managers as $manager) {
            echo "   - {$manager->name} ({$manager->email})\n";
        }

        $directors = User::where('company_id', $this->companyId)
                        ->where('role', 'director')
                        ->get();

        echo "\n👥 Directors de l'entreprise ({$directors->count()}):\n";
        foreach ($directors as $director) {
            echo "   - {$director->name} ({$director->email})\n";
        }

        // Chercher les notifications récentes
        $notifications = \App\Models\Notification::whereHas('user', function($q) {
                                                   $q->where('company_id', $this->companyId);
                                               })
                                               ->where('type', 'escalation')
                                               ->where('created_at', '>', now()->subHour())
                                               ->get();

        echo "\n📬 Notifications d'escalation récentes ({$notifications->count()}):\n";
        foreach ($notifications as $notification) {
            echo "   - À: {$notification->user->name}\n";
            echo "     Titre: {$notification->title}\n";
            echo "     Message: {$notification->message}\n";
            echo "     Créée: {$notification->created_at}\n\n";
        }
    }
}

// Exécution du test
if (php_sapi_name() === 'cli') {
    $tester = new ManualEscalationTest();
    $tester->testEscalation();
}