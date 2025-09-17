<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SlaRule;
use App\Models\FeedbackType;

// Script pour nettoyer les règles SLA - garder seulement les feedbacks négatifs

class SlaRulesCleanup
{
    private $companyId = '9fde0f86-211a-46ce-91db-8672e878797b';

    public function __construct()
    {
        // Initialiser Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function cleanup()
    {
        echo "🧹 NETTOYAGE DES RÈGLES SLA\n";
        echo "===========================\n\n";

        $this->showCurrentRules();
        $this->deactivateNonNegativeRules();
        $this->showFinalRules();
        $this->showStatistics();
    }

    private function showCurrentRules()
    {
        echo "1. Règles SLA actuelles\n";
        echo "----------------------\n";

        $allRules = SlaRule::forCompany($this->companyId)
                          ->with('feedbackType')
                          ->orderBy('priority_level', 'desc')
                          ->get();

        foreach ($allRules as $rule) {
            $status = $rule->is_active ? '✅ Active' : '❌ Inactive';
            $type = $rule->feedbackType ? $rule->feedbackType->name : 'N/A';

            echo "   {$status} | {$rule->name} (Type: {$type})\n";
        }

        echo "\n";
    }

    private function deactivateNonNegativeRules()
    {
        echo "2. Désactivation des règles non-critiques\n";
        echo "----------------------------------------\n";

        // Récupérer l'ID du type de feedback négatif
        $negativeTypeId = '9fce4bff-a06f-45d6-a371-479b7b0df575';

        // Désactiver toutes les règles qui ne sont pas pour les feedbacks négatifs
        $rulesToDeactivate = SlaRule::forCompany($this->companyId)
                                   ->where('feedback_type_id', '!=', $negativeTypeId)
                                   ->where('is_active', true)
                                   ->get();

        if ($rulesToDeactivate->isEmpty()) {
            echo "ℹ️  Aucune règle non-négative à désactiver\n\n";
            return;
        }

        echo "🚫 Désactivation des règles suivantes :\n";
        foreach ($rulesToDeactivate as $rule) {
            echo "   - {$rule->name} (Type: {$rule->feedbackType->name})\n";

            $rule->update(['is_active' => false]);
            echo "     ✅ Désactivée\n";
        }

        echo "\n💡 Raison : Les feedbacks positifs et suggestions ne nécessitent pas d'escalation urgente\n\n";
    }

    private function showFinalRules()
    {
        echo "3. Règles SLA finales (actives uniquement)\n";
        echo "-----------------------------------------\n";

        $activeRules = SlaRule::forCompany($this->companyId)
                             ->active()
                             ->with('feedbackType')
                             ->orderBy('priority_level', 'desc')
                             ->get();

        if ($activeRules->isEmpty()) {
            echo "❌ Aucune règle active\n\n";
            return;
        }

        foreach ($activeRules as $rule) {
            echo "🔧 {$rule->name}\n";
            echo "   Type: {$rule->feedbackType->name}\n";
            echo "   Priorité: {$rule->priority_level} ({$rule->priority_label})\n";
            echo "   Première réponse: " . round($rule->first_response_sla / 60, 1) . "h\n";
            echo "   Résolution: " . round($rule->resolution_sla / 60, 1) . "h\n";
            echo "   Escalations: " . round($rule->escalation_level_1 / 60, 1) . "h → " . round($rule->escalation_level_2 / 60, 1) . "h → " . round($rule->escalation_level_3 / 60, 1) . "h\n";
            echo "   Canaux: " . implode(', ', $rule->notification_channels ?? ['email']) . "\n\n";
        }
    }

    private function showStatistics()
    {
        echo "4. Statistiques de nettoyage\n";
        echo "----------------------------\n";

        $totalRules = SlaRule::forCompany($this->companyId)->count();
        $activeRules = SlaRule::forCompany($this->companyId)->active()->count();
        $inactiveRules = $totalRules - $activeRules;

        echo "📊 Résumé :\n";
        echo "   Total des règles: {$totalRules}\n";
        echo "   Règles actives: {$activeRules}\n";
        echo "   Règles désactivées: {$inactiveRules}\n\n";

        // Types concernés par les règles actives
        $activeTypes = SlaRule::forCompany($this->companyId)
                             ->active()
                             ->with('feedbackType')
                             ->get()
                             ->pluck('feedbackType.name')
                             ->unique();

        echo "🎯 Types de feedback avec SLA actif :\n";
        foreach ($activeTypes as $type) {
            echo "   - {$type}\n";
        }

        echo "\n✅ NETTOYAGE TERMINÉ !\n";
        echo "🎯 Le système SLA est maintenant focalisé uniquement sur les feedbacks négatifs\n";
        echo "📧 Seuls les feedbacks négatifs déclencheront des escalations et notifications\n\n";
    }
}

// Exécution du nettoyage
if (php_sapi_name() === 'cli') {
    $cleanup = new SlaRulesCleanup();
    $cleanup->cleanup();
}