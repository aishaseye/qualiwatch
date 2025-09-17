<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Feedback;
use App\Models\SlaRule;
use App\Models\Escalation;
use App\Models\User;
use App\Models\Company;
use App\Services\EscalationService;
use Carbon\Carbon;

// Test SLA et Escalation pour l'entreprise 9fde0f86-211a-46ce-91db-8672e878797b
// avec feedback négatif de type 9fce4bff-a06f-45d6-a371-479b7b0df575

class EscalationTester
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

    public function runTests()
    {
        echo "🚨 TEST D'ESCALATION SLA\n";
        echo "======================\n\n";

        // 1. Vérifier les données de base
        $this->testBaseData();

        // 2. Créer un feedback négatif de test si nécessaire
        $feedback = $this->createTestFeedback();

        // 3. Vérifier les règles SLA
        $this->testSlaRules($feedback);

        // 4. Tester l'escalation
        $this->testEscalation($feedback);

        // 5. Analyser les heures et délais
        $this->analyzeTimings($feedback);

        // 6. Tester le déclenchement automatique
        $this->testAutomaticEscalation();

        echo "\n✅ Tests terminés\n";
    }

    private function testBaseData()
    {
        echo "1. Vérification des données de base\n";
        echo "-----------------------------------\n";

        // Vérifier l'entreprise
        $company = Company::find($this->companyId);
        if ($company) {
            echo "✅ Entreprise trouvée: {$company->name}\n";
        } else {
            echo "❌ Entreprise non trouvée avec l'ID: {$this->companyId}\n";
            return false;
        }

        // Vérifier le manager
        $manager = User::where('email', 'sulamaish4738@gmail.com')
                      ->where('company_id', $this->companyId)
                      ->first();

        if ($manager) {
            echo "✅ Manager trouvé: {$manager->name} ({$manager->role})\n";
        } else {
            echo "❌ Manager non trouvé avec l'email: sulamaish4738@gmail.com\n";
        }

        // Vérifier les feedbacks existants
        $existingFeedbacks = Feedback::where('company_id', $this->companyId)
                                   ->where('feedback_type_id', $this->feedbackTypeId)
                                   ->count();

        echo "📊 Feedbacks existants de ce type: {$existingFeedbacks}\n";

        echo "\n";
        return true;
    }

    private function createTestFeedback()
    {
        echo "2. Création/Recherche de feedback de test\n";
        echo "----------------------------------------\n";

        // Chercher un feedback négatif récent
        $existingFeedback = Feedback::where('company_id', $this->companyId)
                                  ->where('feedback_type_id', $this->feedbackTypeId)
                                  ->whereHas('feedbackStatus', function($q) {
                                      $q->whereIn('name', ['new', 'in_progress']);
                                  })
                                  ->orderBy('created_at', 'desc')
                                  ->first();

        if ($existingFeedback) {
            echo "✅ Feedback existant trouvé: #{$existingFeedback->reference}\n";
            echo "   Créé le: {$existingFeedback->created_at}\n";
            echo "   Statut: {$existingFeedback->feedbackStatus->name}\n";
            echo "   Rating: {$existingFeedback->rating}/5\n";
            return $existingFeedback;
        }

        echo "⚠️  Aucun feedback actif trouvé. Création d'un feedback de test...\n";

        // Créer un feedback de test (simulation)
        $testData = [
            'company_id' => $this->companyId,
            'feedback_type_id' => $this->feedbackTypeId,
            'rating' => 1, // Note très négative
            'sentiment' => 'en_colere',
            'created_at' => Carbon::now()->subMinutes(30), // Créé il y a 30 minutes
            'content' => 'Service épouvantable, très déçu!',
            'reference' => 'TEST-' . now()->format('YmdHis')
        ];

        echo "📝 Données du feedback de test:\n";
        foreach ($testData as $key => $value) {
            echo "   {$key}: {$value}\n";
        }

        return (object) $testData; // Simulation
    }

    private function testSlaRules($feedback)
    {
        echo "\n3. Vérification des règles SLA\n";
        echo "------------------------------\n";

        // Chercher les règles SLA applicables
        $applicableRule = SlaRule::findApplicableRule($feedback);

        if ($applicableRule) {
            echo "✅ Règle SLA applicable trouvée: {$applicableRule->name}\n";
            echo "   Priorité: {$applicableRule->priority_level} ({$applicableRule->priority_label})\n";
            echo "   Première réponse SLA: {$applicableRule->first_response_sla_hours}h\n";
            echo "   Résolution SLA: {$applicableRule->resolution_sla_hours}h\n";
            echo "   Escalade Niveau 1: " . ($applicableRule->escalation_level_1 / 60) . "h\n";
            echo "   Escalade Niveau 2: " . ($applicableRule->escalation_level_2 / 60) . "h\n";
            echo "   Escalade Niveau 3: " . ($applicableRule->escalation_level_3 / 60) . "h\n";

            echo "   Destinataires Niveau 1: " . implode(', ', $applicableRule->level_1_recipients ?? []) . "\n";
            echo "   Destinataires Niveau 2: " . implode(', ', $applicableRule->level_2_recipients ?? []) . "\n";
            echo "   Destinataires Niveau 3: " . implode(', ', $applicableRule->level_3_recipients ?? []) . "\n";

        } else {
            echo "❌ Aucune règle SLA applicable trouvée\n";

            // Lister toutes les règles pour debug
            $allRules = SlaRule::forCompany($this->companyId)->active()->get();
            echo "📋 Règles SLA disponibles ({$allRules->count()}):\n";
            foreach ($allRules as $rule) {
                echo "   - {$rule->name} (Type: {$rule->feedback_type_id})\n";
            }
        }

        return $applicableRule;
    }

    private function testEscalation($feedback)
    {
        echo "\n4. Test d'escalation\n";
        echo "-------------------\n";

        // Vérifier les escalations existantes
        $existingEscalations = Escalation::where('feedback_id', $feedback->id ?? 'test')
                                       ->get();

        echo "📊 Escalations existantes: {$existingEscalations->count()}\n";

        foreach ($existingEscalations as $escalation) {
            echo "   - Niveau {$escalation->escalation_level} créée le {$escalation->escalated_at}\n";
            echo "     Raison: {$escalation->trigger_reason}\n";
            echo "     Résolue: " . ($escalation->is_resolved ? 'Oui' : 'Non') . "\n";
        }

        // Test du service d'escalation
        if (isset($feedback->id)) {
            echo "\n🔄 Test du service d'escalation...\n";
            try {
                $needsEscalation = $this->escalationService->checkFeedbackForEscalation($feedback);
                echo $needsEscalation ? "✅ Escalation nécessaire détectée\n" : "ℹ️  Pas d'escalation nécessaire pour le moment\n";
            } catch (Exception $e) {
                echo "❌ Erreur lors du test d'escalation: {$e->getMessage()}\n";
            }
        } else {
            echo "⚠️  Test avec données simulées, impossible de tester l'escalation réelle\n";
        }
    }

    private function analyzeTimings($feedback)
    {
        echo "\n5. Analyse des heures et délais\n";
        echo "------------------------------\n";

        $now = Carbon::now();
        $createdAt = Carbon::parse($feedback->created_at);
        $elapsedMinutes = $now->diffInMinutes($createdAt);

        echo "⏰ Heure actuelle: {$now->format('Y-m-d H:i:s')}\n";
        echo "📅 Feedback créé: {$createdAt->format('Y-m-d H:i:s')}\n";
        echo "⏱️  Temps écoulé: {$elapsedMinutes} minutes (" . round($elapsedMinutes/60, 1) . "h)\n";

        // Simuler les délais SLA (valeurs typiques)
        $slaDelays = [
            'escalation_level_1' => 60,   // 1 heure
            'escalation_level_2' => 120,  // 2 heures
            'escalation_level_3' => 240,  // 4 heures
        ];

        echo "\n📋 Analyse des seuils d'escalation:\n";
        foreach ($slaDelays as $level => $delayMinutes) {
            $levelNum = str_replace('escalation_level_', '', $level);
            $deadline = $createdAt->copy()->addMinutes($delayMinutes);
            $timeLeft = $deadline->diffInMinutes($now, false);

            if ($timeLeft <= 0) {
                echo "   ❌ Niveau {$levelNum}: DÉPASSÉ depuis " . abs($timeLeft) . " minutes\n";
            } else {
                echo "   ⏳ Niveau {$levelNum}: Dans {$timeLeft} minutes ({$deadline->format('H:i')})\n";
            }
        }
    }

    private function testAutomaticEscalation()
    {
        echo "\n6. Test du déclenchement automatique\n";
        echo "-----------------------------------\n";

        echo "🔄 Exécution de la commande d'escalation automatique...\n";

        try {
            $escalationsTriggered = $this->escalationService->checkAllFeedbacksForEscalation();
            echo "✅ Commande exécutée avec succès\n";
            echo "📊 Nouvelles escalations déclenchées: {$escalationsTriggered}\n";

            // Afficher les statistiques
            $stats = $this->escalationService->getEscalationStats($this->companyId);
            echo "\n📈 Statistiques d'escalation pour l'entreprise:\n";
            echo "   Total actives: {$stats['total_active']}\n";
            echo "   Niveau 1: {$stats['level_1']}\n";
            echo "   Niveau 2: {$stats['level_2']}\n";
            echo "   Niveau 3: {$stats['level_3']}\n";
            echo "   Résolues aujourd'hui: {$stats['resolved_today']}\n";

            if ($stats['avg_resolution_time']) {
                $avgHours = round($stats['avg_resolution_time'] / 60, 1);
                echo "   Temps moyen résolution: {$avgHours}h\n";
            }

        } catch (Exception $e) {
            echo "❌ Erreur lors du test automatique: {$e->getMessage()}\n";
        }
    }
}

// Exécution des tests
if (php_sapi_name() === 'cli') {
    $tester = new EscalationTester();
    $tester->runTests();
}