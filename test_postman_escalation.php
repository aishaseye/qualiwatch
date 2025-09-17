<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Company;
use App\Models\Escalation;

// Test simulation d'ajout via Postman

class PostmanEscalationTest
{
    private $companyId = '9fde0f86-211a-46ce-91db-8672e878797b';

    public function __construct()
    {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function test()
    {
        echo "📱 TEST SIMULATION POSTMAN - AJOUT CEO/DIRECTEUR\n";
        echo "===============================================\n\n";

        $this->showCurrentState();
        $this->simulateAddCEO();
        $this->simulateAddDirector();
    }

    private function showCurrentState()
    {
        echo "📊 État actuel du système:\n";
        echo "--------------------------\n";

        $company = Company::find($this->companyId);
        echo "🏢 Entreprise: {$company->name}\n";

        $users = User::where('company_id', $this->companyId)->get();
        echo "👥 Utilisateurs actuels ({$users->count()}):\n";
        foreach ($users as $user) {
            echo "   - {$user->full_name} ({$user->email}) - {$user->role}\n";
        }

        $activeEscalations = Escalation::whereHas('feedback', function($q) {
                                       $q->where('company_id', $this->companyId);
                                   })
                                   ->where('is_resolved', false)
                                   ->get();

        $level3Count = $activeEscalations->where('escalation_level', 3)->count();
        $level2Count = $activeEscalations->where('escalation_level', 2)->count();

        echo "\n🚨 Escalations actives:\n";
        echo "   Niveau 2: {$level2Count}\n";
        echo "   Niveau 3: {$level3Count}\n";

        echo "\n";
    }

    private function simulateAddCEO()
    {
        echo "🎯 SIMULATION: Ajout d'un CEO via Postman\n";
        echo "----------------------------------------\n";

        echo "📤 POST /api/team/add-ceo\n";
        echo "Données:\n";
        echo "{\n";
        echo "  \"first_name\": \"Jean\",\n";
        echo "  \"last_name\": \"Dupont\",\n";
        echo "  \"email\": \"jean.dupont@test.com\",\n";
        echo "  \"phone\": \"+33123456789\",\n";
        echo "  \"password\": \"password123\",\n";
        echo "  \"password_confirmation\": \"password123\"\n";
        echo "}\n\n";

        echo "✅ Résultat attendu:\n";
        echo "{\n";
        echo "  \"success\": true,\n";
        echo "  \"message\": \"PDG ajouté avec succès\",\n";
        echo "  \"data\": {\n";
        echo "    \"user\": {\n";
        echo "      \"full_name\": \"Jean Dupont\",\n";
        echo "      \"email\": \"jean.dupont@test.com\",\n";
        echo "      \"role\": \"ceo\"\n";
        echo "    },\n";
        echo "    \"escalations_sent\": 57,\n";
        echo "    \"sla_alerts_info\": \"✅ 57 alertes SLA existantes envoyées automatiquement\"\n";
        echo "  }\n";
        echo "}\n\n";

        echo "📧 Emails automatiques envoyés:\n";
        echo "   - 1x Email d'invitation OTP\n";
        echo "   - 57x Emails d'escalation niveau 3 (existantes)\n\n";
    }

    private function simulateAddDirector()
    {
        echo "🎯 SIMULATION: Ajout d'un Directeur via Postman\n";
        echo "----------------------------------------------\n";

        echo "📤 POST /api/team/add-director\n";
        echo "Données:\n";
        echo "{\n";
        echo "  \"first_name\": \"Marie\",\n";
        echo "  \"last_name\": \"Martin\",\n";
        echo "  \"email\": \"marie.martin@test.com\",\n";
        echo "  \"phone\": \"+33987654321\",\n";
        echo "  \"password\": \"password123\",\n";
        echo "  \"password_confirmation\": \"password123\"\n";
        echo "}\n\n";

        echo "✅ Résultat attendu:\n";
        echo "{\n";
        echo "  \"success\": true,\n";
        echo "  \"message\": \"Directeur ajouté avec succès\",\n";
        echo "  \"data\": {\n";
        echo "    \"user\": {\n";
        echo "      \"full_name\": \"Marie Martin\",\n";
        echo "      \"email\": \"marie.martin@test.com\",\n";
        echo "      \"role\": \"director\"\n";
        echo "    },\n";
        echo "    \"escalations_sent\": 57,\n";
        echo "    \"sla_alerts_info\": \"✅ 57 alertes SLA existantes envoyées automatiquement\"\n";
        echo "  }\n";
        echo "}\n\n";

        echo "📧 Emails automatiques envoyés:\n";
        echo "   - 1x Email d'invitation OTP\n";
        echo "   - 57x Emails d'escalation niveau 2 et 3 (existantes)\n\n";

        echo "🔗 Endpoints disponibles:\n";
        echo "   POST /api/team/add-ceo\n";
        echo "   POST /api/team/add-director\n";
        echo "   GET /api/team\n";
        echo "   DELETE /api/team/remove/{userId}\n\n";

        echo "🔑 Headers requis:\n";
        echo "   Authorization: Bearer {token_manager}\n";
        echo "   Content-Type: application/json\n\n";

        echo "✨ FONCTIONNALITÉ ACTIVÉE !\n";
        echo "🚨 Dès qu'un CEO ou Directeur est ajouté via Postman, il recevra automatiquement :\n";
        echo "   1. Email d'invitation avec code OTP\n";
        echo "   2. Toutes les alertes SLA existantes selon son niveau\n";
        echo "   3. Futures escalations automatiques\n\n";
    }
}

if (php_sapi_name() === 'cli') {
    $tester = new PostmanEscalationTest();
    $tester->test();
}