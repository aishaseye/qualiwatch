<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Feedback;
use App\Models\Client;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    /**
     * Liste toutes les entreprises avec rankings complets (Super Admin)
     */
    public function getAllCompanies(Request $request)
    {
        try {
            // Filtres temporels
            $period = $request->get('period'); // week, month, year
            $dateFilter = $this->getDateFilter($period);
            
            // Récupération des entreprises avec statistiques complètes
            $companies = Company::with(['manager', 'services', 'employees', 'businessSector'])
                ->withCount([
                    'feedbacks',
                    'services', 
                    'employees',
                    'feedbacks as positive_feedbacks' => function($query) use ($dateFilter) {
                        $query->where('type', 'positif');
                        if ($dateFilter) $query->where('created_at', '>=', $dateFilter);
                    },
                    'feedbacks as negative_feedbacks' => function($query) use ($dateFilter) {
                        $query->where('type', 'negatif');
                        if ($dateFilter) $query->where('created_at', '>=', $dateFilter);
                    },
                    'feedbacks as suggestion_feedbacks' => function($query) use ($dateFilter) {
                        $query->where('type', 'suggestion');
                        if ($dateFilter) $query->where('created_at', '>=', $dateFilter);
                    },
                    'feedbacks as treated_feedbacks' => function($query) use ($dateFilter) {
                        $query->where('status', 'treated');
                        if ($dateFilter) $query->where('created_at', '>=', $dateFilter);
                    },
                    'feedbacks as period_feedbacks' => function($query) use ($dateFilter) {
                        if ($dateFilter) $query->where('created_at', '>=', $dateFilter);
                    }
                ])
                ->get()
                ->map(function ($company) use ($dateFilter, $period) {
                    // Calculs des KPIs et scores
                    $totalFeedbacks = $period ? $company->period_feedbacks_count : $company->feedbacks_count;
                    $positiveFeedbacks = $company->positive_feedbacks_count;
                    $negativeFeedbacks = $company->negative_feedbacks_count;
                    $suggestionFeedbacks = $company->suggestion_feedbacks_count;
                    $treatedFeedbacks = $company->treated_feedbacks_count;
                    
                    // Score de satisfaction (basé sur les feedbacks positifs)
                    $satisfactionScore = $totalFeedbacks > 0 ? round(($positiveFeedbacks / $totalFeedbacks) * 100, 1) : 0;
                    
                    // Note moyenne
                    $averageRating = $this->getAverageRating($company, $dateFilter);
                    
                    // Taux de traitement
                    $treatmentRate = $totalFeedbacks > 0 ? round(($treatedFeedbacks / $totalFeedbacks) * 100, 1) : 0;
                    
                    // Score global (combinaison satisfaction + traitement + note)
                    $globalScore = round(($satisfactionScore * 0.4 + $treatmentRate * 0.3 + ($averageRating * 20) * 0.3), 1);
                    
                    $company->stats = [
                        'total_feedbacks' => $totalFeedbacks,
                        'positive_feedbacks' => $positiveFeedbacks,
                        'negative_feedbacks' => $negativeFeedbacks,
                        'suggestion_feedbacks' => $suggestionFeedbacks,
                        'treated_feedbacks' => $treatedFeedbacks,
                        'satisfaction_score' => $satisfactionScore,
                        'treatment_rate' => $treatmentRate,
                        'average_rating' => $averageRating,
                        'global_score' => $globalScore,
                        'period_filter' => $period ?? 'all'
                    ];
                    
                    return $company;
                });

            // Rankings par catégories
            $rankings = [
                'most_feedbacks' => $companies->sortByDesc('stats.total_feedbacks')->take(10)->values(),
                'most_positive' => $companies->sortByDesc('stats.positive_feedbacks')->take(10)->values(),
                'most_negative' => $companies->sortByDesc('stats.negative_feedbacks')->take(10)->values(),
                'most_suggestions' => $companies->sortByDesc('stats.suggestion_feedbacks')->take(10)->values(),
                'best_satisfaction' => $companies->sortByDesc('stats.satisfaction_score')->take(10)->values(),
                'best_treatment_rate' => $companies->sortByDesc('stats.treatment_rate')->take(10)->values(),
                'best_rated' => $companies->sortByDesc('stats.average_rating')->take(10)->values(),
                'best_global_score' => $companies->sortByDesc('stats.global_score')->take(10)->values()
            ];

            // KPIs globaux pour la période
            $globalKPIs = [
                'total_companies' => $companies->count(),
                'active_companies' => $companies->where('stats.total_feedbacks', '>', 0)->count(),
                'total_feedbacks' => $companies->sum('stats.total_feedbacks'),
                'total_positive' => $companies->sum('stats.positive_feedbacks'),
                'total_negative' => $companies->sum('stats.negative_feedbacks'),
                'total_suggestions' => $companies->sum('stats.suggestion_feedbacks'),
                'average_satisfaction' => round($companies->avg('stats.satisfaction_score'), 1),
                'average_treatment_rate' => round($companies->avg('stats.treatment_rate'), 1),
                'period_filter' => $period ?? 'all'
            ];

            // Pagination des entreprises complètes
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            $companiesPaginated = $companies->forPage($page, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'companies' => $companiesPaginated->values(),
                    'rankings' => $rankings,
                    'global_kpis' => $globalKPIs,
                    'pagination' => [
                        'total' => $companies->count(),
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'last_page' => ceil($companies->count() / $perPage),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entreprises',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails complets d'une entreprise (Super Admin)
     */
    public function getCompanyDetails(Request $request, $companyId)
    {
        try {
            $company = Company::with([
                'manager', 
                'services.feedbacks', 
                'employees.feedbacks',
                'feedbacks.client',
                'businessSector'
            ])
            ->withCount([
                'feedbacks',
                'services', 
                'employees',
                'feedbacks as positive_feedbacks' => function($query) {
                    $query->where('type', 'positif');
                },
                'feedbacks as negative_feedbacks' => function($query) {
                    $query->where('type', 'negatif');
                },
                'feedbacks as suggestion_feedbacks' => function($query) {
                    $query->where('type', 'suggestion');
                }
            ])
            ->findOrFail($companyId);

            // Stats par mois (6 derniers mois)
            $timeline = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthFeedbacks = $company->feedbacks()
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type');

                $timeline[] = [
                    'month' => $date->format('M Y'),
                    'positif' => $monthFeedbacks->get('positif', 0),
                    'negatif' => $monthFeedbacks->get('negatif', 0),
                    'suggestion' => $monthFeedbacks->get('suggestion', 0),
                ];
            }

            // Score de satisfaction
            $satisfactionScore = 0;
            if ($company->feedbacks_count > 0) {
                $avgKalipoints = $company->feedbacks()->avg('kalipoints');
                $satisfactionScore = round(($avgKalipoints / 5) * 100, 1);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'company' => $company,
                    'satisfaction_score' => $satisfactionScore,
                    'timeline' => $timeline,
                    'stats' => [
                        'total_feedbacks' => $company->feedbacks_count,
                        'positive_feedbacks' => $company->positive_feedbacks_count,
                        'negative_feedbacks' => $company->negative_feedbacks_count,
                        'suggestion_feedbacks' => $company->suggestion_feedbacks_count,
                        'total_services' => $company->services_count,
                        'total_employees' => $company->employees_count,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Entreprise non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Statistiques globales de la plateforme (Super Admin)
     */
    public function getGlobalStatistics(Request $request)
    {
        try {
            $totalCompanies = Company::count();
            $totalUsers = User::count();
            $totalFeedbacks = Feedback::count();
            $totalClients = Client::count();

            // Feedbacks par type
            $feedbacksByType = Feedback::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type');

            // Croissance mensuelle
            $currentMonth = Feedback::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            
            $previousMonth = Feedback::whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->count();

            $monthlyGrowth = $previousMonth > 0 
                ? round((($currentMonth - $previousMonth) / $previousMonth) * 100, 1)
                : 0;

            // Top 5 entreprises
            $topCompanies = Company::withCount('feedbacks')
                ->orderBy('feedbacks_count', 'desc')
                ->limit(5)
                ->get();

            // Timeline globale (12 derniers mois)
            $globalTimeline = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthData = Feedback::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type');

                $globalTimeline[] = [
                    'month' => $date->format('M Y'),
                    'positif' => $monthData->get('positif', 0),
                    'negatif' => $monthData->get('negatif', 0),
                    'suggestion' => $monthData->get('suggestion', 0),
                    'total' => $monthData->sum()
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'global_stats' => [
                        'total_companies' => $totalCompanies,
                        'total_users' => $totalUsers,
                        'total_feedbacks' => $totalFeedbacks,
                        'total_clients' => $totalClients,
                        'monthly_growth' => $monthlyGrowth
                    ],
                    'feedbacks_by_type' => [
                        'positif' => $feedbacksByType->get('positif', 0),
                        'negatif' => $feedbacksByType->get('negatif', 0),
                        'suggestion' => $feedbacksByType->get('suggestion', 0)
                    ],
                    'top_companies' => $topCompanies,
                    'timeline' => $globalTimeline
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques globales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard global Super Admin
     */
    public function getSuperAdminDashboard(Request $request)
    {
        try {
            // Récupérer les stats globales
            $globalStats = $this->getGlobalStatistics($request);
            $globalData = $globalStats->getData()->data;

            // Entreprises récemment créées
            $recentCompanies = Company::with('manager')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Feedbacks récents
            $recentFeedbacks = Feedback::with(['company', 'client'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'global_stats' => $globalData->global_stats,
                    'feedbacks_by_type' => $globalData->feedbacks_by_type,
                    'recent_companies' => $recentCompanies,
                    'recent_feedbacks' => $recentFeedbacks,
                    'timeline' => array_slice($globalData->timeline, -6) // 6 derniers mois
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le filtre de date selon la période
     */
    private function getDateFilter($period)
    {
        return match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => null, // Tous les temps
        };
    }

    /**
     * Calculer la note moyenne pour une entreprise avec filtre de date
     */
    private function getAverageRating($company, $dateFilter)
    {
        $query = $company->feedbacks();
        if ($dateFilter) {
            $query->where('created_at', '>=', $dateFilter);
        }
        return round($query->avg('rating') ?? 0, 1);
    }
}