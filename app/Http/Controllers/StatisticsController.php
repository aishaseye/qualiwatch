<?php

namespace App\Http\Controllers;

use App\Models\CompanyStatistic;
use App\Models\ServiceStatistic;
use App\Models\EmployeeStatistic;
use App\Services\StatisticsCalculatorService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    protected StatisticsCalculatorService $statisticsService;

    public function __construct(StatisticsCalculatorService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Statistiques historiques de l'entreprise
     */
    public function companyHistoricalStats(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut spécifier company_id, sinon utilise sa propre entreprise
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if (!$companyId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Super admin doit spécifier company_id'
                    ], 400);
                }
            } else {
                $company = $user->company;
                if (!$company) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Utilisateur n\'a pas d\'entreprise associée'
                    ], 400);
                }
                $companyId = $company->id;
            }

            $periodType = $request->get('period_type', 'monthly');
            $months = (int) $request->get('months', 6);

            $stats = CompanyStatistic::where('company_id', $companyId)
                ->where('period_type', $periodType)
                ->orderBy('period_date', 'desc')
                ->limit($months)
                ->get()
                ->reverse()
                ->values();

            // Calculer les tendances
            $trends = $this->calculateTrends($stats);

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'trends' => $trends,
                    'period_type' => $periodType,
                    'periods_count' => $stats->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques historiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des services avec comparaisons
     */
    public function servicesComparison(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut spécifier company_id, sinon utilise sa propre entreprise
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if (!$companyId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Super admin doit spécifier company_id'
                    ], 400);
                }
            } else {
                $company = $user->company;
                if (!$company) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Utilisateur n\'a pas d\'entreprise associée'
                    ], 400);
                }
                $companyId = $company->id;
            }

            $periodType = $request->get('period_type', 'monthly');
            $date = $request->get('date', now()->toDateString());

            $serviceStats = ServiceStatistic::where('company_id', $companyId)
                ->where('period_type', $periodType)
                ->where('period_date', $date)
                ->with(['service', 'topEmployee'])
                ->orderBy('performance_score', 'desc')
                ->get();

            // Calculer les rangs
            foreach ($serviceStats as $index => $stat) {
                $stat->rank_in_company = $index + 1;
                $stat->save();
            }

            // Moyenne de l'entreprise
            $companyAverage = $serviceStats->avg('satisfaction_score');

            // Mettre à jour les comparaisons vs moyenne entreprise
            foreach ($serviceStats as $stat) {
                $stat->vs_company_average = $companyAverage > 0 
                    ? round((($stat->satisfaction_score - $companyAverage) / $companyAverage) * 100, 2)
                    : 0;
                $stat->save();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'services_stats' => $serviceStats,
                    'company_average' => round($companyAverage, 2),
                    'period' => $date,
                    'period_type' => $periodType,
                    'total_services' => $serviceStats->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comparaisons services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Classement des employés avec badges et recommandations
     */
    public function employeesRanking(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut spécifier company_id, sinon utilise sa propre entreprise
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if (!$companyId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Super admin doit spécifier company_id'
                    ], 400);
                }
            } else {
                $company = $user->company;
                if (!$company) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Utilisateur n\'a pas d\'entreprise associée'
                    ], 400);
                }
                $companyId = $company->id;
            }

            $periodType = $request->get('period_type', 'monthly');
            $date = $request->get('date', now()->toDateString());
            $serviceId = $request->get('service_id');

            $query = EmployeeStatistic::where('company_id', $companyId)
                ->where('period_type', $periodType)
                ->where('period_date', $date)
                ->with(['employee.service', 'service']);

            if ($serviceId) {
                $query->where('service_id', $serviceId);
            }

            $employeeStats = $query->orderBy('performance_score', 'desc')->get();

            // Calculer les rangs
            foreach ($employeeStats as $index => $stat) {
                if ($serviceId) {
                    $stat->rank_in_service = $index + 1;
                } else {
                    $stat->rank_in_company = $index + 1;
                }
                $stat->save();
            }

            // Statistiques du groupe
            $topPerformers = $employeeStats->where('performance_score', '>=', 85)->count();
            $needsImprovement = $employeeStats->where('performance_score', '<', 60)->count();
            $employeeOfPeriod = $employeeStats->where('employee_of_period', true)->first();

            // Badges les plus fréquents
            $allBadges = $employeeStats->flatMap->badges_earned;
            $badgesCounts = collect($allBadges)->countBy()->sortDesc()->take(5);

            return response()->json([
                'success' => true,
                'data' => [
                    'employees_stats' => $employeeStats,
                    'summary' => [
                        'total_employees' => $employeeStats->count(),
                        'top_performers' => $topPerformers,
                        'needs_improvement' => $needsImprovement,
                        'average_performance' => round($employeeStats->avg('performance_score'), 2),
                    ],
                    'employee_of_period' => $employeeOfPeriod,
                    'popular_badges' => $badgesCounts,
                    'period' => $date,
                    'period_type' => $periodType,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du classement des employés',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Profil statistique détaillé d'un employé
     */
    public function employeeDetailedProfile(Request $request, $employeeId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut spécifier company_id, sinon utilise sa propre entreprise
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if (!$companyId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Super admin doit spécifier company_id'
                    ], 400);
                }
            } else {
                $company = $user->company;
                if (!$company) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Utilisateur n\'a pas d\'entreprise associée'
                    ], 400);
                }
                $companyId = $company->id;
            }

            $periodType = $request->get('period_type', 'monthly');
            $periods = (int) $request->get('periods', 6);

            // Statistiques sur plusieurs périodes
            $stats = EmployeeStatistic::where('company_id', $companyId)
                ->where('employee_id', $employeeId)
                ->where('period_type', $periodType)
                ->with(['employee.service'])
                ->orderBy('period_date', 'desc')
                ->limit($periods)
                ->get()
                ->reverse()
                ->values();

            if ($stats->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune statistique trouvée pour cet employé'
                ], 404);
            }

            $employee = $stats->first()->employee;
            $latestStats = $stats->last();

            // Évolution des performances
            $performanceEvolution = $stats->map(function ($stat) {
                return [
                    'period' => $stat->formatted_period,
                    'performance_score' => $stat->performance_score,
                    'satisfaction_score' => $stat->satisfaction_score,
                    'total_feedbacks' => $stat->total_feedbacks,
                ];
            });

            // Badges collectés sur toutes les périodes
            $allBadges = $stats->flatMap->badges_earned->unique()->values();

            // Recommandations consolidées
            $allTrainingRecommendations = $stats->flatMap->training_recommendations->unique()->values();
            $allRecognitionSuggestions = $stats->flatMap->recognition_suggestions->unique()->values();

            // Calcul de la tendance d'amélioration
            if ($stats->count() >= 2) {
                $firstScore = $stats->first()->performance_score;
                $lastScore = $stats->last()->performance_score;
                $improvementTrend = $firstScore > 0 ? round((($lastScore - $firstScore) / $firstScore) * 100, 2) : 0;
                
                // Mettre à jour la tendance dans les stats les plus récentes
                $latestStats->update(['improvement_trend' => $improvementTrend]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee,
                    'latest_stats' => $latestStats,
                    'performance_evolution' => $performanceEvolution,
                    'all_badges_earned' => $allBadges,
                    'training_recommendations' => $allTrainingRecommendations,
                    'recognition_suggestions' => $allRecognitionSuggestions,
                    'analysis' => [
                        'performance_level' => $latestStats->performance_level,
                        'trend_status' => $latestStats->trend_status,
                        'consistency_level' => $latestStats->consistency_level,
                        'overall_rating' => $latestStats->overall_rating,
                        'has_recognition' => $latestStats->has_recognition,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil détaillé',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Forcer le recalcul des statistiques
     */
    public function recalculateStatistics(Request $request)
    {
        try {
            $request->validate([
                'period_type' => 'required|in:daily,weekly,monthly,yearly',
                'date' => 'nullable|date',
            ]);

            $user = $request->user();
            
            // Super admin peut spécifier company_id, sinon utilise sa propre entreprise
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if (!$companyId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Super admin doit spécifier company_id'
                    ], 400);
                }
                $company = \App\Models\Company::findOrFail($companyId);
            } else {
                $company = $user->company;
                if (!$company) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Utilisateur n\'a pas d\'entreprise associée'
                    ], 400);
                }
            }
            
            $periodType = $request->period_type;
            $date = $request->date ? Carbon::parse($request->date) : now();

            // Recalculer les statistiques
            $this->statisticsService->calculateCompanyStatistics($company, $periodType, $date);
            $this->statisticsService->calculateServicesStatistics($company, $periodType, $date);
            $this->statisticsService->calculateEmployeesStatistics($company, $periodType, $date);

            return response()->json([
                'success' => true,
                'message' => 'Statistiques recalculées avec succès',
                'data' => [
                    'company_id' => $company->id,
                    'period_type' => $periodType,
                    'date' => $date->toDateString(),
                    'recalculated_at' => now(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du recalcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Résumé statistique global
     */
    public function globalSummary(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut spécifier company_id, sinon utilise sa propre entreprise
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if (!$companyId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Super admin doit spécifier company_id'
                    ], 400);
                }
            } else {
                $company = $user->company;
                if (!$company) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Utilisateur n\'a pas d\'entreprise associée'
                    ], 400);
                }
                $companyId = $company->id;
            }

            $periodType = $request->get('period_type', 'monthly');
            $date = $request->get('date', now()->toDateString());

            // Statistiques entreprise
            $companyStats = CompanyStatistic::where('company_id', $companyId)
                ->where('period_type', $periodType)
                ->where('period_date', $date)
                ->first();

            // Top 3 services
            $topServices = ServiceStatistic::where('company_id', $companyId)
                ->where('period_type', $periodType)
                ->where('period_date', $date)
                ->with('service')
                ->orderBy('performance_score', 'desc')
                ->limit(3)
                ->get();

            // Top 5 employés
            $topEmployees = EmployeeStatistic::where('company_id', $companyId)
                ->where('period_type', $periodType)
                ->where('period_date', $date)
                ->with('employee')
                ->orderBy('performance_score', 'desc')
                ->limit(5)
                ->get();

            // Employé de la période
            $employeeOfPeriod = EmployeeStatistic::where('company_id', $companyId)
                ->where('period_type', $periodType)
                ->where('period_date', $date)
                ->where('employee_of_period', true)
                ->with('employee')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'company_stats' => $companyStats,
                    'top_services' => $topServices,
                    'top_employees' => $topEmployees,
                    'employee_of_period' => $employeeOfPeriod,
                    'period' => $date,
                    'period_type' => $periodType,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du résumé global',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer les tendances à partir des statistiques
     */
    private function calculateTrends($stats)
    {
        if ($stats->count() < 2) {
            return null;
        }

        $latest = $stats->last();
        $previous = $stats->get($stats->count() - 2);

        return [
            'satisfaction_score' => [
                'current' => $latest->satisfaction_score,
                'previous' => $previous->satisfaction_score,
                'change' => round($latest->satisfaction_score - $previous->satisfaction_score, 2),
                'change_percent' => $previous->satisfaction_score > 0 
                    ? round((($latest->satisfaction_score - $previous->satisfaction_score) / $previous->satisfaction_score) * 100, 2)
                    : 0
            ],
            'total_feedbacks' => [
                'current' => $latest->total_feedbacks,
                'previous' => $previous->total_feedbacks,
                'change' => $latest->total_feedbacks - $previous->total_feedbacks,
                'change_percent' => $previous->total_feedbacks > 0 
                    ? round((($latest->total_feedbacks - $previous->total_feedbacks) / $previous->total_feedbacks) * 100, 2)
                    : 0
            ],
            'validation_completion_rate' => [
                'current' => $latest->validation_completion_rate,
                'previous' => $previous->validation_completion_rate,
                'change' => round($latest->validation_completion_rate - $previous->validation_completion_rate, 2),
            ],
        ];
    }
}