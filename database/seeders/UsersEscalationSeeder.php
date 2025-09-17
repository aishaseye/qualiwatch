<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class UsersEscalationSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer l'entreprise Restaurant Le Gourmet
        $company = Company::where('name', 'like', '%Restaurant Le Gourmet%')->first();
        
        if (!$company) {
            $this->command->error('Entreprise Restaurant Le Gourmet non trouvée.');
            return;
        }

        $this->command->info("Création des utilisateurs d'escalation pour: {$company->name}");

        // Vérifier si les utilisateurs existent déjà
        $director = User::where('email', 'directeur@restaurant-legourmet.com')->first();
        $ceo = User::where('email', 'pdg@restaurant-legourmet.com')->first();

        // Créer le Directeur
        if (!$director) {
            $director = User::create([
                'first_name' => 'Marie',
                'last_name' => 'Martin',
                'email' => 'directeur@restaurant-legourmet.com',
                'phone' => '+33123456790',
                'password' => Hash::make('password123'),
                'role' => 'director',
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ]);

            $this->command->info("✅ Directeur créé: {$director->full_name} ({$director->email})");
        } else {
            // Mettre à jour le rôle si l'utilisateur existe
            $director->update([
                'role' => 'director',
                'company_id' => $company->id
            ]);
            $this->command->info("✅ Directeur mis à jour: {$director->full_name} ({$director->email})");
        }

        // Créer le PDG/CEO
        if (!$ceo) {
            $ceo = User::create([
                'first_name' => 'Jean-Pierre',
                'last_name' => 'Durand',
                'email' => 'pdg@restaurant-legourmet.com',
                'phone' => '+33123456791',
                'password' => Hash::make('password123'),
                'role' => 'ceo',
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ]);

            $this->command->info("✅ PDG créé: {$ceo->full_name} ({$ceo->email})");
        } else {
            // Mettre à jour le rôle si l'utilisateur existe
            $ceo->update([
                'role' => 'ceo',
                'company_id' => $company->id
            ]);
            $this->command->info("✅ PDG mis à jour: {$ceo->full_name} ({$ceo->email})");
        }

        // Afficher résumé
        $this->command->info('');
        $this->command->info('🎯 HIÉRARCHIE D\'ESCALATION CRÉÉE :');
        $this->command->info('   📋 Manager: Jean Dupont (manager@qualywatch-demo.com)');
        $this->command->info('   👔 Directeur: Marie Martin (directeur@restaurant-legourmet.com)');
        $this->command->info('   🏛️ PDG: Jean-Pierre Durand (pdg@restaurant-legourmet.com)');
        $this->command->info('');
        $this->command->info('✅ Maintenant les escalations SLA pourront notifier tous les niveaux !');
    }
}