<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Configuration de la base de données (à adapter selon votre config)
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'qualywatch',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "🎮 Test du système de gamification QualyWatch\n";
echo "=" . str_repeat("=", 50) . "\n\n";

try {
    // Test 1: Vérifier les tables
    echo "📋 Test 1: Vérification des tables...\n";
    $tables = ['badges', 'user_badges', 'leaderboards', 'challenges', 'user_challenges'];
    
    foreach ($tables as $table) {
        try {
            $count = Capsule::table($table)->count();
            echo "  ✅ Table '$table': $count enregistrements\n";
        } catch (Exception $e) {
            echo "  ❌ Erreur table '$table': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Test 2: Créer des données de test
    echo "🏗️  Test 2: Création de données de test...\n";
    
    // Créer une entreprise de test
    $companyId = Capsule::table('companies')->insertGetId([
        'id' => \Illuminate\Support\Str::uuid(),
        'name' => 'Test Company',
        'email' => 'test@company.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "  ✅ Entreprise créée: $companyId\n";
    
    // Créer un utilisateur de test
    $userId = Capsule::table('users')->insertGetId([
        'id' => \Illuminate\Support\Str::uuid(),
        'name' => 'John Doe',
        'email' => 'john@test.com',
        'company_id' => $companyId,
        'password' => password_hash('password', PASSWORD_DEFAULT),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "  ✅ Utilisateur créé: $userId\n";
    
    // Test 3: Vérifier les badges par défaut
    echo "\n🏅 Test 3: Badges disponibles...\n";
    $badges = Capsule::table('badges')->where('is_active', true)->get();
    
    foreach ($badges as $badge) {
        echo "  🎖️  {$badge->title} ({$badge->rarity}) - {$badge->points_reward} points\n";
    }
    
    // Test 4: Simulation d'attribution de badge
    echo "\n🎯 Test 4: Attribution de badge...\n";
    
    if (!empty($badges)) {
        $firstBadge = $badges->first();
        
        $userBadgeId = Capsule::table('user_badges')->insertGetId([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $userId,
            'badge_id' => $firstBadge->id,
            'company_id' => $companyId,
            'earned_date' => now()->format('Y-m-d'),
            'points_earned' => $firstBadge->points_reward,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "  ✅ Badge '{$firstBadge->title}' attribué à l'utilisateur\n";
        echo "  📊 Points gagnés: {$firstBadge->points_reward}\n";
    }
    
    // Test 5: Créer un classement de test
    echo "\n📊 Test 5: Création de classement...\n";
    
    $leaderboardId = Capsule::table('leaderboards')->insertGetId([
        'id' => \Illuminate\Support\Str::uuid(),
        'user_id' => $userId,
        'company_id' => $companyId,
        'period_type' => 'monthly',
        'period_date' => now()->startOfMonth(),
        'metric_type' => 'satisfaction_score',
        'score' => 85.5,
        'rank_overall' => 1,
        'total_participants' => 10,
        'points_earned' => 300,
        'is_winner' => true,
        'podium_position' => 1,
        'is_published' => true,
        'published_at' => now(),
        'calculated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "  ✅ Classement créé: Position #1 avec 85.5% de satisfaction\n";
    echo "  🏆 Statut: Gagnant avec 300 points\n";
    
    // Test 6: Créer un défi
    echo "\n🎯 Test 6: Création de défi...\n";
    
    $challengeId = Capsule::table('challenges')->insertGetId([
        'id' => \Illuminate\Support\Str::uuid(),
        'company_id' => $companyId,
        'name' => 'test_challenge',
        'title' => 'Défi Satisfaction',
        'description' => 'Atteindre 90% de satisfaction client',
        'type' => 'individual',
        'category' => 'satisfaction',
        'objectives' => json_encode(['type' => 'satisfaction_score', 'target' => 90]),
        'target_value' => 90,
        'target_unit' => '%',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(20),
        'duration_type' => 'monthly',
        'reward_points' => 500,
        'status' => 'active',
        'created_by' => $userId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "  ✅ Défi créé: 'Défi Satisfaction' (500 points)\n";
    
    // Participation au défi
    $userChallengeId = Capsule::table('user_challenges')->insertGetId([
        'id' => \Illuminate\Support\Str::uuid(),
        'user_id' => $userId,
        'challenge_id' => $challengeId,
        'joined_at' => now(),
        'is_active' => true,
        'current_value' => 75,
        'progress_percentage' => 83.33, // 75/90 * 100
        'is_completed' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "  📈 Participation: 75/90 (83.33% de progression)\n";
    
    echo "\n🎉 Tests terminés avec succès !\n\n";
    
    // Résumé des statistiques
    echo "📈 Résumé des statistiques:\n";
    echo "  👥 Utilisateurs: " . Capsule::table('users')->count() . "\n";
    echo "  🏅 Badges disponibles: " . Capsule::table('badges')->where('is_active', true)->count() . "\n";
    echo "  🎖️  Badges attribués: " . Capsule::table('user_badges')->count() . "\n";
    echo "  📊 Classements: " . Capsule::table('leaderboards')->count() . "\n";
    echo "  🎯 Défis actifs: " . Capsule::table('challenges')->where('status', 'active')->count() . "\n";
    echo "  👤 Participations aux défis: " . Capsule::table('user_challenges')->count() . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors du test: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

function now() {
    return new DateTime();
}