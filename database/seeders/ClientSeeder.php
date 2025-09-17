<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'first_name' => 'Marie',
                'last_name' => 'Dubois',
                'email' => 'marie.dubois@gmail.com',
                'phone' => '+33987654321',
            ],
            [
                'first_name' => 'Laurent',
                'last_name' => 'Martin',
                'email' => 'laurent.martin@yahoo.fr',
                'phone' => '+33876543210',
            ],
            [
                'first_name' => 'Isabelle',
                'last_name' => 'Bernard',
                'email' => 'isabelle.bernard@outlook.com',
                'phone' => '+33765432109',
            ],
            [
                'first_name' => 'François',
                'last_name' => 'Petit',
                'email' => 'francois.petit@hotmail.com',
                'phone' => '+33654321098',
            ],
            [
                'first_name' => 'Sophie',
                'last_name' => 'Moreau',
                'email' => 'sophie.moreau@gmail.com',
                'phone' => '+33543210987',
            ],
            [
                'first_name' => 'Nicolas',
                'last_name' => 'Leroy',
                'email' => 'nicolas.leroy@free.fr',
                'phone' => '+33432109876',
            ],
            [
                'first_name' => 'Catherine',
                'last_name' => 'Simon',
                'email' => 'catherine.simon@orange.fr',
                'phone' => '+33321098765',
            ],
            [
                'first_name' => 'Pierre',
                'last_name' => 'Michel',
                'email' => 'pierre.michel@laposte.net',
                'phone' => '+33210987654',
            ],
            [
                'first_name' => 'Valérie',
                'last_name' => 'Garnier',
                'email' => 'valerie.garnier@sfr.fr',
                'phone' => '+33109876543',
            ],
            [
                'first_name' => 'Christophe',
                'last_name' => 'Blanc',
                'email' => 'christophe.blanc@gmail.com',
                'phone' => '+33098765432',
            ],
            [
                'first_name' => 'Nathalie',
                'last_name' => 'Roux',
                'email' => 'nathalie.roux@yahoo.fr',
                'phone' => '+33987654320',
            ],
            [
                'first_name' => 'Stéphane',
                'last_name' => 'Laurent',
                'email' => 'stephane.laurent@hotmail.com',
                'phone' => '+33876543219',
            ],
            [
                'first_name' => 'Sylvie',
                'last_name' => 'Lefebvre',
                'email' => 'sylvie.lefebvre@outlook.com',
                'phone' => '+33765432108',
            ],
            [
                'first_name' => 'Olivier',
                'last_name' => 'Martinez',
                'email' => 'olivier.martinez@gmail.com',
                'phone' => '+33654321097',
            ],
            [
                'first_name' => 'Cécile',
                'last_name' => 'Garcia',
                'email' => 'cecile.garcia@free.fr',
                'phone' => '+33543210986',
            ],
        ];

        foreach ($clients as $clientData) {
            Client::create([
                'first_name' => $clientData['first_name'],
                'last_name' => $clientData['last_name'],
                'email' => $clientData['email'],
                'phone' => $clientData['phone'],
                'total_kalipoints' => fake()->numberBetween(0, 500),
                'bonus_kalipoints' => fake()->numberBetween(0, 100),
                'status' => fake()->randomElement(['normal', 'normal', 'normal', 'vip']), // Majorité normal
            ]);
        }
    }
}