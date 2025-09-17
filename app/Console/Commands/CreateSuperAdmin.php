<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:super-admin 
                            {--email= : Email du super admin}
                            {--password= : Mot de passe du super admin}
                            {--first-name= : Prénom du super admin}
                            {--last-name= : Nom du super admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un compte Super Admin pour accéder aux fonctionnalités globales';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Création d\'un compte Super Admin pour QualyWatch');
        
        // Récupérer les données
        $email = $this->option('email') ?: $this->ask('Email du Super Admin');
        $password = $this->option('password') ?: $this->secret('Mot de passe (min 8 caractères)');
        $firstName = $this->option('first-name') ?: $this->ask('Prénom');
        $lastName = $this->option('last-name') ?: $this->ask('Nom');

        // Validation
        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ], [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            $this->error('❌ Erreurs de validation :');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  • ' . $error);
            }
            return Command::FAILURE;
        }

        // Créer le super admin
        try {
            $superAdmin = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => Hash::make($password),
                'phone' => '+33000000000', // Phone par défaut
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]);

            $this->info('');
            $this->info('✅ Super Admin créé avec succès !');
            $this->info('');
            $this->table(['Champ', 'Valeur'], [
                ['ID', $superAdmin->id],
                ['Nom complet', $firstName . ' ' . $lastName],
                ['Email', $email],
                ['Rôle', 'super_admin'],
                ['Créé le', $superAdmin->created_at->format('d/m/Y H:i:s')],
            ]);
            $this->info('');
            $this->warn('🔐 Gardez ces identifiants en sécurité !');
            $this->info('');
            $this->info('Pour vous connecter :');
            $this->info('POST /api/auth/login');
            $this->info('Body: {"identifier": "' . $email . '", "password": "***"}');
            $this->info('');
            $this->info('Endpoints Super Admin disponibles :');
            $this->info('• GET /api/super-admin/dashboard');
            $this->info('• GET /api/super-admin/companies');
            $this->info('• GET /api/super-admin/statistics');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la création : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
