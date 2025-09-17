<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Company;

// Script pour vérifier et corriger le manager de l'entreprise

class ManagerVerifier
{
    private $companyId = '9fde0f86-211a-46ce-91db-8672e878797b';
    private $managerEmail = 'sulamaish4738@gmail.com';

    public function __construct()
    {
        // Initialiser Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function verify()
    {
        echo "👤 VÉRIFICATION DU MANAGER DE L'ENTREPRISE\n";
        echo "==========================================\n\n";

        $this->checkCompany();
        $this->checkManager();
        $this->showFinalStatus();
    }

    private function checkCompany()
    {
        echo "1. Informations de l'entreprise\n";
        echo "-------------------------------\n";

        $company = Company::find($this->companyId);
        if (!$company) {
            echo "❌ Entreprise non trouvée\n";
            return;
        }

        echo "🏢 Entreprise: {$company->name}\n";
        echo "📧 Email: {$company->email}\n";
        echo "📱 Téléphone: {$company->phone}\n";
        echo "🆔 ID: {$company->id}\n\n";
    }

    private function checkManager()
    {
        echo "2. Vérification du manager\n";
        echo "-------------------------\n";

        // Chercher l'utilisateur avec cet email
        $user = User::where('email', $this->managerEmail)->first();

        if (!$user) {
            echo "❌ Utilisateur {$this->managerEmail} non trouvé dans la base\n";
            return;
        }

        echo "✅ Utilisateur trouvé: {$user->full_name}\n";
        echo "📧 Email: {$user->email}\n";
        echo "🏢 Entreprise actuelle: {$user->company_id}\n";
        echo "👔 Rôle: {$user->role}\n";

        // Vérifier si c'est le bon entreprise
        if ($user->company_id !== $this->companyId) {
            echo "🔄 Correction nécessaire - assignation à la bonne entreprise...\n";

            $user->update(['company_id' => $this->companyId]);
            echo "✅ Manager assigné à l'entreprise {$this->companyId}\n";
        } else {
            echo "✅ Manager déjà dans la bonne entreprise\n";
        }

        // Vérifier le rôle
        if ($user->role !== 'manager') {
            echo "🔄 Correction du rôle en 'manager'...\n";

            $user->update(['role' => 'manager']);
            echo "✅ Rôle mis à jour vers 'manager'\n";
        } else {
            echo "✅ Rôle correct: manager\n";
        }

        echo "\n";
    }

    private function showFinalStatus()
    {
        echo "3. État final de l'équipe\n";
        echo "------------------------\n";

        // Afficher tous les utilisateurs de l'entreprise
        $users = User::where('company_id', $this->companyId)->get();

        echo "👥 Équipe complète de l'entreprise ({$users->count()} membres):\n\n";

        foreach ($users->groupBy('role') as $role => $roleUsers) {
            echo "🏷️  {$role}:\n";
            foreach ($roleUsers as $user) {
                $highlight = $user->email === $this->managerEmail ? ' ⭐' : '';
                echo "   - {$user->full_name} ({$user->email}){$highlight}\n";
            }
            echo "\n";
        }

        // Vérifier la configuration SLA
        echo "🔧 Vérification des règles SLA pour les managers:\n";
        $slaRules = \App\Models\SlaRule::forCompany($this->companyId)
                                      ->active()
                                      ->get();

        $hasManagerInSla = false;
        foreach ($slaRules as $rule) {
            $level1Recipients = $rule->level_1_recipients ?? [];
            if (in_array('manager', $level1Recipients)) {
                echo "   ✅ {$rule->name} - Niveau 1 → manager\n";
                $hasManagerInSla = true;
            }
        }

        if (!$hasManagerInSla) {
            echo "   ⚠️  Aucune règle SLA ne notifie les managers\n";
        }

        echo "\n✅ VÉRIFICATION TERMINÉE !\n";
        echo "👤 {$this->managerEmail} est maintenant correctement configuré comme manager de l'entreprise\n";
    }
}

// Exécution de la vérification
if (php_sapi_name() === 'cli') {
    $verifier = new ManagerVerifier();
    $verifier->verify();
}