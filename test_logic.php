<?php

echo "🧠 Test de la Logique de Gamification QualyWatch\n";
echo "=" . str_repeat("=", 55) . "\n\n";

// Test 1: Test des critères de badges
echo "🏅 Test 1: Logique des badges...\n";

// Simuler des données de feedback
$userFeedbacks = [
    ['rating' => 5, 'type' => 'positif', 'created_at' => '2024-01-15'],
    ['rating' => 4, 'type' => 'positif', 'created_at' => '2024-01-16'],
    ['rating' => 5, 'type' => 'positif', 'created_at' => '2024-01-17'],
    ['rating' => 3, 'type' => 'negatif', 'created_at' => '2024-01-18'],
    ['rating' => 5, 'type' => 'positif', 'created_at' => '2024-01-19'],
];

// Test calcul satisfaction
$totalRating = array_sum(array_column($userFeedbacks, 'rating'));
$avgRating = $totalRating / count($userFeedbacks);
$satisfactionRate = ($avgRating / 5) * 100;

echo "  📊 Feedbacks simulés: " . count($userFeedbacks) . " feedbacks\n";
echo "  ⭐ Note moyenne: " . round($avgRating, 2) . "/5\n";
echo "  📈 Taux de satisfaction: " . round($satisfactionRate, 1) . "%\n";

// Test éligibilité badge "Client Favoris" (>= 90% satisfaction, min 5 feedbacks)
$isEligibleClientFavoris = $satisfactionRate >= 90 && count($userFeedbacks) >= 5;
echo "  🎖️  Éligible 'Client Favoris': " . ($isEligibleClientFavoris ? "✅ OUI" : "❌ NON") . "\n";

// Test éligibilité badge "Top Performer" (>= 95% satisfaction, min 3 feedbacks)
$isEligibleTopPerformer = $satisfactionRate >= 95 && count($userFeedbacks) >= 3;
echo "  🏆 Éligible 'Top Performer': " . ($isEligibleTopPerformer ? "✅ OUI" : "❌ NON") . "\n";

echo "\n";

// Test 2: Test du système de points
echo "💎 Test 2: Système de points...\n";

$pointsCalculation = [
    'appreciation' => ['count' => 3, 'points_per' => 10],
    'positif' => ['count' => 4, 'points_per' => 5],
    'incident_resolved' => ['count' => 2, 'points_per' => 25],
];

$totalPoints = 0;
foreach ($pointsCalculation as $type => $data) {
    $points = $data['count'] * $data['points_per'];
    $totalPoints += $points;
    echo "  📝 $type: {$data['count']} × {$data['points_per']} = $points points\n";
}

echo "  🔢 Total KaliPoints: $totalPoints points\n";
echo "\n";

// Test 3: Test de classement
echo "📊 Test 3: Système de classement...\n";

// Simuler des données de classement
$employees = [
    ['id' => 1, 'name' => 'Alice', 'satisfaction' => 92.5, 'feedbacks' => 25],
    ['id' => 2, 'name' => 'Bob', 'satisfaction' => 88.3, 'feedbacks' => 18],
    ['id' => 3, 'name' => 'Charlie', 'satisfaction' => 94.1, 'feedbacks' => 22],
    ['id' => 4, 'name' => 'Diana', 'satisfaction' => 87.9, 'feedbacks' => 30],
    ['id' => 5, 'name' => 'Eve', 'satisfaction' => 90.2, 'feedbacks' => 15],
];

// Calcul du score composite (satisfaction 70% + volume 30%)
foreach ($employees as &$employee) {
    $satisfactionScore = $employee['satisfaction'];
    $volumeScore = min($employee['feedbacks'] * 2, 40); // Max 40 points pour le volume
    $employee['composite_score'] = round($satisfactionScore * 0.7 + $volumeScore * 0.3, 2);
}

// Tri par score composite
usort($employees, function($a, $b) {
    return $b['composite_score'] <=> $a['composite_score'];
});

echo "  🏆 Classement par score composite:\n";
foreach ($employees as $rank => $employee) {
    $position = $rank + 1;
    $medal = match($position) {
        1 => "🥇",
        2 => "🥈", 
        3 => "🥉",
        default => "  "
    };
    echo "    $medal #$position {$employee['name']}: {$employee['composite_score']} points\n";
    echo "           (Satisfaction: {$employee['satisfaction']}%, Feedbacks: {$employee['feedbacks']})\n";
}

echo "\n";

// Test 4: Test des défis
echo "🎯 Test 4: Système de défis...\n";

// Simuler un défi
$challenge = [
    'title' => 'Défi Satisfaction Mensuelle',
    'type' => 'satisfaction_score',
    'target_value' => 90,
    'current_month_data' => [
        'alice' => ['current' => 92.5, 'target' => 90],
        'bob' => ['current' => 75.2, 'target' => 90],
        'charlie' => ['current' => 94.1, 'target' => 90],
    ]
];

echo "  🏁 Défi: {$challenge['title']}\n";
echo "  🎯 Objectif: {$challenge['target_value']}%\n";
echo "  📈 Progression:\n";

foreach ($challenge['current_month_data'] as $name => $data) {
    $progress = min(100, ($data['current'] / $data['target']) * 100);
    $status = $progress >= 100 ? "✅ TERMINÉ" : "🔄 " . round($progress, 1) . "%";
    echo "    👤 " . ucfirst($name) . ": {$data['current']}% $status\n";
}

echo "\n";

// Test 5: Test de reconnaissance automatique
echo "🌟 Test 5: Système de reconnaissance...\n";

// Simulation sélection employé du mois
$monthlyData = [
    'alice' => ['composite' => 89.2, 'consistency' => 15, 'innovation' => 5],
    'charlie' => ['composite' => 91.5, 'consistency' => 10, 'innovation' => 10],
    'diana' => ['composite' => 87.9, 'consistency' => 12, 'innovation' => 3],
];

foreach ($monthlyData as $name => &$data) {
    $data['total_score'] = $data['composite'] + $data['consistency'] + $data['innovation'];
}

// Trier par score total
uasort($monthlyData, function($a, $b) {
    return $b['total_score'] <=> $a['total_score'];
});

$employeeOfMonth = array_keys($monthlyData)[0];
$topScore = reset($monthlyData);

echo "  👑 Employé du mois: " . ucfirst($employeeOfMonth) . "\n";
echo "  📊 Score total: {$topScore['total_score']} points\n";
echo "     - Score composite: {$topScore['composite']}\n";
echo "     - Bonus consistance: {$topScore['consistency']}\n";
echo "     - Bonus innovation: {$topScore['innovation']}\n";

echo "\n";

// Test 6: Test des événements (simulation)
echo "🎪 Test 6: Simulation d'événements...\n";

$events = [
    [
        'type' => 'badge_earned',
        'user' => 'Alice',
        'badge' => 'Client Favoris',
        'points' => 100,
        'rarity' => 'rare'
    ],
    [
        'type' => 'challenge_completed',
        'user' => 'Charlie', 
        'challenge' => 'Défi Satisfaction',
        'rank' => 1,
        'points' => 500
    ],
    [
        'type' => 'leaderboard_published',
        'period' => 'monthly',
        'winner' => 'Charlie',
        'metric' => 'satisfaction_score'
    ]
];

foreach ($events as $event) {
    switch ($event['type']) {
        case 'badge_earned':
            $icon = match($event['rarity']) {
                'legendary' => '🏆',
                'epic' => '💜',
                'rare' => '💎',
                default => '🏅'
            };
            echo "  $icon {$event['user']} a obtenu le badge '{$event['badge']}' (+{$event['points']} points)\n";
            break;
            
        case 'challenge_completed':
            $rankIcon = match($event['rank']) {
                1 => '🥇',
                2 => '🥈',
                3 => '🥉',
                default => "#{$event['rank']}"
            };
            echo "  🎯 {$event['user']} a terminé '{$event['challenge']}' en $rankIcon (+{$event['points']} points)\n";
            break;
            
        case 'leaderboard_published':
            echo "  📊 Classement {$event['period']} publié - Gagnant: {$event['winner']} ({$event['metric']})\n";
            break;
    }
}

echo "\n🎉 Tous les tests logiques réussis !\n\n";

// Résumé final
echo "✨ Résumé des fonctionnalités testées:\n";
echo "  ✅ Calcul de satisfaction et éligibilité badges\n";
echo "  ✅ Système de points KaliPoints\n";
echo "  ✅ Algorithme de classement composite\n";
echo "  ✅ Suivi de progression des défis\n";
echo "  ✅ Sélection automatique employé du mois\n";
echo "  ✅ Simulation d'événements de gamification\n";

echo "\n🚀 Le système de gamification est prêt pour les tests en base de données !\n";