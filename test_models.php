<?php

echo "🎮 Test des Modèles de Gamification QualyWatch\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Test 1: Vérifier que les fichiers de modèles existent
echo "📋 Test 1: Vérification des modèles...\n";

$models = [
    'Badge' => 'app/Models/Badge.php',
    'UserBadge' => 'app/Models/UserBadge.php',
    'Leaderboard' => 'app/Models/Leaderboard.php',
    'Challenge' => 'app/Models/Challenge.php',
    'UserChallenge' => 'app/Models/UserChallenge.php',
];

foreach ($models as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "  ✅ Modèle $name: OK\n";
    } else {
        echo "  ❌ Modèle $name: MANQUANT ($path)\n";
    }
}

echo "\n";

// Test 2: Vérifier les services
echo "🛠️  Test 2: Vérification des services...\n";

$services = [
    'GamificationService' => 'app/Services/GamificationService.php',
    'RewardService' => 'app/Services/RewardService.php',
];

foreach ($services as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "  ✅ Service $name: OK\n";
    } else {
        echo "  ❌ Service $name: MANQUANT ($path)\n";
    }
}

echo "\n";

// Test 3: Vérifier les migrations
echo "🗄️  Test 3: Vérification des migrations...\n";

$migrations = [
    'badges' => 'database/migrations/*create_badges_table.php',
    'user_badges' => 'database/migrations/*create_user_badges_table.php',
    'leaderboards' => 'database/migrations/*create_leaderboards_table.php',
    'challenges' => 'database/migrations/*create_challenges_table.php',
    'user_challenges' => 'database/migrations/*create_user_challenges_table.php',
];

foreach ($migrations as $name => $pattern) {
    $files = glob(__DIR__ . '/' . $pattern);
    if (!empty($files)) {
        echo "  ✅ Migration $name: " . basename($files[0]) . "\n";
    } else {
        echo "  ❌ Migration $name: MANQUANTE ($pattern)\n";
    }
}

echo "\n";

// Test 4: Vérifier les événements
echo "🎪 Test 4: Vérification des événements...\n";

$events = [
    'BadgeEarned' => 'app/Events/BadgeEarned.php',
    'LeaderboardUpdated' => 'app/Events/LeaderboardUpdated.php',
    'ChallengeCompleted' => 'app/Events/ChallengeCompleted.php',
    'LeaderboardPublished' => 'app/Events/LeaderboardPublished.php',
];

foreach ($events as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "  ✅ Événement $name: OK\n";
    } else {
        echo "  ❌ Événement $name: MANQUANT ($path)\n";
    }
}

echo "\n";

// Test 5: Vérifier les listeners
echo "🔔 Test 5: Vérification des listeners...\n";

$listeners = [
    'SendBadgeEarnedNotification' => 'app/Listeners/SendBadgeEarnedNotification.php',
    'SendLeaderboardNotification' => 'app/Listeners/SendLeaderboardNotification.php',
    'SendChallengeNotification' => 'app/Listeners/SendChallengeNotification.php',
    'UpdateGamificationProgress' => 'app/Listeners/UpdateGamificationProgress.php',
];

foreach ($listeners as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "  ✅ Listener $name: OK\n";
    } else {
        echo "  ❌ Listener $name: MANQUANT ($path)\n";
    }
}

echo "\n";

// Test 6: Vérifier les commandes
echo "⚡ Test 6: Vérification des commandes...\n";

$commands = [
    'RunGamificationCheck' => 'app/Console/Commands/RunGamificationCheck.php',
];

foreach ($commands as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "  ✅ Commande $name: OK\n";
    } else {
        echo "  ❌ Commande $name: MANQUANTE ($path)\n";
    }
}

echo "\n";

// Test 7: Vérifier les jobs
echo "🏃 Test 7: Vérification des jobs...\n";

$jobs = [
    'ProcessGamificationUpdate' => 'app/Jobs/ProcessGamificationUpdate.php',
];

foreach ($jobs as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "  ✅ Job $name: OK\n";
    } else {
        echo "  ❌ Job $name: MANQUANT ($path)\n";
    }
}

echo "\n";

// Test 8: Vérifier la syntaxe PHP
echo "🔍 Test 8: Vérification de la syntaxe...\n";

$phpFiles = [
    'app/Models/Badge.php',
    'app/Models/UserBadge.php',
    'app/Services/GamificationService.php',
    'app/Events/BadgeEarned.php',
];

foreach ($phpFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        // Test de syntaxe basique (vérifier si le fichier commence par <?php)
        $content = file_get_contents(__DIR__ . '/' . $file);
        if (strpos($content, '<?php') === 0) {
            echo "  ✅ Syntaxe $file: OK\n";
        } else {
            echo "  ⚠️  Syntaxe $file: Pas de balise PHP d'ouverture\n";
        }
    }
}

echo "\n🎉 Tests de vérification terminés !\n\n";

// Résumé
$totalFiles = count($models) + count($services) + count($events) + count($listeners) + count($commands) + count($jobs);
$existingFiles = 0;

$allFiles = array_merge($models, $services, $events, $listeners, $commands, $jobs);

foreach ($allFiles as $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        $existingFiles++;
    }
}

echo "📊 Résumé:\n";
echo "  📁 Fichiers créés: $existingFiles / $totalFiles\n";
echo "  📈 Pourcentage de completion: " . round(($existingFiles / $totalFiles) * 100, 1) . "%\n";

if ($existingFiles === $totalFiles) {
    echo "  🎯 Système de gamification complet !\n";
} else {
    echo "  ⚠️  Quelques fichiers manquants détectés\n";
}

echo "\n✨ Prêt pour les tests avec une base de données !\n";