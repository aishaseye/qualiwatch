<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\UserBadge;
use App\Models\Leaderboard;
use App\Models\Challenge;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RewardService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Système de reconnaissance automatique mensuelle
     */
    public function runMonthlyRecognition($companyId)
    {
        Log::info("Starting monthly recognition for company {$companyId}");

        $results = [
            'employee_of_month' => null,
            'team_of_month' => null,
            'recognition_awards' => 0,
            'bonus_points_distributed' => 0,
        ];

        try {
            DB::beginTransaction();

            // 1. Employé du mois
            $employeeOfMonth = $this->selectEmployeeOfMonth($companyId);
            if ($employeeOfMonth) {
                $this->awardEmployeeOfMonth($employeeOfMonth);
                $results['employee_of_month'] = $employeeOfMonth;
            }

            // 2. Équipe du mois
            $teamOfMonth = $this->selectTeamOfMonth($companyId);
            if ($teamOfMonth) {
                $this->awardTeamOfMonth($teamOfMonth);
                $results['team_of_month'] = $teamOfMonth;
            }

            // 3. Reconnaissances spéciales
            $specialRecognitions = $this->awardSpecialRecognitions($companyId);
            $results['recognition_awards'] = count($specialRecognitions);

            // 4. Bonus points pour amélioration
            $bonusPoints = $this->distributeImprovementBonuses($companyId);
            $results['bonus_points_distributed'] = $bonusPoints;

            DB::commit();

            Log::info("Monthly recognition completed successfully", $results);
            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Monthly recognition failed for company {$companyId}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sélectionner l'employé du mois
     */
    private function selectEmployeeOfMonth($companyId)
    {
        $currentMonth = now()->startOfMonth();
        
        // Calculer un score composite basé sur plusieurs métriques
        $candidates = Leaderboard::byCompany($companyId)
                                ->where('period_type', 'monthly')
                                ->where('period_date', $currentMonth)
                                ->with(['user', 'user.employee.service'])
                                ->get()
                                ->groupBy('user_id');

        $employeeScores = [];

        foreach ($candidates as $userId => $leaderboards) {
            $user = $leaderboards->first()->user;
            
            $compositeScore = $this->calculateCompositeScore($leaderboards);
            $consistencyBonus = $this->calculateConsistencyBonus($user, $companyId);
            $innovationBonus = $this->calculateInnovationBonus($user, $companyId);

            $employeeScores[$userId] = [
                'user' => $user,
                'composite_score' => $compositeScore,
                'consistency_bonus' => $consistencyBonus,
                'innovation_bonus' => $innovationBonus,
                'total_score' => $compositeScore + $consistencyBonus + $innovationBonus,
                'leaderboards' => $leaderboards,
            ];
        }

        if (empty($employeeScores)) return null;

        // Trier par score total
        uasort($employeeScores, function ($a, $b) {
            return $b['total_score'] <=> $a['total_score'];
        });

        return reset($employeeScores);
    }

    /**
     * Calculer un score composite
     */
    private function calculateCompositeScore($leaderboards)
    {
        $weights = [
            'satisfaction_score' => 0.3,
            'overall_performance' => 0.25,
            'positive_feedbacks' => 0.2,
            'resolution_time' => 0.15,
            'kalipoints_earned' => 0.1,
        ];

        $score = 0;
        $totalWeight = 0;

        foreach ($leaderboards as $leaderboard) {
            $metricType = $leaderboard->metric_type;
            $weight = $weights[$metricType] ?? 0;
            
            if ($weight > 0) {
                // Normaliser le score selon le rang (meilleur rang = plus de points)
                $normalizedScore = max(0, 100 - ($leaderboard->rank_overall * 10));
                $score += $normalizedScore * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? round($score / $totalWeight, 2) : 0;
    }

    /**
     * Calculer le bonus de consistance
     */
    private function calculateConsistencyBonus($user, $companyId)
    {
        // Vérifier la performance sur les 3 derniers mois
        $months = [];
        for ($i = 0; $i < 3; $i++) {
            $months[] = now()->subMonths($i)->startOfMonth();
        }

        $consistentRanks = [];
        foreach ($months as $month) {
            $avgRank = Leaderboard::where('user_id', $user->id)
                                 ->where('company_id', $companyId)
                                 ->where('period_type', 'monthly')
                                 ->where('period_date', $month)
                                 ->avg('rank_overall');
            
            if ($avgRank) {
                $consistentRanks[] = $avgRank;
            }
        }

        if (count($consistentRanks) < 2) return 0;

        // Bonus si l'employé est resté dans le top 20% de façon constante
        $avgRank = collect($consistentRanks)->avg();
        $variance = collect($consistentRanks)->map(function ($rank) use ($avgRank) {
            return pow($rank - $avgRank, 2);
        })->avg();

        // Plus la variance est faible et le rang moyen bon, plus le bonus est élevé
        return $avgRank <= 5 && $variance <= 4 ? 15 : ($avgRank <= 10 && $variance <= 9 ? 10 : 0);
    }

    /**
     * Calculer le bonus d'innovation
     */
    private function calculateInnovationBonus($user, $companyId)
    {
        $currentMonth = now()->startOfMonth();
        
        // Badges rares obtenus ce mois
        $rareBadges = UserBadge::where('user_id', $user->id)
                              ->where('company_id', $companyId)
                              ->whereMonth('earned_date', $currentMonth->month)
                              ->whereYear('earned_date', $currentMonth->year)
                              ->whereHas('badge', function ($query) {
                                  $query->whereIn('rarity', ['rare', 'epic', 'legendary']);
                              })
                              ->count();

        // Défis complétés en premier
        $challengeWins = Challenge::where('company_id', $companyId)
                               ->whereHas('userChallenges', function ($query) use ($user) {
                                   $query->where('user_id', $user->id)
                                         ->where('is_winner', true);
                               })
                               ->whereMonth('end_date', $currentMonth->month)
                               ->whereYear('end_date', $currentMonth->year)
                               ->count();

        return ($rareBadges * 5) + ($challengeWins * 10);
    }

    /**
     * Attribuer le titre d'employé du mois
     */
    private function awardEmployeeOfMonth($employeeData)
    {
        $user = $employeeData['user'];
        $company = $user->company;

        // Créer un badge spécial "Employé du mois"
        $badge = \App\Models\Badge::firstOrCreate([
            'name' => 'employee_of_month_' . now()->format('Y_m'),
            'title' => 'Employé du mois ' . now()->format('F Y'),
            'description' => 'Récompense pour des performances exceptionnelles ce mois-ci',
            'icon' => 'crown',
            'color' => '#FFD700',
            'category' => 'special',
            'frequency' => 'once',
            'points_reward' => 500,
            'rarity' => 'legendary',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Attribuer le badge
        $userBadge = $badge->awardToUser($user, [
            'score' => $employeeData['total_score'],
            'composite_score' => $employeeData['composite_score'],
            'consistency_bonus' => $employeeData['consistency_bonus'],
            'innovation_bonus' => $employeeData['innovation_bonus'],
            'recognition_type' => 'employee_of_month',
        ]);

        // Bonus KaliPoints supplémentaire
        $user->addKaliPoints(1000, true, "Employé du mois " . now()->format('F Y'));

        // Notification spéciale
        $this->notificationService->create([
            'user_id' => $user->id,
            'type' => 'employee_of_month',
            'title' => '👑 Félicitations, vous êtes l\'Employé du Mois !',
            'message' => "Vous avez été sélectionné(e) comme Employé du Mois pour " . now()->format('F Y') . " grâce à vos performances exceptionnelles !",
            'data' => [
                'badge_id' => $badge->id,
                'total_score' => $employeeData['total_score'],
                'recognition_period' => now()->format('F Y'),
                'bonus_points' => 1500, // Badge + bonus
            ],
            'priority' => 'critical',
            'channels' => ['database', 'pusher', 'email', 'sms'],
            'scheduled_for' => now(),
        ]);

        // Notification à toute l'entreprise
        $this->announceEmployeeOfMonth($user, $company, $employeeData);

        Log::info("Employee of the month awarded", [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'total_score' => $employeeData['total_score'],
        ]);
    }

    /**
     * Sélectionner l'équipe du mois
     */
    private function selectTeamOfMonth($companyId)
    {
        $currentMonth = now()->startOfMonth();
        
        $services = \App\Models\Service::where('company_id', $companyId)
                                     ->with(['employees.user'])
                                     ->get();

        $teamScores = [];

        foreach ($services as $service) {
            $serviceUsers = $service->employees->pluck('user')->filter();
            
            if ($serviceUsers->isEmpty()) continue;

            $serviceScore = $this->calculateTeamScore($serviceUsers, $companyId, $currentMonth);
            
            $teamScores[$service->id] = [
                'service' => $service,
                'members' => $serviceUsers,
                'score' => $serviceScore,
                'member_count' => $serviceUsers->count(),
            ];
        }

        if (empty($teamScores)) return null;

        // Trier par score
        uasort($teamScores, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return reset($teamScores);
    }

    /**
     * Calculer le score d'équipe
     */
    private function calculateTeamScore($teamMembers, $companyId, $currentMonth)
    {
        $userIds = $teamMembers->pluck('id')->toArray();
        
        // Score moyen des classements
        $avgRankings = Leaderboard::whereIn('user_id', $userIds)
                                 ->where('company_id', $companyId)
                                 ->where('period_type', 'monthly')
                                 ->where('period_date', $currentMonth)
                                 ->selectRaw('AVG(rank_overall) as avg_rank, COUNT(*) as participations')
                                 ->first();

        if (!$avgRankings || $avgRankings->participations == 0) return 0;

        // Bonus pour collaboration (défis d'équipe réussis)
        $teamChallenges = Challenge::where('company_id', $companyId)
                                 ->where('type', 'team')
                                 ->whereHas('userChallenges', function ($query) use ($userIds) {
                                     $query->whereIn('user_id', $userIds)
                                           ->where('is_completed', true);
                                 })
                                 ->count();

        // Score = (100 - rang moyen) + bonus collaboration
        $baseScore = max(0, 100 - $avgRankings->avg_rank);
        $collaborationBonus = $teamChallenges * 20;
        
        return round($baseScore + $collaborationBonus, 2);
    }

    /**
     * Attribuer le titre d'équipe du mois
     */
    private function awardTeamOfMonth($teamData)
    {
        $service = $teamData['service'];
        $members = $teamData['members'];

        foreach ($members as $member) {
            // Badge pour chaque membre de l'équipe
            $badge = \App\Models\Badge::firstOrCreate([
                'name' => 'team_of_month_' . now()->format('Y_m'),
                'title' => 'Équipe du mois ' . now()->format('F Y'),
                'description' => 'Membre de l\'équipe du mois pour des performances collectives exceptionnelles',
                'icon' => 'users',
                'color' => '#3B82F6',
                'category' => 'teamwork',
                'frequency' => 'once',
                'points_reward' => 300,
                'rarity' => 'epic',
                'is_active' => true,
                'is_public' => true,
            ]);

            $badge->awardToUser($member, [
                'team_score' => $teamData['score'],
                'service_name' => $service->name,
                'recognition_type' => 'team_of_month',
            ]);

            // Notification individuelle
            $this->notificationService->create([
                'user_id' => $member->id,
                'type' => 'team_of_month',
                'title' => '🏆 Votre équipe est l\'Équipe du Mois !',
                'message' => "L'équipe {$service->name} a été sélectionnée comme Équipe du Mois pour " . now()->format('F Y') . " !",
                'data' => [
                    'service_name' => $service->name,
                    'team_score' => $teamData['score'],
                    'member_count' => $teamData['member_count'],
                ],
                'priority' => 'high',
                'channels' => ['database', 'pusher', 'email'],
                'scheduled_for' => now(),
            ]);
        }

        Log::info("Team of the month awarded", [
            'service_id' => $service->id,
            'service_name' => $service->name,
            'member_count' => $teamData['member_count'],
            'score' => $teamData['score'],
        ]);
    }

    /**
     * Attribuer des reconnaissances spéciales
     */
    private function awardSpecialRecognitions($companyId)
    {
        $recognitions = [];

        // 1. Amélioration la plus remarquable
        $mostImproved = $this->findMostImprovedEmployee($companyId);
        if ($mostImproved) {
            $this->awardMostImproved($mostImproved);
            $recognitions[] = $mostImproved;
        }

        // 2. Esprit d'équipe exceptionnel
        $teamPlayer = $this->findExceptionalTeamPlayer($companyId);
        if ($teamPlayer) {
            $this->awardTeamPlayer($teamPlayer);
            $recognitions[] = $teamPlayer;
        }

        // 3. Innovation du mois
        $innovator = $this->findTopInnovator($companyId);
        if ($innovator) {
            $this->awardInnovator($innovator);
            $recognitions[] = $innovator;
        }

        return $recognitions;
    }

    /**
     * Trouver l'employé avec la plus grande amélioration
     */
    private function findMostImprovedEmployee($companyId)
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();

        $improvements = Leaderboard::byCompany($companyId)
                                  ->where('period_type', 'monthly')
                                  ->where('period_date', $currentMonth)
                                  ->where('is_improvement', true)
                                  ->whereNotNull('improvement_percentage')
                                  ->where('improvement_percentage', '>', 20) // Au moins 20% d'amélioration
                                  ->orderBy('improvement_percentage', 'desc')
                                  ->with('user')
                                  ->first();

        return $improvements;
    }

    /**
     * Distribuer des bonus d'amélioration
     */
    private function distributeImprovementBonuses($companyId)
    {
        $currentMonth = now()->startOfMonth();
        
        $improvements = Leaderboard::byCompany($companyId)
                                  ->where('period_type', 'monthly')
                                  ->where('period_date', $currentMonth)
                                  ->where('is_improvement', true)
                                  ->where('improvement_percentage', '>', 10)
                                  ->with('user')
                                  ->get();

        $totalBonus = 0;

        foreach ($improvements as $leaderboard) {
            $improvement = $leaderboard->improvement_percentage;
            $bonusPoints = match(true) {
                $improvement >= 50 => 200,
                $improvement >= 30 => 150,
                $improvement >= 20 => 100,
                $improvement >= 10 => 50,
                default => 0
            };

            if ($bonusPoints > 0) {
                $leaderboard->user->addKaliPoints(
                    $bonusPoints,
                    true,
                    "Bonus amélioration: +{$improvement}% en {$leaderboard->metric_label}"
                );

                $totalBonus += $bonusPoints;

                $this->notificationService->create([
                    'user_id' => $leaderboard->user->id,
                    'type' => 'improvement_bonus',
                    'title' => '📈 Bonus d\'amélioration !',
                    'message' => "Vous recevez {$bonusPoints} KaliPoints pour votre amélioration de {$improvement}% !",
                    'data' => [
                        'improvement_percentage' => $improvement,
                        'bonus_points' => $bonusPoints,
                        'metric' => $leaderboard->metric_label,
                    ],
                    'priority' => 'medium',
                    'channels' => ['database', 'pusher'],
                    'scheduled_for' => now(),
                ]);
            }
        }

        return $totalBonus;
    }

    /**
     * Annoncer l'employé du mois à toute l'entreprise
     */
    private function announceEmployeeOfMonth($user, $company, $employeeData)
    {
        $allEmployees = User::where('company_id', $company->id)
                           ->where('id', '!=', $user->id)
                           ->get();

        foreach ($allEmployees as $employee) {
            $this->notificationService->create([
                'user_id' => $employee->id,
                'type' => 'company_announcement',
                'title' => '👑 Nouvel Employé du Mois !',
                'message' => "Félicitez {$user->name} qui a été sélectionné(e) comme Employé du Mois de " . now()->format('F Y') . " !",
                'data' => [
                    'employee_of_month_id' => $user->id,
                    'employee_of_month_name' => $user->name,
                    'recognition_period' => now()->format('F Y'),
                    'total_score' => $employeeData['total_score'],
                ],
                'priority' => 'medium',
                'channels' => ['database', 'pusher'],
                'scheduled_for' => now(),
            ]);
        }
    }

    /**
     * Obtenir les statistiques de reconnaissance pour une entreprise
     */
    public function getRecognitionStats($companyId)
    {
        $currentYear = now()->year;

        return [
            'employees_of_month' => $this->getEmployeesOfMonth($companyId, $currentYear),
            'teams_of_month' => $this->getTeamsOfMonth($companyId, $currentYear),
            'special_recognitions' => $this->getSpecialRecognitions($companyId, $currentYear),
            'total_bonus_distributed' => $this->getTotalBonusDistributed($companyId, $currentYear),
            'recognition_frequency' => $this->getRecognitionFrequency($companyId),
        ];
    }

    /**
     * Planifier les reconnaissances automatiques
     */
    public function scheduleAutomaticRecognitions($companyId)
    {
        // Cette méthode sera appelée par une tâche cron mensuelle
        if (now()->day === 1) { // Premier jour du mois
            return $this->runMonthlyRecognition($companyId);
        }

        return null;
    }
}