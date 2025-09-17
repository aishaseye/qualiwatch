<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Service;
use App\Models\Feedback;
use Illuminate\Support\Facades\DB;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        $clients = Client::all();
        
        // Récupérer les types de feedback
        $positifType = DB::table('feedback_types')->where('name', 'positif')->first();
        $negatifType = DB::table('feedback_types')->where('name', 'negatif')->first();
        $incidentType = DB::table('feedback_types')->where('name', 'incident')->first();
        
        // Récupérer les statuts de feedback
        $statusNew = DB::table('feedback_statuses')->where('name', 'new')->first();
        $statusInProgress = DB::table('feedback_statuses')->where('name', 'in_progress')->first();
        $statusTreated = DB::table('feedback_statuses')->where('name', 'treated')->first();
        $statusResolved = DB::table('feedback_statuses')->where('name', 'resolved')->first();
        
        $statuses = [$statusNew, $statusInProgress, $statusTreated, $statusResolved];

        foreach ($companies as $company) {
            $services = Service::where('company_id', $company->id)->get();
            $employees = Employee::where('company_id', $company->id)->get();

            // Créer des feedbacks variés pour chaque entreprise
            for ($i = 0; $i < 15; $i++) {
                $client = $clients->random();
                $service = $services->random();
                $employee = $employees->random();
                $type = fake()->randomElement(['appreciation', 'incident', 'suggestion']);

                $sectorCode = $company->businessSector?->code ?? 'autres';
                $feedbackData = $this->getFeedbackData($type, $sectorCode);
                
                // Déterminer le feedback_type_id
                $feedbackTypeId = match($type) {
                    'appreciation' => $positifType->id,
                    'suggestion' => $negatifType->id, 
                    'incident' => $incidentType->id,
                    default => $positifType->id
                };
                
                // Choisir un statut au hasard
                $selectedStatus = fake()->randomElement($statuses);

                Feedback::create([
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                    'employee_id' => $employee->id,
                    'service_id' => $service->id,
                    'feedback_type_id' => $feedbackTypeId,
                    'feedback_status_id' => $selectedStatus->id,
                    'type' => $type,
                    'title' => $feedbackData['title'],
                    'description' => $feedbackData['description'],
                    'status' => $selectedStatus->name,
                    'kalipoints' => $type === 'appreciation' ? fake()->numberBetween(1, 5) : fake()->numberBetween(1, 3),
                    'rating' => $type === 'appreciation' ? fake()->numberBetween(3, 5) : fake()->numberBetween(1, 3),
                    'positive_kalipoints' => $type === 'appreciation' ? fake()->numberBetween(10, 25) : 0,
                    'negative_kalipoints' => $type === 'incident' ? fake()->numberBetween(5, 15) : 0,
                    'suggestion_kalipoints' => $type === 'suggestion' ? fake()->numberBetween(8, 20) : 0,
                    'sentiment' => $this->getSentimentForType($type),
                    'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
                    'updated_at' => fake()->dateTimeBetween('-3 months', 'now'),
                ]);
            }
        }
    }

    private function getFeedbackData(string $type, string $sector): array
    {
        $feedbacks = match([$type, $sector]) {
            ['appreciation', 'restauration'] => [
                ['title' => 'Excellent service', 'description' => 'Le serveur était très attentionné et les plats délicieux. Une soirée parfaite !'],
                ['title' => 'Cuisine remarquable', 'description' => 'Les saveurs étaient exceptionnelles, présentation soignée. Bravo au chef !'],
                ['title' => 'Ambiance chaleureuse', 'description' => 'Cadre magnifique, personnel souriant. Nous reviendrons avec plaisir !'],
                ['title' => 'Service impeccable', 'description' => 'Rapidité, qualité, gentillesse. Tout était parfait du début à la fin.'],
            ],
            ['incident', 'restauration'] => [
                ['title' => 'Attente trop longue', 'description' => 'Plus d\'une heure d\'attente pour recevoir nos plats. Service très lent.'],
                ['title' => 'Plat froid', 'description' => 'Mon plat principal était tiède à l\'arrivée. Décevant pour le prix.'],
                ['title' => 'Erreur de commande', 'description' => 'Nous avons reçu des plats que nous n\'avions pas commandés.'],
                ['title' => 'Personnel désagréable', 'description' => 'Le serveur était impoli et peu professionnel.'],
            ],
            ['suggestion', 'restauration'] => [
                ['title' => 'Menu végétarien', 'description' => 'Pourriez-vous ajouter plus d\'options végétariennes à votre carte ?'],
                ['title' => 'Terrasse chauffée', 'description' => 'Des chauffages sur la terrasse seraient appréciés en hiver.'],
                ['title' => 'Réservation en ligne', 'description' => 'Un système de réservation sur votre site web serait pratique.'],
                ['title' => 'Menu enfant', 'description' => 'Un menu spécial pour les enfants serait une excellente idée.'],
            ],
            ['appreciation', 'hotellerie'] => [
                ['title' => 'Chambre parfaite', 'description' => 'Chambre spacieuse, propre et bien équipée. Vue magnifique !'],
                ['title' => 'Personnel exceptionnel', 'description' => 'L\'équipe de la réception était très professionnelle et accueillante.'],
                ['title' => 'Petit-déjeuner délicieux', 'description' => 'Large choix, produits frais, service attentionné au restaurant.'],
                ['title' => 'Spa relaxant', 'description' => 'Moment de détente parfait, installations modernes et personnel qualifié.'],
            ],
            ['incident', 'hotellerie'] => [
                ['title' => 'Problème de climatisation', 'description' => 'La climatisation ne fonctionnait pas dans notre chambre.'],
                ['title' => 'Bruit nocturne', 'description' => 'Beaucoup de bruit dans les couloirs la nuit, sommeil perturbé.'],
                ['title' => 'Check-in lent', 'description' => 'Attente de 30 minutes à la réception pour l\'enregistrement.'],
                ['title' => 'Ménage insuffisant', 'description' => 'La chambre n\'était pas correctement nettoyée à notre arrivée.'],
            ],
            ['suggestion', 'hotellerie'] => [
                ['title' => 'Wi-Fi plus rapide', 'description' => 'La connexion internet pourrait être améliorée dans les chambres.'],
                ['title' => 'Service 24h/24', 'description' => 'Un service de room service 24h/24 serait appréciable.'],
                ['title' => 'Parking gratuit', 'description' => 'Le parking gratuit pour les clients serait un plus.'],
                ['title' => 'Navette aéroport', 'description' => 'Une navette depuis l\'aéroport faciliterait l\'accès.'],
            ],
            ['appreciation', 'commerce_retail'] => [
                ['title' => 'Conseils excellents', 'description' => 'Le vendeur m\'a parfaitement orienté dans mon choix. Très professionnel !'],
                ['title' => 'Produits de qualité', 'description' => 'Large gamme de produits, excellente qualité, bon rapport qualité-prix.'],
                ['title' => 'Service après-vente', 'description' => 'Problème résolu rapidement, équipe technique très compétente.'],
                ['title' => 'Magasin bien organisé', 'description' => 'Facile de s\'y retrouver, tout est bien rangé et étiqueté.'],
            ],
            ['incident', 'commerce_retail'] => [
                ['title' => 'Produit défectueux', 'description' => 'L\'article acheté est tombé en panne après 2 jours d\'utilisation.'],
                ['title' => 'Prix incorrects', 'description' => 'Les prix affichés ne correspondent pas à ceux en caisse.'],
                ['title' => 'Stock indisponible', 'description' => 'Produit affiché disponible mais absent des rayons.'],
                ['title' => 'Attente en caisse', 'description' => 'File d\'attente très longue avec une seule caisse ouverte.'],
            ],
            ['suggestion', 'commerce_retail'] => [
                ['title' => 'Application mobile', 'description' => 'Une app pour vérifier les stocks en temps réel serait pratique.'],
                ['title' => 'Programme fidélité', 'description' => 'Un système de points fidélité récompenserait les clients réguliers.'],
                ['title' => 'Click & Collect', 'description' => 'Pouvoir commander en ligne et récupérer en magasin.'],
                ['title' => 'Formations produits', 'description' => 'Des ateliers pour apprendre à utiliser les produits complexes.'],
            ],
            default => [
                ['title' => 'Feedback général', 'description' => 'Commentaire général sur le service ou produit.'],
            ]
        };

        return fake()->randomElement($feedbacks);
    }

    private function getSentimentForType(string $type): ?string
    {
        return match($type) {
            'appreciation' => fake()->randomElement(['content', 'heureux', 'extremement_satisfait']),
            'incident' => fake()->randomElement(['mecontent', 'en_colere', 'laisse_a_desirer']),
            'suggestion' => fake()->randomElement(['constructif', 'amelioration', 'proposition']),
            default => null
        };
    }
}