<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

// Script pour créer les utilisateurs manquants pour les escalations

class EscalationUserFixer
{
    private $companyId = '9fde0f86-211a-46ce-91db-8672e878797b';

    public function __construct()
    {
        // Initialiser Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function fix()
    {
        echo "🔧 CORRECTION DES UTILISATEURS D'ESCALATION\n";
        echo "==========================================\n\n";

        $this->checkExistingUsers();
        $this->createMissingUsers();
        $this->testNotificationSystem();
    }

    private function checkExistingUsers()
    {
        echo "1. Vérification des utilisateurs existants\n";
        echo "-----------------------------------------\n";

        $company = Company::find($this->companyId);
        echo "🏢 Entreprise: {$company->name}\n\n";

        $users = User::where('company_id', $this->companyId)->get();

        echo "👥 Utilisateurs existants ({$users->count()}):\n";
        foreach ($users as $user) {
            echo "   - {$user->full_name} ({$user->email}) - Rôle: {$user->role}\n";
        }

        // Vérifier les rôles manquants
        $existingRoles = $users->pluck('role')->unique()->toArray();
        $requiredRoles = ['manager', 'director', 'ceo'];
        $missingRoles = array_diff($requiredRoles, $existingRoles);

        if (!empty($missingRoles)) {
            echo "\n❌ Rôles manquants: " . implode(', ', $missingRoles) . "\n";
        } else {
            echo "\n✅ Tous les rôles requis sont présents\n";
        }

        echo "\n";
    }

    private function createMissingUsers()
    {
        echo "2. Création des utilisateurs manquants\n";
        echo "-------------------------------------\n";

        // Vérifier si le manager existe déjà
        $manager = User::where('email', 'sulamaish4738@gmail.com')
                      ->where('company_id', $this->companyId)
                      ->first();

        if (!$manager) {
            echo "🔄 Création du manager (sulamaish4738@gmail.com)...\n";
            try {
                $manager = User::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'company_id' => $this->companyId,
                    'first_name' => 'Sulamaish',
                    'last_name' => 'Manager',
                    'email' => 'sulamaish4738@gmail.com',
                    'password' => Hash::make('passer123'),
                    'role' => 'manager',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                echo "✅ Manager créé avec succès\n";
            } catch (Exception $e) {
                echo "❌ Erreur création manager: {$e->getMessage()}\n";
            }
        } else {
            echo "✅ Manager existe déjà: {$manager->full_name}\n";
        }

        // Vérifier si le directeur existe
        $director = User::where('email', 'mouha712@gmail.com')
                       ->where('company_id', $this->companyId)
                       ->where('role', 'director')
                       ->first();

        if (!$director) {
            echo "🔄 Création du directeur (mouha712@gmail.com)...\n";
            try {
                $director = User::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'company_id' => $this->companyId,
                    'first_name' => 'Mouha',
                    'last_name' => 'Director',
                    'email' => 'mouha712@gmail.com',
                    'password' => Hash::make('password123'),
                    'role' => 'director',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                echo "✅ Directeur créé avec succès\n";
            } catch (Exception $e) {
                echo "❌ Erreur création directeur: {$e->getMessage()}\n";
            }
        } else {
            echo "✅ Directeur existe déjà: {$director->full_name}\n";
        }

        // Vérifier si le CEO existe
        $ceo = User::where('company_id', $this->companyId)
                  ->where('role', 'ceo')
                  ->first();

        if (!$ceo) {
            echo "🔄 Création du CEO (mouha712@gmail.com)...\n";
            try {
                // Vérifier si un utilisateur avec cet email existe déjà
                $existingUser = User::where('email', 'mouha712@gmail.com')
                                   ->where('company_id', $this->companyId)
                                   ->first();

                if ($existingUser && $existingUser->role !== 'ceo') {
                    // Créer un CEO avec un email légèrement différent
                    $ceoEmail = 'mouha712+ceo@gmail.com';
                } else {
                    $ceoEmail = 'mouha712@gmail.com';
                }

                $ceo = User::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'company_id' => $this->companyId,
                    'first_name' => 'Mouha',
                    'last_name' => 'CEO',
                    'email' => $ceoEmail,
                    'password' => Hash::make('password123'),
                    'role' => 'ceo',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                echo "✅ CEO créé avec succès (email: {$ceoEmail})\n";
            } catch (Exception $e) {
                echo "❌ Erreur création CEO: {$e->getMessage()}\n";
            }
        } else {
            echo "✅ CEO existe déjà: {$ceo->full_name} ({$ceo->email})\n";
        }

        echo "\n";
    }

    private function testNotificationSystem()
    {
        echo "3. Test du système de notification\n";
        echo "----------------------------------\n";

        // Lister tous les utilisateurs après création
        $users = User::where('company_id', $this->companyId)->get();

        echo "👥 Utilisateurs disponibles pour notifications:\n";
        foreach ($users->groupBy('role') as $role => $roleUsers) {
            echo "   {$role}:\n";
            foreach ($roleUsers as $user) {
                echo "     - {$user->full_name} ({$user->email})\n";
            }
        }

        // Tester une escalation
        echo "\n🧪 Test de création d'escalation...\n";

        try {
            $escalationService = new \App\Services\EscalationService();

            // Récupérer un feedback pour test
            $feedback = \App\Models\Feedback::where('company_id', $this->companyId)
                                           ->where('feedback_type_id', '9fce4bff-a06f-45d6-a371-479b7b0df575')
                                           ->first();

            if ($feedback) {
                echo "📝 Feedback de test: {$feedback->reference}\n";

                // Forcer une vérification d'escalation
                $result = $escalationService->checkFeedbackForEscalation($feedback);

                if ($result) {
                    echo "✅ Nouvelle escalation créée avec succès\n";

                    // Vérifier si les notifications ont été envoyées
                    $recentEscalations = \App\Models\Escalation::where('feedback_id', $feedback->id)
                                                              ->where('created_at', '>', now()->subMinutes(5))
                                                              ->get();

                    foreach ($recentEscalations as $escalation) {
                        $notified = $escalation->notified_at ? 'Oui' : 'Non';
                        echo "   Escalation niveau {$escalation->escalation_level}: Notifiée = {$notified}\n";
                    }
                } else {
                    echo "ℹ️  Aucune nouvelle escalation nécessaire\n";
                }
            } else {
                echo "❌ Aucun feedback trouvé pour test\n";
            }

        } catch (Exception $e) {
            echo "❌ Erreur lors du test: {$e->getMessage()}\n";
        }

        echo "\n";
    }
}

// Exécution du script de correction
if (php_sapi_name() === 'cli') {
    $fixer = new EscalationUserFixer();
    $fixer->fix();
}