<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SlaRule;
use App\Models\FeedbackType;
use App\Models\Company;

class SlaRulesSeeder extends Seeder
{
    public function run()
    {
        // Récupérer les types de feedback
        $positifType = FeedbackType::where('label', 'positif')->first();
        $negatifType = FeedbackType::where('label', 'negatif')->first();
        $suggestionType = FeedbackType::where('label', 'suggestion')->first();

        if (!$positifType || !$negatifType || !$suggestionType) {
            $this->command->error('Types de feedback non trouvés. Exécutez d\'abord FeedbackTypesSeeder.');
            return;
        }

        // Récupérer l'entreprise (Restaurant Le Gourmet ou Aisha SARL)
        $company = Company::where('name', 'like', '%Restaurant Le Gourmet%')->first();
        
        if (!$company) {
            $company = Company::where('name', 'like', '%Aisha%')->first();
        }
        
        if (!$company) {
            $this->command->error('Aucune entreprise trouvée (Aisha SARL ou Restaurant Le Gourmet).');
            return;
        }

        $this->command->info("Configuration SLA pour: {$company->name}");

        // 🎯 RÈGLES SLA COMPLÈTES - Couvrent TOUS les feedbacks
        $slaRules = [
            
            // ==========================================
            // 🔴 FEEDBACKS NÉGATIFS (Priorité haute)
            // ==========================================
            [
                'name' => 'Incident Catastrophique',
                'description' => 'Feedbacks négatifs avec note maximale (5/5) - Situation critique',
                'feedback_type_id' => $negatifType->id,
                'conditions' => ['rating' => '>=5'], // Note 5/5 en négatif = CATASTROPHIQUE
                'priority_level' => 5, // URGENCE
                'first_response_sla' => 30, // 30 minutes ⚡
                'resolution_sla' => 240, // 4h
                'escalation_level_1' => 60,  // 1h → Manager
                'escalation_level_2' => 120, // 2h → Direction  
                'escalation_level_3' => 240, // 4h → PDG
                'level_1_recipients' => ['manager', 'service_head'],
                'level_2_recipients' => ['director', 'quality_manager'],
                'level_3_recipients' => ['ceo', 'board'],
                'notification_channels' => ['email', 'sms', 'app'],
                'sort_order' => 1
            ],
            
            [
                'name' => 'Feedback Négatif Critique',
                'description' => 'Feedbacks négatifs avec note élevée (4/5) - Action immédiate',
                'feedback_type_id' => $negatifType->id,
                'conditions' => ['rating' => '>=4'], // Notes 4-5/5 en négatif = CRITIQUE
                'priority_level' => 4, // CRITIQUE
                'first_response_sla' => 60, // 1h
                'resolution_sla' => 480, // 8h
                'escalation_level_1' => 120,  // 2h → Manager
                'escalation_level_2' => 240,  // 4h → Direction
                'escalation_level_3' => 480,  // 8h → PDG
                'level_1_recipients' => ['manager'],
                'level_2_recipients' => ['director'],
                'level_3_recipients' => ['ceo'],
                'notification_channels' => ['email', 'sms', 'app'],
                'sort_order' => 2
            ],

            [
                'name' => 'Feedback Négatif Standard',
                'description' => 'Feedbacks négatifs moyens (1-3/5) - Traitement normal',
                'feedback_type_id' => $negatifType->id,
                'conditions' => ['rating' => '<=3'], // Notes 1-3/5 en négatif = Normal
                'priority_level' => 3, // NORMAL
                'first_response_sla' => 120, // 2h
                'resolution_sla' => 720, // 12h
                'escalation_level_1' => 240,  // 4h → Manager
                'escalation_level_2' => 480,  // 8h → Direction
                'escalation_level_3' => 720,  // 12h → PDG
                'level_1_recipients' => ['manager'],
                'level_2_recipients' => ['director'],
                'level_3_recipients' => ['ceo'],
                'notification_channels' => ['email', 'app'],
                'sort_order' => 3
            ],

            // ==========================================
            // 💡 SUGGESTIONS (Priorité normale)
            // ==========================================
            [
                'name' => 'Suggestion d\'Amélioration',
                'description' => 'Idées et propositions clients',
                'feedback_type_id' => $suggestionType->id,
                'conditions' => [], // Toutes les suggestions
                'priority_level' => 2, // NORMAL
                'first_response_sla' => 720, // 12h
                'resolution_sla' => 2880, // 48h (2 jours)
                'escalation_level_1' => 1440, // 24h → Manager
                'escalation_level_2' => 2160, // 36h → Direction
                'escalation_level_3' => 2880, // 48h → PDG
                'level_1_recipients' => ['manager'],
                'level_2_recipients' => ['director'],
                'level_3_recipients' => ['ceo'],
                'notification_channels' => ['email', 'app'],
                'sort_order' => 5
            ],

            // ==========================================
            // 🟢 FEEDBACKS POSITIFS (Priorité basse)
            // ==========================================
            [
                'name' => 'Feedback Positif Exceptionnel',
                'description' => 'Clients très satisfaits - Valorisation',
                'feedback_type_id' => $positifType->id,
                'conditions' => ['rating' => '>=5'], // Note 5/5
                'priority_level' => 1, // FAIBLE
                'first_response_sla' => 480, // 8h (remercier rapidement)
                'resolution_sla' => 1440, // 24h
                'escalation_level_1' => 720,  // 12h → Manager
                'escalation_level_2' => 1080, // 18h → Direction
                'escalation_level_3' => 1440, // 24h → PDG
                'level_1_recipients' => ['manager'],
                'level_2_recipients' => ['director'],
                'level_3_recipients' => ['ceo'],
                'notification_channels' => ['email'],
                'sort_order' => 6
            ],

            [
                'name' => 'Feedback Positif Standard',
                'description' => 'Clients satisfaits - Remerciement',
                'feedback_type_id' => $positifType->id,
                'conditions' => ['rating' => '>=4'], // Notes 4-5/5
                'priority_level' => 1, // FAIBLE
                'first_response_sla' => 720, // 12h
                'resolution_sla' => 2160, // 36h
                'escalation_level_1' => 1440, // 24h → Manager
                'escalation_level_2' => 1800, // 30h → Direction
                'escalation_level_3' => 2160, // 36h → PDG
                'level_1_recipients' => ['manager'],
                'level_2_recipients' => ['director'],
                'level_3_recipients' => ['ceo'],
                'notification_channels' => ['email'],
                'sort_order' => 7
            ],

            // ==========================================
            // 🛡️ RÈGLE DE SÉCURITÉ (Tous les autres cas)
            // ==========================================
            [
                'name' => 'SLA Par Défaut',
                'description' => 'Règle de sécurité pour tous les autres feedbacks',
                'feedback_type_id' => $negatifType->id, // On met négatif par sécurité
                'conditions' => [], // Aucune condition = attrape tout
                'priority_level' => 2, // NORMAL
                'first_response_sla' => 240, // 4h
                'resolution_sla' => 720, // 12h
                'escalation_level_1' => 360,  // 6h → Manager
                'escalation_level_2' => 540,  // 9h → Direction
                'escalation_level_3' => 720,  // 12h → PDG
                'level_1_recipients' => ['manager'],
                'level_2_recipients' => ['director'],
                'level_3_recipients' => ['ceo'],
                'notification_channels' => ['email', 'app'],
                'sort_order' => 99 // En dernière position
            ]
        ];

        // Supprimer les anciennes règles de cette entreprise
        SlaRule::where('company_id', $company->id)->delete();
        
        $this->command->info('Anciennes règles SLA supprimées.');

        // Créer les nouvelles règles
        foreach ($slaRules as $ruleData) {
            SlaRule::create([
                'company_id' => $company->id,
                'name' => $ruleData['name'],
                'description' => $ruleData['description'],
                'feedback_type_id' => $ruleData['feedback_type_id'],
                'conditions' => $ruleData['conditions'],
                'priority_level' => $ruleData['priority_level'],
                'first_response_sla' => $ruleData['first_response_sla'],
                'resolution_sla' => $ruleData['resolution_sla'],
                'escalation_level_1' => $ruleData['escalation_level_1'],
                'escalation_level_2' => $ruleData['escalation_level_2'],
                'escalation_level_3' => $ruleData['escalation_level_3'],
                'level_1_recipients' => $ruleData['level_1_recipients'],
                'level_2_recipients' => $ruleData['level_2_recipients'],
                'level_3_recipients' => $ruleData['level_3_recipients'],
                'notification_channels' => $ruleData['notification_channels'],
                'is_active' => true,
                'sort_order' => $ruleData['sort_order']
            ]);

            $this->command->info("✅ Règle '{$ruleData['name']}' créée");
        }

        $this->command->info('');
        $this->command->info('🎯 CONFIGURATION SLA TERMINÉE !');
        $this->command->info('📋 Résumé des règles:');
        $this->command->info('   🔴 Incident Catastrophique: 30min (Note 5/5 négatif = TRÈS GRAVE)');
        $this->command->info('   🔴 Négatif Critique: 1h (Note 4/5 négatif = GRAVE)');
        $this->command->info('   🔴 Négatif Standard: 2h (Notes 1-3/5 négatif = Normal)'); 
        $this->command->info('   💡 Suggestions: 12h (Toutes)');
        $this->command->info('   🟢 Positif Exceptionnel: 8h (Note 5/5)');
        $this->command->info('   🟢 Positif Standard: 12h (Notes 4-5/5)');
        $this->command->info('   🛡️ Par Défaut: 4h (Autres cas)');
        $this->command->info('');
        $this->command->info('✅ MAINTENANT TOUS VOS FEEDBACKS AURONT UN SLA !');
    }
}