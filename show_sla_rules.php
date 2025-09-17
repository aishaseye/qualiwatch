<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SlaRule;
use App\Models\FeedbackType;

// Script pour afficher toutes les règles SLA

class SlaRulesViewer
{
    private $companyId = '9fde0f86-211a-46ce-91db-8672e878797b';

    public function __construct()
    {
        // Initialiser Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function showRules()
    {
        echo "📋 RÈGLES SLA ACTUELLES\n";
        echo "======================\n\n";

        $this->showAllFeedbackTypes();
        $this->showCurrentSlaRules();
        $this->showRecommendations();
    }

    private function showAllFeedbackTypes()
    {
        echo "1. Types de feedback disponibles\n";
        echo "--------------------------------\n";

        $feedbackTypes = FeedbackType::all();

        foreach ($feedbackTypes as $type) {
            echo "🏷️  {$type->name} (ID: {$type->id})\n";
            if ($type->description) {
                echo "   Description: {$type->description}\n";
            }
            echo "\n";
        }
    }

    private function showCurrentSlaRules()
    {
        echo "2. Règles SLA configurées pour l'entreprise\n";
        echo "-------------------------------------------\n";

        $slaRules = SlaRule::forCompany($this->companyId)
                          ->active()
                          ->with('feedbackType')
                          ->orderBy('priority_level', 'desc')
                          ->get();

        if ($slaRules->isEmpty()) {
            echo "❌ Aucune règle SLA configurée\n\n";
            return;
        }

        foreach ($slaRules as $rule) {
            echo "🔧 Règle: {$rule->name}\n";
            echo "   Type de feedback: {$rule->feedbackType->name}\n";
            echo "   Priorité: {$rule->priority_level} ({$rule->priority_label})\n";
            echo "   Active: " . ($rule->is_active ? 'Oui' : 'Non') . "\n";

            echo "\n   ⏰ Délais SLA:\n";
            echo "   - Première réponse: " . round($rule->first_response_sla / 60, 1) . "h\n";
            echo "   - Résolution: " . round($rule->resolution_sla / 60, 1) . "h\n";

            echo "\n   🚨 Escalations:\n";
            echo "   - Niveau 1: " . round($rule->escalation_level_1 / 60, 1) . "h → " . implode(', ', $rule->level_1_recipients ?? []) . "\n";
            echo "   - Niveau 2: " . round($rule->escalation_level_2 / 60, 1) . "h → " . implode(', ', $rule->level_2_recipients ?? []) . "\n";
            echo "   - Niveau 3: " . round($rule->escalation_level_3 / 60, 1) . "h → " . implode(', ', $rule->level_3_recipients ?? []) . "\n";

            echo "\n   📺 Canaux: " . implode(', ', $rule->notification_channels ?? ['email']) . "\n";

            if ($rule->conditions) {
                echo "   📝 Conditions: " . json_encode($rule->conditions) . "\n";
            }

            echo "\n" . str_repeat("-", 60) . "\n\n";
        }
    }

    private function showRecommendations()
    {
        echo "3. Recommandations pour les feedbacks négatifs uniquement\n";
        echo "---------------------------------------------------------\n";

        // Vérifier quels types sont négatifs
        $feedbackTypes = FeedbackType::all();
        $negativeTypes = $feedbackTypes->filter(function($type) {
            return stripos($type->name, 'negatif') !== false ||
                   stripos($type->name, 'incident') !== false ||
                   stripos($type->name, 'probleme') !== false ||
                   stripos($type->name, 'plainte') !== false;
        });

        echo "🎯 Types de feedback NÉGATIFS identifiés:\n";
        foreach ($negativeTypes as $type) {
            echo "   - {$type->name} (ID: {$type->id})\n";
        }

        echo "\n💡 Règles SLA recommandées (FEEDBACKS NÉGATIFS UNIQUEMENT):\n\n";

        echo "🔥 RÈGLE 1: Incident Critique\n";
        echo "   Type: negatif\n";
        echo "   Priorité: 5 (Urgence)\n";
        echo "   Première réponse: 15 minutes\n";
        echo "   Résolution: 2 heures\n";
        echo "   Escalation 1: 30min → manager\n";
        echo "   Escalation 2: 1h → director\n";
        echo "   Escalation 3: 2h → ceo\n";
        echo "   Canaux: email, sms, app\n\n";

        echo "⚠️  RÈGLE 2: Feedback Négatif Standard\n";
        echo "   Type: negatif\n";
        echo "   Priorité: 3 (Élevé)\n";
        echo "   Première réponse: 2 heures\n";
        echo "   Résolution: 8 heures\n";
        echo "   Escalation 1: 4h → manager\n";
        echo "   Escalation 2: 6h → director\n";
        echo "   Escalation 3: 8h → ceo\n";
        echo "   Canaux: email, app\n\n";

        echo "🚫 À SUPPRIMER: Toutes les règles pour feedbacks positifs ou suggestions\n";
        echo "   (Les feedbacks positifs ne nécessitent pas d'escalation urgente)\n\n";
    }
}

// Exécution
if (php_sapi_name() === 'cli') {
    $viewer = new SlaRulesViewer();
    $viewer->showRules();
}