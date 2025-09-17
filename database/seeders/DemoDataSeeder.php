<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Company;
use App\Models\Service;
use App\Models\Employee;
use App\Models\Client;
use App\Models\Feedback;
use App\Models\ValidationLog;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Création des données de démonstration...');

        // 1. Créer un utilisateur gérant de démonstration
        $user = User::create([
            'first_name' => 'Marie',
            'last_name' => 'Dubois',
            'email' => 'marie.dubois@hotel-royal.com',
            'phone' => '+33123456789',
            'password' => Hash::make('password123'),
            'role' => 'manager',
        ]);

        $this->command->info('✅ Utilisateur gérant créé: ' . $user->email);

        // 2. Créer l'entreprise de démonstration
        $company = Company::create([
            'manager_id' => $user->id,
            'name' => 'Hôtel Royal Paris',
            'email' => 'contact@hotel-royal.com',
            'location' => '15 Avenue des Champs-Élysées, 75008 Paris',
            'category' => 'hotel',
            'employees_count' => 45,
            'creation_year' => 2010,
            'phone' => '+33142567890',
        ]);

        $this->command->info('✅ Entreprise créée: ' . $company->name);

        // 3. Créer les services
        $services = [
            ['name' => 'Réception', 'color' => '#3B82F6', 'icon' => 'phone', 'description' => 'Accueil et réception des clients'],
            ['name' => 'Direction', 'color' => '#8B5CF6', 'icon' => 'user-tie', 'description' => 'Direction générale'],
            ['name' => 'Restauration', 'color' => '#10B981', 'icon' => 'utensils', 'description' => 'Restaurant et room service'],
            ['name' => 'Housekeeping', 'color' => '#F59E0B', 'icon' => 'home', 'description' => 'Entretien et nettoyage'],
            ['name' => 'Conciergerie', 'color' => '#EF4444', 'icon' => 'briefcase', 'description' => 'Services de conciergerie'],
            ['name' => 'Spa & Bien-être', 'color' => '#EC4899', 'icon' => 'heart', 'description' => 'Centre de bien-être et spa'],
            ['name' => 'Maintenance', 'color' => '#6B7280', 'icon' => 'tools', 'description' => 'Maintenance technique'],
        ];

        foreach ($services as $serviceData) {
            $service = $company->services()->create($serviceData);
            $this->command->info("✅ Service créé: {$service->name}");
        }

        // 4. Créer les employés
        $employees = [
            // Réception
            ['service' => 'Réception', 'first_name' => 'Jean', 'last_name' => 'Martin', 'position' => 'Réceptionniste Senior', 'email' => 'j.martin@hotel-royal.com'],
            ['service' => 'Réception', 'first_name' => 'Sophie', 'last_name' => 'Durand', 'position' => 'Réceptionniste', 'email' => 's.durand@hotel-royal.com'],
            ['service' => 'Réception', 'first_name' => 'Lucas', 'last_name' => 'Bernard', 'position' => 'Réceptionniste de nuit', 'email' => 'l.bernard@hotel-royal.com'],
            
            // Direction
            ['service' => 'Direction', 'first_name' => 'Pierre', 'last_name' => 'Moreau', 'position' => 'Directeur Général', 'email' => 'p.moreau@hotel-royal.com'],
            ['service' => 'Direction', 'first_name' => 'Isabelle', 'last_name' => 'Leroy', 'position' => 'Directrice Commerciale', 'email' => 'i.leroy@hotel-royal.com'],
            
            // Restauration
            ['service' => 'Restauration', 'first_name' => 'Antoine', 'last_name' => 'Rousseau', 'position' => 'Chef Exécutif', 'email' => 'a.rousseau@hotel-royal.com'],
            ['service' => 'Restauration', 'first_name' => 'Claire', 'last_name' => 'Girard', 'position' => 'Responsable Restaurant', 'email' => 'c.girard@hotel-royal.com'],
            ['service' => 'Restauration', 'first_name' => 'Mohamed', 'last_name' => 'Benali', 'position' => 'Serveur Senior', 'email' => 'm.benali@hotel-royal.com'],
            
            // Housekeeping
            ['service' => 'Housekeeping', 'first_name' => 'Carmen', 'last_name' => 'Silva', 'position' => 'Gouvernante Générale', 'email' => 'c.silva@hotel-royal.com'],
            ['service' => 'Housekeeping', 'first_name' => 'Fatima', 'last_name' => 'Ahmed', 'position' => 'Femme de chambre', 'email' => 'f.ahmed@hotel-royal.com'],
            
            // Conciergerie
            ['service' => 'Conciergerie', 'first_name' => 'François', 'last_name' => 'Lambert', 'position' => 'Chef Concierge', 'email' => 'f.lambert@hotel-royal.com'],
            
            // Spa
            ['service' => 'Spa & Bien-être', 'first_name' => 'Amélie', 'last_name' => 'Roux', 'position' => 'Responsable Spa', 'email' => 'a.roux@hotel-royal.com'],
            ['service' => 'Spa & Bien-être', 'first_name' => 'Léa', 'last_name' => 'Bonnet', 'position' => 'Masseuse', 'email' => 'l.bonnet@hotel-royal.com'],
            
            // Maintenance
            ['service' => 'Maintenance', 'first_name' => 'Paul', 'last_name' => 'Mercier', 'position' => 'Technicien Principal', 'email' => 'p.mercier@hotel-royal.com'],
            ['service' => 'Maintenance', 'first_name' => 'David', 'last_name' => 'Blanc', 'position' => 'Électricien', 'email' => 'd.blanc@hotel-royal.com'],
        ];

        foreach ($employees as $empData) {
            $service = $company->services()->where('name', $empData['service'])->first();
            if ($service) {
                $employee = $company->employees()->create([
                    'service_id' => $service->id,
                    'first_name' => $empData['first_name'],
                    'last_name' => $empData['last_name'],
                    'position' => $empData['position'],
                    'email' => $empData['email'],
                    'phone' => '+3315' . rand(10000000, 99999999),
                    'hire_date' => Carbon::now()->subMonths(rand(6, 36)),
                    'is_active' => true,
                ]);
                $this->command->info("✅ Employé créé: {$employee->full_name} ({$empData['service']})");
            }
        }

        // 5. Créer des clients de démonstration
        $clients = [
            ['first_name' => 'Marc', 'last_name' => 'Dupont', 'email' => 'marc.dupont@email.com', 'phone' => '+33601234567'],
            ['first_name' => 'Julie', 'last_name' => 'Martin', 'email' => 'julie.martin@email.com', 'phone' => '+33602345678'],
            ['first_name' => 'Pierre', 'last_name' => 'Bernard', 'email' => 'pierre.bernard@email.com', 'phone' => '+33603456789'],
            ['first_name' => 'Sarah', 'last_name' => 'Dubois', 'email' => 'sarah.dubois@email.com', 'phone' => '+33604567890'],
            ['first_name' => 'Thomas', 'last_name' => 'Moreau', 'email' => 'thomas.moreau@email.com', 'phone' => '+33605678901'],
            ['first_name' => 'Camille', 'last_name' => 'Leroy', 'email' => 'camille.leroy@email.com', 'phone' => '+33606789012'],
            ['first_name' => 'Nicolas', 'last_name' => 'Garcia', 'email' => 'nicolas.garcia@email.com', 'phone' => '+33607890123'],
            ['first_name' => 'Emma', 'last_name' => 'Martinez', 'email' => 'emma.martinez@email.com', 'phone' => '+33608901234'],
        ];

        foreach ($clients as $clientData) {
            $client = Client::create(array_merge($clientData, [
                'first_feedback_at' => Carbon::now()->subDays(rand(1, 90)),
                'total_feedbacks' => rand(1, 5),
                'total_kalipoints' => rand(5, 25),
                'bonus_kalipoints' => rand(0, 10),
            ]));
            $this->command->info("✅ Client créé: {$client->full_name}");
        }

        // 6. Créer des feedbacks de démonstration
        $this->createDemoFeedbacks($company);

        $this->command->info('🎉 Données de démonstration créées avec succès !');
        $this->command->info('');
        $this->command->info('📧 Compte de démonstration:');
        $this->command->info('   Email: marie.dubois@hotel-royal.com');
        $this->command->info('   Mot de passe: password123');
        $this->command->info('');
        $this->command->info('🏢 Entreprise: Hôtel Royal Paris');
        $this->command->info('📊 Données générées:');
        $this->command->info('   - ' . $company->services()->count() . ' services');
        $this->command->info('   - ' . $company->employees()->count() . ' employés');
        $this->command->info('   - ' . Client::count() . ' clients');
        $this->command->info('   - ' . $company->feedbacks()->count() . ' feedbacks');
    }

    private function createDemoFeedbacks($company)
    {
        $services = $company->services()->get();
        $employees = $company->employees()->get();
        $clients = Client::all();

        $feedbackTemplates = [
            // Appréciations
            'appreciation' => [
                ['title' => 'Excellent accueil', 'description' => 'Personnel très accueillant et professionnel. L\'équipe de réception nous a parfaitement orientés.'],
                ['title' => 'Service remarquable', 'description' => 'Service impeccable, chambres très propres et petit-déjeuner délicieux.'],
                ['title' => 'Séjour parfait', 'description' => 'Tout était parfait, de l\'accueil au départ. Personnel aux petits soins.'],
                ['title' => 'Très satisfait', 'description' => 'Très bon hôtel, personnel aimable et souriant. Nous recommandons !'],
                ['title' => 'Service exceptionnel', 'description' => 'Le service de conciergerie nous a trouvé d\'excellents restaurants. Bravo !'],
            ],
            
            // Incidents
            'incident' => [
                ['title' => 'Problème de climatisation', 'description' => 'La climatisation de la chambre 205 ne fonctionnait pas correctement. Il faisait très chaud.'],
                ['title' => 'Attente trop longue', 'description' => 'Attente de 30 minutes au check-in alors que nous avions réservé.'],
                ['title' => 'Chambre mal nettoyée', 'description' => 'La salle de bain n\'était pas propre à notre arrivée. Cheveux dans la douche.'],
                ['title' => 'Bruit dans le couloir', 'description' => 'Beaucoup de bruit dans les couloirs jusqu\'à tard dans la nuit.'],
                ['title' => 'Wifi défaillant', 'description' => 'Connection internet très lente, impossible de travailler depuis la chambre.'],
            ],
            
            // Suggestions
            'suggestion' => [
                ['title' => 'Améliorer le petit-déjeuner', 'description' => 'Proposer plus d\'options végétariennes au petit-déjeuner buffet.'],
                ['title' => 'Application mobile', 'description' => 'Une application pour commander le room service serait très pratique.'],
                ['title' => 'Horaires spa étendus', 'description' => 'Ouvrir le spa plus tôt le matin pour les clients matinaux.'],
                ['title' => 'Station de recharge', 'description' => 'Installer des bornes de recharge pour voitures électriques au parking.'],
                ['title' => 'Check-in express', 'description' => 'Mettre en place un système de check-in automatique pour gagner du temps.'],
            ]
        ];

        // Générer des feedbacks sur les 6 derniers mois
        for ($month = 5; $month >= 0; $month--) {
            $monthDate = Carbon::now()->subMonths($month);
            $feedbacksThisMonth = rand(15, 25);

            for ($i = 0; $i < $feedbacksThisMonth; $i++) {
                $type = ['appreciation', 'incident', 'suggestion'][array_rand(['appreciation', 'incident', 'suggestion'])];
                $template = $feedbackTemplates[$type][array_rand($feedbackTemplates[$type])];
                
                $client = $clients->random();
                $service = $services->random();
                $employee = $employees->where('service_id', $service->id)->random();

                $kalipoints = match($type) {
                    'appreciation' => rand(3, 5),
                    'incident' => 0,
                    'suggestion' => 2,
                };

                $status = $this->getRandomStatus($type);
                $createdAt = $monthDate->copy()->addDays(rand(0, 27))->addHours(rand(8, 22));

                $feedback = Feedback::create([
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                    'employee_id' => $employee?->id,
                    'service_id' => $service->id,
                    'type' => $type,
                    'status' => $status,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'kalipoints' => $kalipoints,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Traiter certains feedbacks
                if (in_array($status, ['treated', 'resolved', 'implemented']) && rand(0, 1)) {
                    $feedback->update([
                        'admin_resolution_description' => $this->getResolutionDescription($type, $template['title']),
                        'admin_comments' => 'Traité par l\'équipe.',
                        'treated_by_user_id' => $company->manager_id,
                        'treated_at' => $createdAt->copy()->addHours(rand(2, 48)),
                    ]);

                    // Générer des validations client pour certains feedbacks
                    if ($type !== 'appreciation' && rand(0, 2) == 0) {
                        $this->createValidation($feedback);
                    }
                }

                // Mettre à jour les stats du client
                if ($type !== 'incident' && $kalipoints > 0) {
                    $client->increment('total_kalipoints', $kalipoints);
                }
                $client->update(['last_feedback_at' => $createdAt]);
            }
        }

        $this->command->info('✅ ' . $company->feedbacks()->count() . ' feedbacks créés');
    }

    private function getRandomStatus($type)
    {
        $statuses = match($type) {
            'appreciation' => ['new', 'archived'],
            'incident' => ['new', 'in_progress', 'treated', 'resolved', 'partially_resolved'],
            'suggestion' => ['new', 'in_progress', 'treated', 'implemented', 'partially_implemented'],
        };

        return $statuses[array_rand($statuses)];
    }

    private function getResolutionDescription($type, $title)
    {
        $resolutions = match($type) {
            'incident' => [
                'Nous avons immédiatement contacté notre équipe technique pour résoudre le problème.',
                'Le problème a été identifié et corrigé. Nous nous excusons pour la gêne occasionnée.',
                'Notre équipe a pris les mesures nécessaires pour éviter que cela se reproduise.',
                'Nous avons procédé aux réparations et vérifications nécessaires.',
            ],
            'suggestion' => [
                'Merci pour cette excellente suggestion. Nous l\'étudions avec attention.',
                'Votre idée nous intéresse beaucoup. Nous travaillons à sa mise en œuvre.',
                'Suggestion très pertinente que nous avons décidé d\'implémenter.',
                'Nous avons pris en compte votre recommandation dans nos améliorations.',
            ],
            default => 'Merci pour votre retour.'
        };

        return $resolutions[array_rand($resolutions)];
    }

    private function createValidation($feedback)
    {
        $validationStatuses = ['satisfied', 'partially_satisfied', 'not_satisfied'];
        $status = $validationStatuses[array_rand($validationStatuses)];
        $rating = rand(2, 5);
        
        $bonusPoints = match([$feedback->type, $status]) {
            ['incident', 'satisfied'] => 3,
            ['incident', 'partially_satisfied'] => 1,
            ['suggestion', 'satisfied'] => 5,
            ['suggestion', 'partially_satisfied'] => 2,
            default => 0
        };

        if ($rating >= 4) $bonusPoints += 1;

        $feedback->validateByClient($status, $rating, 'Merci pour votre réactivité.', $bonusPoints);

        ValidationLog::createFromValidation($feedback, [
            'status' => $status,
            'rating' => $rating,
            'comment' => 'Validation automatique générée par le seeder',
            'bonus_points' => $bonusPoints,
        ], (object) ['ip' => '127.0.0.1', 'header' => fn() => 'Seeder']);
    }
}