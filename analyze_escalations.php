<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Feedback;
use App\Models\SlaRule;
use App\Models\Escalation;
use App\Models\User;
use App\Models\Company;
use Carbon\Carbon;

// Analyse des escalations existantes

class EscalationAnalyzer
{
    private $companyId = '9fde0f86-211a-46ce-91db-8672e878797b';

    public function __construct()
    {
        // Initialiser Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function analyze()
    {
        echo "📊 ANALYSE DES ESCALATIONS EXISTANTES\n";
        echo "=====================================\n\n";

        $this->analyzeCompanyData();
        $this->analyzeActiveEscalations();
        $this->analyzeSlaRules();
        $this->analyzeEscalationTiming();
        $this->suggestActions();
    }

    private function analyzeCompanyData()
    {
        echo "1. Données de l'entreprise\n";
        echo "-------------------------\n";

        $company = Company::find($this->companyId);
        echo "🏢 Entreprise: {$company->name}\n";
        echo "📧 Email: {$company->email}\n";
        echo "📱 Téléphone: {$company->phone}\n";

        // Utilisateurs de l'entreprise
        $users = User::where('company_id', $this->companyId)->get()->groupBy('role');

        echo "\n👥 Utilisateurs par rôle:\n";
        foreach ($users as $role => $roleUsers) {
            echo "   {$role}: {$roleUsers->count()}\n";
            foreach ($roleUsers as $user) {
                echo "     - {$user->name} ({$user->email})\n";
            }
        }

        // Feedbacks récents
        $recentFeedbacks = Feedback::where('company_id', $this->companyId)
                                 ->where('created_at', '>', now()->subDays(7))
                                 ->count();
        echo "\n📝 Feedbacks des 7 derniers jours: {$recentFeedbacks}\n";

        echo "\n";
    }

    private function analyzeActiveEscalations()
    {
        echo "2. Escalations actives\n";
        echo "---------------------\n";

        $escalations = Escalation::whereHas('feedback', function($q) {
                                     $q->where('company_id', $this->companyId);
                                 })
                                 ->where('is_resolved', false)
                                 ->with(['feedback', 'slaRule'])
                                 ->orderBy('escalation_level', 'desc')
                                 ->orderBy('escalated_at', 'asc')
                                 ->get();

        echo "🚨 Total escalations actives: {$escalations->count()}\n\n";

        foreach ($escalations as $escalation) {
            $feedback = $escalation->feedback;
            $elapsed = $escalation->escalated_at->diffForHumans();

            echo "📌 Escalation #{$escalation->id}\n";
            echo "   Niveau: {$escalation->escalation_level}\n";
            echo "   Feedback: #{$feedback->reference}\n";
            echo "   Raison: {$escalation->trigger_reason}\n";
            echo "   Créée: {$escalation->escalated_at->format('Y-m-d H:i:s')} ({$elapsed})\n";
            echo "   SLA Rule: " . ($escalation->slaRule ? $escalation->slaRule->name : 'N/A') . "\n";

            if ($escalation->notified_at) {
                echo "   Notifiée: {$escalation->notified_at->format('Y-m-d H:i:s')}\n";
                echo "   Canaux: " . implode(', ', $escalation->notification_channels ? $escalation->notification_channels : []) . "\n";
            } else {
                echo "   ⚠️  NON NOTIFIÉE\n";
            }

            echo "\n";
        }
    }

    private function analyzeSlaRules()
    {
        echo "3. Règles SLA configurées\n";
        echo "------------------------\n";

        $slaRules = SlaRule::forCompany($this->companyId)
                          ->active()
                          ->with('feedbackType')
                          ->ordered()
                          ->get();

        echo "📋 Règles SLA actives: {$slaRules->count()}\n\n";

        foreach ($slaRules as $rule) {
            echo "🔧 Règle: {$rule->name}\n";
            echo "   Type de feedback: " . ($rule->feedbackType ? $rule->feedbackType->name : 'N/A') . "\n";
            echo "   Priorité: {$rule->priority_level} ({$rule->priority_label})\n";
            echo "   Première réponse: " . round($rule->first_response_sla / 60, 1) . "h\n";
            echo "   Résolution: " . round($rule->resolution_sla / 60, 1) . "h\n";
            echo "   Escalations:\n";
            echo "     Niveau 1: " . round($rule->escalation_level_1 / 60, 1) . "h → " . implode(', ', $rule->level_1_recipients ? $rule->level_1_recipients : []) . "\n";
            echo "     Niveau 2: " . round($rule->escalation_level_2 / 60, 1) . "h → " . implode(', ', $rule->level_2_recipients ? $rule->level_2_recipients : []) . "\n";
            echo "     Niveau 3: " . round($rule->escalation_level_3 / 60, 1) . "h → " . implode(', ', $rule->level_3_recipients ? $rule->level_3_recipients : []) . "\n";
            echo "   Canaux: " . implode(', ', $rule->notification_channels ? $rule->notification_channels : ['email']) . "\n";
            echo "\n";
        }
    }

    private function analyzeEscalationTiming()
    {
        echo "4. Analyse des délais d'escalation\n";
        echo "---------------------------------\n";

        $now = Carbon::now();
        echo "⏰ Heure actuelle: {$now->format('Y-m-d H:i:s')}\n\n";

        // Escalations par heure de création
        $escalationsByHour = Escalation::whereHas('feedback', function($q) {
                                          $q->where('company_id', $this->companyId);
                                      })
                                      ->where('escalated_at', '>', now()->subDays(7))
                                      ->get()
                                      ->groupBy(function($escalation) {
                                          return $escalation->escalated_at->format('H');
                                      });

        echo "📈 Escalations par heure (7 derniers jours):\n";
        for ($hour = 0; $hour < 24; $hour++) {
            $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $count = $escalationsByHour->get($hourStr, collect())->count();
            $bar = str_repeat('█', min($count, 20));
            echo "   {$hourStr}h: {$bar} ({$count})\n";
        }

        // Temps de résolution moyen
        $resolvedEscalations = Escalation::whereHas('feedback', function($q) {
                                            $q->where('company_id', $this->companyId);
                                        })
                                        ->where('is_resolved', true)
                                        ->where('resolved_at', '>', now()->subDays(30))
                                        ->get();

        if ($resolvedEscalations->count() > 0) {
            $avgResolutionMinutes = $resolvedEscalations->avg(function($escalation) {
                return $escalation->escalated_at->diffInMinutes($escalation->resolved_at);
            });

            echo "\n📊 Temps moyen de résolution: " . round($avgResolutionMinutes / 60, 1) . "h\n";
        }

        echo "\n";
    }

    private function suggestActions()
    {
        echo "5. Recommandations\n";
        echo "-----------------\n";

        $activeEscalations = Escalation::whereHas('feedback', function($q) {
                                        $q->where('company_id', $this->companyId);
                                     })
                                     ->where('is_resolved', false)
                                     ->get();

        $level3Count = $activeEscalations->where('escalation_level', 3)->count();
        $level2Count = $activeEscalations->where('escalation_level', 2)->count();
        $level1Count = $activeEscalations->where('escalation_level', 1)->count();

        if ($level3Count > 0) {
            echo "🚨 URGENT: {$level3Count} escalations de niveau 3 nécessitent une attention immédiate du PDG/Direction\n";
        }

        if ($level2Count > 0) {
            echo "⚠️  {$level2Count} escalations de niveau 2 en attente de traitement par la direction\n";
        }

        if ($level1Count > 0) {
            echo "📋 {$level1Count} escalations de niveau 1 à traiter par les managers\n";
        }

        // Vérifier les notifications non envoyées
        $unnotified = $activeEscalations->whereNull('notified_at')->count();
        if ($unnotified > 0) {
            echo "❌ {$unnotified} escalations sans notification envoyée - vérifier le système de notification\n";
        }

        // Suggestions d'amélioration
        echo "\n💡 Suggestions d'amélioration:\n";
        echo "   - Créer des utilisateurs avec les rôles manager/director/ceo\n";
        echo "   - Configurer les emails des destinataires d'escalation\n";
        echo "   - Mettre en place une surveillance temps réel des SLA\n";
        echo "   - Former les équipes à la résolution rapide des escalations\n";

        echo "\n";
    }
}

// Exécution de l'analyse
if (php_sapi_name() === 'cli') {
    $analyzer = new EscalationAnalyzer();
    $analyzer->analyze();
}