<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\Leaderboard;
use App\Models\User;
use App\Models\Feedback;
use App\Events\BadgeEarned;
use App\Events\LeaderboardUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GamificationService
{
    /**
     * Vérifier et attribuer tous les badges éligibles pour un utilisateur
     */
    public function checkAndAwardBadges(User $user, $triggerEvent = null)
    {
        $badges = Badge::active()->get();
        $awardedBadges = [];

        foreach ($badges as $badge) {
            if ($this->shouldAwardBadge($badge, $user)) {
                $achievementData = $this->getAchievementData($badge, $user);
                $userBadge = $badge->awardToUser($user, $achievementData);
                
                if ($userBadge) {
                    $awardedBadges[] = $userBadge;
                    
                    // Déclencher événement
                    event(new BadgeEarned($userBadge));
                    
                    Log::info("Badge '{$badge->title}' attribué à {$user->name}");
                }
            }
        }

        return $awardedBadges;
    }

    /**
     * Vérifier si un badge doit être attribué
     */
    private function shouldAwardBadge(Badge $badge, User $user)
    {
        // Si badge déjà obtenu et c'est un badge "once"
        if ($badge->frequency === 'once') {
            $existing = UserBadge::where('user_id', $user->id)
                                 ->where('badge_id', $badge->id)
                                 ->exists();
            if ($existing) return false;
        }

        // Vérifier l'éligibilité selon les critères
        $period = $this->getCurrentPeriodForBadge($badge);
        return $badge->checkEligibility($user, $period);
    }

    /**
     * Obtenir les données d'achievement pour un badge
     */
    private function getAchievementData(Badge $badge, User $user)
    {
        $criteria = $badge->criteria;
        $period = $this->getCurrentPeriodForBadge($badge);

        switch ($criteria['type'] ?? '') {
            case 'satisfaction_rate':
                return $this->getSatisfactionData($user, $period);
                
            case 'avg_resolution_time':
                return $this->getResolutionTimeData($user, $period);
                
            case 'monthly_ranking':
                return $this->getRankingData($user, $period, $criteria['metric']);
                
            default:
                return [];
        }
    }

    /**
     * Calculer et mettre à jour tous les classements
     */
    public function calculateLeaderboards($companyId, $periodType = 'monthly', $periodDate = null)
    {
        $periodDate = $periodDate ?? $this->getPeriodDate($periodType);
        
        $metrics = [
            'satisfaction_score',
            'total_feedbacks', 
            'positive_feedbacks',
            'resolution_time',
            'response_time',
            'kalipoints_earned',
            'overall_performance'
        ];

        foreach ($metrics as $metric) {
            $this->calculateLeaderboardForMetric($companyId, $periodType, $periodDate, $metric);
        }

        // Publier les classements
        $this->publishLeaderboards($companyId, $periodType, $periodDate);
    }

    /**
     * Calculer le classement pour une métrique spécifique
     */
    private function calculateLeaderboardForMetric($companyId, $periodType, $periodDate, $metricType)
    {
        $users = User::where('company_id', $companyId)
                    ->whereHas('employee')
                    ->with('employee.service')
                    ->get();

        $rankings = [];

        foreach ($users as $user) {
            $score = $this->calculateMetricScore($user, $metricType, $periodType, $periodDate);
            
            if ($score !== null) {
                $rankings[] = [
                    'user' => $user,
                    'score' => $score,
                    'detailed_metrics' => $this->getDetailedMetrics($user, $periodType, $periodDate)
                ];
            }
        }

        // Trier par score (croissant pour les temps, décroissant pour les autres)
        $isTimeMetric = in_array($metricType, ['resolution_time', 'response_time']);
        usort($rankings, function ($a, $b) use ($isTimeMetric) {
            return $isTimeMetric ? 
                $a['score'] <=> $b['score'] : 
                $b['score'] <=> $a['score'];
        });

        // Créer ou mettre à jour les entrées du classement
        $this->saveLeaderboardEntries($rankings, $companyId, $periodType, $periodDate, $metricType);
    }

    /**
     * Calculer le score pour une métrique
     */
    private function calculateMetricScore(User $user, $metricType, $periodType, $periodDate)
    {
        $feedbacks = $this->getFeedbacksForPeriod($user, $periodType, $periodDate);

        if ($feedbacks->isEmpty()) return null;

        switch ($metricType) {
            case 'satisfaction_score':
                return $feedbacks->avg('rating') * 20; // Convertir 1-5 en 0-100
                
            case 'total_feedbacks':
                return $feedbacks->count();
                
            case 'positive_feedbacks':
                return $feedbacks->where('rating', '>=', 4)->count();
                
            case 'resolution_time':
                $resolved = $feedbacks->whereNotNull('resolved_at');
                return $resolved->isEmpty() ? null : 
                    $resolved->map(function ($f) {
                        return $f->created_at->diffInHours($f->resolved_at);
                    })->avg();
                    
            case 'response_time':
                $treated = $feedbacks->whereNotNull('treated_at');
                return $treated->isEmpty() ? null :
                    $treated->map(function ($f) {
                        return $f->created_at->diffInHours($f->treated_at);
                    })->avg();
                    
            case 'kalipoints_earned':
                return $feedbacks->sum('kalipoints');
                
            case 'overall_performance':
                return $this->calculateOverallPerformanceScore($feedbacks);
                
            default:
                return null;
        }
    }

    /**
     * Calculer un score de performance globale
     */
    private function calculateOverallPerformanceScore($feedbacks)
    {
        if ($feedbacks->isEmpty()) return 0;

        $satisfactionScore = ($feedbacks->avg('rating') / 5) * 100;
        $volumeScore = min($feedbacks->count() * 2, 40); // Max 40 points pour le volume
        
        $resolutionBonus = 0;
        $resolved = $feedbacks->whereNotNull('resolved_at');
        if ($resolved->isNotEmpty()) {
            $avgResolutionHours = $resolved->map(function ($f) {
                return $f->created_at->diffInHours($f->resolved_at);
            })->avg();
            
            $resolutionBonus = max(0, 20 - ($avgResolutionHours * 2)); // Bonus pour rapidité
        }

        return round($satisfactionScore * 0.6 + $volumeScore * 0.3 + $resolutionBonus * 0.1, 2);
    }

    /**
     * Sauvegarder les entrées du classement
     */
    private function saveLeaderboardEntries($rankings, $companyId, $periodType, $periodDate, $metricType)
    {
        $totalParticipants = count($rankings);
        
        foreach ($rankings as $index => $ranking) {
            $user = $ranking['user'];
            $rank = $index + 1;
            $score = $ranking['score'];
            
            // Calculer les points de récompense selon le rang
            $points = $this->calculateRankingPoints($rank, $totalParticipants, $metricType);
            
            // Déterminer position podium
            $podiumPosition = $rank <= 3 ? $rank : null;
            $isWinner = $rank === 1;
            
            // Calculer l'amélioration vs période précédente
            $improvement = $this->calculateImprovement($user, $metricType, $periodType, $periodDate, $score);

            Leaderboard::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'period_type' => $periodType,
                    'period_date' => $periodDate,
                    'metric_type' => $metricType,
                ],
                [
                    'service_id' => $user->employee?->service_id,
                    'score' => $score,
                    'rank_overall' => $rank,
                    'rank_in_service' => $this->calculateServiceRank($user, $rankings),
                    'total_participants' => $totalParticipants,
                    'detailed_metrics' => $ranking['detailed_metrics'],
                    'improvement_percentage' => $improvement['percentage'],
                    'is_improvement' => $improvement['is_improvement'],
                    'points_earned' => $points,
                    'is_winner' => $isWinner,
                    'podium_position' => $podiumPosition,
                    'calculated_at' => now(),
                ]
            );
        }
    }

    /**
     * Calculer les points de récompense selon le classement
     */
    private function calculateRankingPoints($rank, $totalParticipants, $metricType)
    {
        $basePoints = match($metricType) {
            'satisfaction_score', 'overall_performance' => 100,
            'total_feedbacks', 'positive_feedbacks' => 50,
            'resolution_time', 'response_time' => 75,
            'kalipoints_earned' => 25,
            default => 50
        };

        return match($rank) {
            1 => $basePoints * 3,    // 1er place
            2 => $basePoints * 2,    // 2ème place
            3 => $basePoints * 1.5,  // 3ème place
            default => max(0, $basePoints * (1 - ($rank / $totalParticipants))) // Points dégressifs
        };
    }

    /**
     * Publier tous les classements d'une période
     */
    private function publishLeaderboards($companyId, $periodType, $periodDate)
    {
        $leaderboards = Leaderboard::byCompany($companyId)
                                  ->byPeriod($periodType, $periodDate)
                                  ->get();

        foreach ($leaderboards as $leaderboard) {
            $leaderboard->publish();
        }

        // Déclencher événement global
        event(new LeaderboardUpdated($companyId, $periodType, $periodDate));
    }

    /**
     * Obtenir les métriques détaillées pour un utilisateur
     */
    private function getDetailedMetrics(User $user, $periodType, $periodDate)
    {
        $feedbacks = $this->getFeedbacksForPeriod($user, $periodType, $periodDate);

        return [
            'total_feedbacks' => $feedbacks->count(),
            // Pour les feedbacks POSITIFS: rating >= 4 = vraiment positifs
            'positive_feedbacks' => $feedbacks->where('type', 'positif')->where('rating', '>=', 4)->count() +
                                   $feedbacks->where('type', 'appreciation')->where('rating', '>=', 4)->count(),
            // Pour les feedbacks NÉGATIFS: type négatif avec rating >= 4 = plus graves
            // OU feedbacks positifs avec rating <= 2 = vraiment décevants
            'negative_feedbacks' => $feedbacks->where('type', 'negatif')->where('rating', '>=', 4)->count() +
                                  $feedbacks->where('type', 'incident')->where('rating', '>=', 4)->count() +
                                  $feedbacks->where('type', 'positif')->where('rating', '<=', 2)->count() +
                                  $feedbacks->where('type', 'appreciation')->where('rating', '<=', 2)->count(),
            'avg_rating' => round($feedbacks->avg('rating'), 2),
            'total_kalipoints' => $feedbacks->sum('kalipoints'),
            'incidents_resolved' => $feedbacks->whereNotNull('resolved_at')->count(),
            'avg_resolution_hours' => $feedbacks->whereNotNull('resolved_at')->isEmpty() ? 0 :
                round($feedbacks->whereNotNull('resolved_at')->map(function ($f) {
                    return $f->created_at->diffInHours($f->resolved_at);
                })->avg(), 2),
        ];
    }

    /**
     * Obtenir les feedbacks pour une période
     */
    private function getFeedbacksForPeriod(User $user, $periodType, $periodDate)
    {
        $query = Feedback::where('employee_id', $user->id);

        switch ($periodType) {
            case 'daily':
                $query->whereDate('created_at', $periodDate);
                break;
            case 'weekly':
                $startOfWeek = Carbon::parse($periodDate)->startOfWeek();
                $endOfWeek = Carbon::parse($periodDate)->endOfWeek();
                $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                break;
            case 'monthly':
                $query->whereMonth('created_at', Carbon::parse($periodDate)->month)
                      ->whereYear('created_at', Carbon::parse($periodDate)->year);
                break;
            case 'yearly':
                $query->whereYear('created_at', Carbon::parse($periodDate)->year);
                break;
        }

        return $query->get();
    }

    /**
     * Obtenir la date de période actuelle
     */
    private function getPeriodDate($periodType)
    {
        return match($periodType) {
            'daily' => today(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            'yearly' => now()->startOfYear(),
            default => today()
        };
    }

    /**
     * Obtenir les statistiques de gamification pour une entreprise
     */
    public function getGamificationStats($companyId)
    {
        return [
            'total_badges_available' => Badge::active()->count(),
            'total_badges_earned' => UserBadge::byCompany($companyId)->count(),
            'unique_badge_earners' => UserBadge::byCompany($companyId)->distinct('user_id')->count(),
            'top_badge_earner' => $this->getTopBadgeEarner($companyId),
            'recent_achievements' => UserBadge::getRecentAchievements($companyId, 5),
            'badge_distribution' => UserBadge::getBadgeDistribution($companyId),
            'monthly_rankings' => Leaderboard::getCurrentRankings($companyId),
            'improvement_trends' => $this->getImprovementTrends($companyId),
        ];
    }

    /**
     * Obtenir le top badge earner
     */
    private function getTopBadgeEarner($companyId)
    {
        return UserBadge::byCompany($companyId)
                       ->select('user_id', DB::raw('COUNT(*) as badge_count'), DB::raw('SUM(points_earned) as total_points'))
                       ->groupBy('user_id')
                       ->orderBy('badge_count', 'desc')
                       ->orderBy('total_points', 'desc')
                       ->with('user')
                       ->first();
    }

    /**
     * Obtenir la période actuelle pour un badge
     */
    private function getCurrentPeriodForBadge(Badge $badge)
    {
        return match($badge->frequency) {
            'daily' => ['date' => today()],
            'weekly' => ['week' => now()->weekOfYear, 'year' => now()->year],
            'monthly' => ['month' => now()->month, 'year' => now()->year],
            'yearly' => ['year' => now()->year],
            default => null
        };
    }

    /**
     * Lancer la vérification quotidienne des badges et classements
     */
    public function runDailyGamificationCheck($companyId)
    {
        Log::info("Lancement vérification gamification quotidienne pour entreprise {$companyId}");

        // Vérifier les badges pour tous les utilisateurs
        $users = User::where('company_id', $companyId)->get();
        $totalBadgesAwarded = 0;

        foreach ($users as $user) {
            $badges = $this->checkAndAwardBadges($user, 'daily_check');
            $totalBadgesAwarded += count($badges);
        }

        // Calculer les classements mensuels si on est en fin de mois
        if (now()->isLastOfMonth()) {
            $this->calculateLeaderboards($companyId, 'monthly');
        }

        // Calculer les classements hebdomadaires si on est dimanche
        if (now()->isSunday()) {
            $this->calculateLeaderboards($companyId, 'weekly');
        }

        Log::info("Vérification gamification terminée : {$totalBadgesAwarded} badges attribués");

        return [
            'badges_awarded' => $totalBadgesAwarded,
            'company_id' => $companyId,
        ];
    }

    /**
     * Obtenir les tendances d'amélioration
     */
    private function getImprovementTrends($companyId)
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        
        $currentData = Leaderboard::byCompany($companyId)
                                 ->where('period_type', 'monthly')
                                 ->where('period_date', $currentMonth)
                                 ->selectRaw('metric_type, AVG(score) as avg_score, COUNT(*) as participant_count')
                                 ->groupBy('metric_type')
                                 ->get()
                                 ->keyBy('metric_type');
                                 
        $previousData = Leaderboard::byCompany($companyId)
                                  ->where('period_type', 'monthly')
                                  ->where('period_date', $previousMonth)
                                  ->selectRaw('metric_type, AVG(score) as avg_score, COUNT(*) as participant_count')
                                  ->groupBy('metric_type')
                                  ->get()
                                  ->keyBy('metric_type');
        
        $trends = [];
        
        foreach ($currentData as $metric => $current) {
            $previous = $previousData->get($metric);
            
            if ($previous) {
                $improvement = (($current->avg_score - $previous->avg_score) / $previous->avg_score) * 100;
                
                $trends[$metric] = [
                    'metric' => $metric,
                    'current_score' => round($current->avg_score, 2),
                    'previous_score' => round($previous->avg_score, 2),
                    'improvement_percentage' => round($improvement, 2),
                    'trend' => $improvement > 0 ? 'up' : ($improvement < 0 ? 'down' : 'stable'),
                    'participants' => $current->participant_count,
                ];
            }
        }
        
        return $trends;
    }

    /**
     * Calculer l'amélioration par rapport à la période précédente
     */
    private function calculateImprovement(User $user, $metricType, $periodType, $periodDate, $currentScore)
    {
        $previousPeriodDate = $this->getPreviousPeriodDate($periodType, $periodDate);
        
        $previousLeaderboard = Leaderboard::where('user_id', $user->id)
                                         ->where('metric_type', $metricType)
                                         ->where('period_type', $periodType)
                                         ->where('period_date', $previousPeriodDate)
                                         ->first();
        
        if (!$previousLeaderboard) {
            return ['percentage' => null, 'is_improvement' => null];
        }
        
        $previousScore = $previousLeaderboard->score;
        
        if ($previousScore == 0) {
            return ['percentage' => null, 'is_improvement' => $currentScore > 0];
        }
        
        $improvement = (($currentScore - $previousScore) / $previousScore) * 100;
        
        return [
            'percentage' => round($improvement, 2),
            'is_improvement' => $improvement > 0
        ];
    }

    /**
     * Obtenir la date de la période précédente
     */
    private function getPreviousPeriodDate($periodType, $currentPeriodDate)
    {
        $date = Carbon::parse($currentPeriodDate);
        
        return match($periodType) {
            'daily' => $date->subDay(),
            'weekly' => $date->subWeek(),
            'monthly' => $date->subMonth(),
            'yearly' => $date->subYear(),
            default => $date->subMonth()
        };
    }

    /**
     * Calculer le rang dans le service
     */
    private function calculateServiceRank(User $user, $rankings)
    {
        if (!$user->employee?->service_id) {
            return null;
        }
        
        $serviceRankings = collect($rankings)->filter(function ($ranking) use ($user) {
            return $ranking['user']->employee?->service_id === $user->employee->service_id;
        })->values();
        
        foreach ($serviceRankings as $index => $ranking) {
            if ($ranking['user']->id === $user->id) {
                return $index + 1;
            }
        }
        
        return null;
    }

    /**
     * Obtenir les données de satisfaction
     */
    private function getSatisfactionData(User $user, $period)
    {
        $feedbacks = $this->getFeedbacksForPeriod($user, $period['type'] ?? 'monthly', $period['date'] ?? now());
        
        return [
            'total_feedbacks' => $feedbacks->count(),
            'avg_rating' => $feedbacks->avg('rating'),
            'satisfaction_rate' => $feedbacks->isEmpty() ? 0 : ($feedbacks->avg('rating') / 5) * 100,
            'positive_count' => $feedbacks->where('rating', '>=', 4)->count(),
        ];
    }

    /**
     * Obtenir les données de temps de résolution
     */
    private function getResolutionTimeData(User $user, $period)
    {
        $feedbacks = $this->getFeedbacksForPeriod($user, $period['type'] ?? 'monthly', $period['date'] ?? now());
        $resolved = $feedbacks->whereNotNull('resolved_at');
        
        return [
            'total_incidents' => $feedbacks->count(),
            'resolved_count' => $resolved->count(),
            'resolution_rate' => $feedbacks->count() > 0 ? ($resolved->count() / $feedbacks->count()) * 100 : 0,
            'avg_resolution_hours' => $resolved->isEmpty() ? 0 : $resolved->map(function ($f) {
                return $f->created_at->diffInHours($f->resolved_at);
            })->avg(),
        ];
    }

    /**
     * Obtenir les données de classement
     */
    private function getRankingData(User $user, $period, $metric)
    {
        $leaderboard = Leaderboard::where('user_id', $user->id)
                                 ->where('metric_type', $metric)
                                 ->where('period_type', $period['type'] ?? 'monthly')
                                 ->when(isset($period['date']), function ($query) use ($period) {
                                     $query->where('period_date', $period['date']);
                                 })
                                 ->first();
        
        return $leaderboard ? [
            'rank' => $leaderboard->rank_overall,
            'score' => $leaderboard->score,
            'total_participants' => $leaderboard->total_participants,
            'points_earned' => $leaderboard->points_earned,
        ] : [];
    }
}