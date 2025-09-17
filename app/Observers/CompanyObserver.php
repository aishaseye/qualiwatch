<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\SlaRule;
use App\Models\FeedbackType;
use Illuminate\Support\Facades\Log;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        Log::info("CompanyObserver::created appelé pour entreprise: " . $company->name);
        $this->createDefaultSlaRules($company);
    }

    /**
     * Créer les règles SLA par défaut pour une nouvelle entreprise
     */
    private function createDefaultSlaRules(Company $company)
    {
        try {
            // Récupérer le type de feedback négatif
            $negativeFeedbackType = FeedbackType::where('name', 'negatif')->first();

            if (!$negativeFeedbackType) {
                Log::warning("Type de feedback négatif non trouvé pour l'entreprise {$company->id}");
                return;
            }

            // Configuration des règles SLA par défaut pour feedbacks négatifs
            $defaultSlaRules = [
                'Feedback Négatif Critique' => [
                    'feedback_type' => $negativeFeedbackType,
                    'priority_level' => 5,
                    'first_response_sla' => 60,    // 1 heure
                    'resolution_sla' => 8 * 60,   // 8 heures
                    'escalation_level_1' => 2 * 60,  // 2h
                    'escalation_level_2' => 4 * 60,  // 4h
                    'escalation_level_3' => 8 * 60,  // 8h
                    'level_1_recipients' => ['manager'],
                    'level_2_recipients' => ['service_head'],
                    'level_3_recipients' => ['director'],
                ],
                'Feedback Négatif Standard' => [
                    'feedback_type' => $negativeFeedbackType,
                    'priority_level' => 3,
                    'first_response_sla' => 2 * 60,   // 2 heures
                    'resolution_sla' => 12 * 60,      // 12 heures
                    'escalation_level_1' => 4 * 60,   // 4h
                    'escalation_level_2' => 8 * 60,   // 8h
                    'escalation_level_3' => 12 * 60,  // 12h
                    'level_1_recipients' => ['manager'],
                    'level_2_recipients' => ['service_head'],
                    'level_3_recipients' => ['director'],
                ],
                'SLA Par Défaut' => [
                    'feedback_type' => null, // Pour tous les types non couverts
                    'priority_level' => 3,
                    'first_response_sla' => 4 * 60,   // 4 heures
                    'resolution_sla' => 24 * 60,      // 24 heures
                    'escalation_level_1' => 8 * 60,   // 8h
                    'escalation_level_2' => 16 * 60,  // 16h
                    'escalation_level_3' => 24 * 60,  // 24h
                    'level_1_recipients' => ['manager'],
                    'level_2_recipients' => ['service_head'],
                    'level_3_recipients' => ['director'],
                ],
            ];

            $createdRules = 0;
            foreach ($defaultSlaRules as $name => $config) {
                SlaRule::create([
                    'company_id' => $company->id,
                    'name' => $name,
                    'description' => "Règle SLA par défaut pour " . $name,
                    'feedback_type_id' => $config['feedback_type']?->id,
                    'priority_level' => $config['priority_level'],
                    'first_response_sla' => $config['first_response_sla'],
                    'resolution_sla' => $config['resolution_sla'],
                    'escalation_level_1' => $config['escalation_level_1'],
                    'escalation_level_2' => $config['escalation_level_2'],
                    'escalation_level_3' => $config['escalation_level_3'],
                    'level_1_recipients' => $config['level_1_recipients'],
                    'level_2_recipients' => $config['level_2_recipients'],
                    'level_3_recipients' => $config['level_3_recipients'],
                    'notification_channels' => ['email', 'app'],
                    'conditions' => [],
                    'is_active' => true,
                    'sort_order' => $config['priority_level'],
                ]);
                $createdRules++;
            }

            Log::info("Règles SLA créées automatiquement pour l'entreprise {$company->name}", [
                'company_id' => $company->id,
                'rules_created' => $createdRules
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la création des règles SLA par défaut pour l'entreprise {$company->id}", [
                'error' => $e->getMessage(),
                'company_id' => $company->id
            ]);
        }
    }
}