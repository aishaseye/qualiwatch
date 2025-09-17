<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Feedback;
use App\Models\User;
use App\Models\Escalation;
use App\Services\EscalationService;
use Carbon\Carbon;

// Test final des notifications d'escalation

class NotificationTester
{
    private $companyId = '9fde0f86-211a-46ce-91db-8672e878797b';
    private $escalationService;

    public function __construct()
    {
        // Initialiser Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        $this->escalationService = new EscalationService();
    }

    public function test()
    {
        echo "🔔 TEST FINAL DES NOTIFICATIONS D'ESCALATION\n";
        echo "============================================\n\n";

        $this->verifyUsers();
        $this->checkEscalationNotifications();
        $this->simulateNewEscalation();
        $this->verifyNotificationChannels();
    }

    private function verifyUsers()
    {
        echo "1. Vérification des utilisateurs pour notifications\n";
        echo "--------------------------------------------------\n";

        $users = User::where('company_id', $this->companyId)->get();

        echo "👥 Utilisateurs de l'entreprise ({$users->count()}):\n";
        foreach ($users->groupBy('role') as $role => $roleUsers) {
            echo "   {$role}:\n";
            foreach ($roleUsers as $user) {
                echo "     - {$user->full_name} ({$user->email})\n";
            }
        }

        // Vérifier le manager spécifique
        $manager = User::where('email', 'sulamaish4738@gmail.com')->first();
        if ($manager) {
            echo "\n✅ Manager principal trouvé: {$manager->full_name} (Entreprise: {$manager->company_id})\n";
        } else {
            echo "\n❌ Manager principal non trouvé\n";
        }

        echo "\n";
    }

    private function checkEscalationNotifications()
    {
        echo "2. Analyse des escalations et notifications\n";
        echo "-------------------------------------------\n";

        $escalations = Escalation::whereHas('feedback', function($q) {
                                    $q->where('company_id', $this->companyId);
                                })
                                ->where('is_resolved', false)
                                ->orderBy('escalation_level', 'desc')
                                ->take(5)
                                ->get();

        echo "🚨 Escalations actives (top 5):\n";
        foreach ($escalations as $escalation) {
            $notified = $escalation->notified_at ? '✅ Notifiée' : '❌ Non notifiée';
            echo "   - Niveau {$escalation->escalation_level}: {$notified}\n";
            echo "     Feedback: {$escalation->feedback->reference}\n";
            echo "     Créée: {$escalation->escalated_at->format('Y-m-d H:i:s')}\n";

            if ($escalation->notified_at) {
                $channels = $escalation->notification_channels ? implode(', ', $escalation->notification_channels) : 'Aucun';
                echo "     Canaux: {$channels}\n";
                echo "     Destinataires: " . ($escalation->notified_users_count ?? 0) . "\n";
            }
            echo "\n";
        }
    }

    private function simulateNewEscalation()
    {
        echo "3. Simulation d'une nouvelle escalation\n";
        echo "---------------------------------------\n";

        // Créer un feedback de test avec une date ancienne pour forcer l'escalation
        try {
            echo "🔄 Création d'un feedback de test...\n";

            $testFeedback = \App\Models\Feedback::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'company_id' => $this->companyId,
                'feedback_type_id' => '9fce4bff-a06f-45d6-a371-479b7b0df575', // Type négatif
                'feedback_status_id' => '1', // Statut new
                'client_id' => 'test-client',
                'content' => 'Test d\'escalation avec notification',
                'rating' => 1,
                'sentiment' => 'en_colere',
                'reference' => 'TEST-' . now()->format('YmdHis'),
                'created_at' => Carbon::now()->subHours(5), // 5 heures dans le passé
                'updated_at' => now()
            ]);

            echo "✅ Feedback de test créé: {$testFeedback->reference}\n";

            // Tester l'escalation
            echo "🔄 Test d'escalation...\n";
            $escalationResult = $this->escalationService->checkFeedbackForEscalation($testFeedback);

            if ($escalationResult) {
                echo "✅ Escalation créée avec succès !\n";

                // Vérifier les nouvelles escalations
                $newEscalations = Escalation::where('feedback_id', $testFeedback->id)->get();

                foreach ($newEscalations as $escalation) {
                    $notified = $escalation->notified_at ? 'Oui' : 'Non';
                    echo "   - Niveau {$escalation->escalation_level}: Notifiée = {$notified}\n";
                    echo "     Raison: {$escalation->trigger_reason}\n";

                    if ($escalation->notified_at) {
                        echo "     ✅ Notification envoyée le {$escalation->notified_at}\n";
                    } else {
                        echo "     ❌ Notification non envoyée\n";
                    }
                }

                // Nettoyage
                echo "\n🧹 Nettoyage du feedback de test...\n";
                $newEscalations->each->delete();
                $testFeedback->delete();
                echo "✅ Nettoyage terminé\n";

            } else {
                echo "ℹ️  Aucune escalation déclenchée\n";
                $testFeedback->delete();
            }

        } catch (Exception $e) {
            echo "❌ Erreur lors de la simulation: {$e->getMessage()}\n";
        }

        echo "\n";
    }

    private function verifyNotificationChannels()
    {
        echo "4. Vérification des canaux de notification\n";
        echo "------------------------------------------\n";

        $slaRules = \App\Models\SlaRule::forCompany($this->companyId)
                                      ->active()
                                      ->get();

        echo "📋 Canaux configurés par règle SLA:\n";
        foreach ($slaRules as $rule) {
            $channels = $rule->notification_channels ? implode(', ', $rule->notification_channels) : 'email (défaut)';
            echo "   - {$rule->name}: {$channels}\n";

            $recipients1 = $rule->level_1_recipients ? implode(', ', $rule->level_1_recipients) : 'Aucun';
            $recipients2 = $rule->level_2_recipients ? implode(', ', $rule->level_2_recipients) : 'Aucun';
            $recipients3 = $rule->level_3_recipients ? implode(', ', $rule->level_3_recipients) : 'Aucun';

            echo "     Niveau 1: {$recipients1}\n";
            echo "     Niveau 2: {$recipients2}\n";
            echo "     Niveau 3: {$recipients3}\n";
        }

        // Vérifier le service de notification
        echo "\n🔧 Test du service de notification...\n";

        try {
            // Vérifier que les classes de notification existent
            $emailClass = class_exists('\App\Mail\EscalationNotification');
            echo "   Classe email d'escalation: " . ($emailClass ? '✅ Disponible' : '❌ Manquante') . "\n";

            $notificationModel = class_exists('\App\Models\Notification');
            echo "   Modèle Notification: " . ($notificationModel ? '✅ Disponible' : '❌ Manquant') . "\n";

            // Vérifier la configuration mail
            $mailConfig = config('mail.default');
            echo "   Configuration mail: {$mailConfig}\n";

        } catch (Exception $e) {
            echo "   ❌ Erreur lors de la vérification: {$e->getMessage()}\n";
        }

        echo "\n✅ Test terminé\n";
    }
}

// Exécution du test
if (php_sapi_name() === 'cli') {
    $tester = new NotificationTester();
    $tester->test();
}