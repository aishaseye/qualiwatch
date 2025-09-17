<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Feedback;
use App\Models\Client;
use App\Models\Service;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Dashboard principal - Vue d'ensemble de l'entreprise
     */
    public function overview(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut spécifier une entreprise avec ?company_id=xxx
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if ($companyId) {
                    $company = Company::findOrFail($companyId);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Super admin doit spécifier company_id en paramètre'
                    ], 400);
                }
            } else {
                $company = $user->company;
            }

            // Filtre par période (week, month, year) - défaut: pas de filtre (tous les temps)
            $period = $request->get('period'); // week, month, year
            $dateFilter = $this->getDateFilter($period);

            // KPIs principaux avec filtre de période
            $baseQuery = $company->feedbacks();
            if ($dateFilter) {
                $baseQuery->where('created_at', '>=', $dateFilter);
            }
            
            $totalFeedbacks = $baseQuery->count();
            $positiveFeedbacks = (clone $baseQuery)->where('type', 'positif')->count();
            $satisfactionScore = $totalFeedbacks > 0 ? round(($positiveFeedbacks / $totalFeedbacks) * 100, 1) : 0;
            
            // Feedbacks nouvellement créés (pour cette période)
            $newFeedbacks = $totalFeedbacks; // Dans la période sélectionnée, tous sont "nouveaux"
            
            // Pourcentage de feedbacks traités
            $treatedFeedbacks = (clone $baseQuery)->where('status', 'treated')->count();
            $treatedPercentage = $totalFeedbacks > 0 ? round(($treatedFeedbacks / $totalFeedbacks) * 100, 1) : 0;
            
            // Croissance mensuelle
            $currentMonth = $company->feedbacks()->whereMonth('created_at', now()->month)->count();
            $lastMonth = $company->feedbacks()->whereMonth('created_at', now()->subMonth()->month)->count();
            $monthlyGrowth = $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;

            // Statistiques par type avec filtre de période
            $feedbacksByTypeQuery = $company->feedbacks();
            if ($dateFilter) {
                $feedbacksByTypeQuery->where('created_at', '>=', $dateFilter);
            }
            $feedbacksByType = $feedbacksByTypeQuery
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get()
                ->keyBy('type');

            // Evolution temporelle (derniers 6 mois)
            $timeline = $this->getFeedbacksTimeline($company, 6);

            // Top 5 services
            $topServices = $company->services()
                ->withCount('feedbacks')
                ->orderBy('feedbacks_count', 'desc')
                ->limit(5)
                ->get();

            // Statistiques clients
            $totalClients = $company->feedbacks()->distinct('client_id')->count('client_id');
            $recurrentClients = $company->feedbacks()
                ->select('client_id')
                ->groupBy('client_id')
                ->havingRaw('count(*) > 1')
                ->get()
                ->count();

            // Validations en attente
            $pendingValidations = $company->feedbacks()
                ->whereNotNull('validation_token')
                ->where('client_validated', false)
                ->where('validation_expires_at', '>', now())
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => [
                        'total_feedbacks' => $totalFeedbacks,
                        'new_feedbacks' => $newFeedbacks,
                        'new_feedbacks_percentage' => $totalFeedbacks > 0 ? 100.0 : 0, // Dans la période, tous sont nouveaux
                        'treated_feedbacks' => $treatedFeedbacks,
                        'treated_percentage' => $treatedPercentage,
                        'satisfaction_score' => $satisfactionScore,
                        'monthly_growth' => $monthlyGrowth,
                        'period_filter' => $period ?? 'all',
                    ],
                    'feedback_stats' => [
                        'positif' => [
                            'count' => $feedbacksByType->get('positif')?->count ?? 0,
                            'percentage' => $totalFeedbacks > 0 ? round(($feedbacksByType->get('positif')?->count ?? 0) / $totalFeedbacks * 100, 1) : 0,
                            'average_rating' => round($this->getFilteredQuery($company->feedbacks()->where('type', 'positif'), $dateFilter)->avg('rating') ?? 0, 1),
                        ],
                        'negatif' => [
                            'count' => $feedbacksByType->get('negatif')?->count ?? 0,
                            'percentage' => $totalFeedbacks > 0 ? round(($feedbacksByType->get('negatif')?->count ?? 0) / $totalFeedbacks * 100, 1) : 0,
                            'average_rating' => round($this->getFilteredQuery($company->feedbacks()->where('type', 'negatif'), $dateFilter)->avg('rating') ?? 0, 1),
                            // Compteurs par statut avec filtre de période
                            'new_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'negatif')->where('status', 'new'), $dateFilter)->count(),
                            'seen_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'negatif')->where('status', 'seen'), $dateFilter)->count(),
                            'in_progress_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'negatif')->where('status', 'in_progress'), $dateFilter)->count(),
                            'treated_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'negatif')->where('status', 'treated'), $dateFilter)->count(),
                            'resolved_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'negatif')->where('status', 'resolved'), $dateFilter)->count(),
                            'partially_resolved_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'negatif')->where('status', 'partially_resolved'), $dateFilter)->count(),
                            'not_resolved_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'negatif')->where('status', 'not_resolved'), $dateFilter)->count(),
                            // Moyennes ratings par statut avec filtre de période
                            'avg_rating_resolved' => round($this->getFilteredQuery($company->feedbacks()->where('type', 'negatif')->where('status', 'resolved'), $dateFilter)->avg('rating') ?? 0, 1),
                            'avg_rating_partial' => round($this->getFilteredQuery($company->feedbacks()->where('type', 'negatif')->where('status', 'partially_resolved'), $dateFilter)->avg('rating') ?? 0, 1),
                            'avg_rating_not_resolved' => round($this->getFilteredQuery($company->feedbacks()->where('type', 'negatif')->where('status', 'not_resolved'), $dateFilter)->avg('rating') ?? 0, 1),
                        ],
                        'suggestion' => [
                            'count' => $feedbacksByType->get('suggestion')?->count ?? 0,
                            'percentage' => $totalFeedbacks > 0 ? round(($feedbacksByType->get('suggestion')?->count ?? 0) / $totalFeedbacks * 100, 1) : 0,
                            'average_rating' => round($this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion'), $dateFilter)->avg('rating') ?? 0, 1),
                            // Compteurs par statut avec filtre de période
                            'new_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion')->where('status', 'new'), $dateFilter)->count(),
                            'seen_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion')->where('status', 'seen'), $dateFilter)->count(),
                            'in_progress_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion')->where('status', 'in_progress'), $dateFilter)->count(),
                            'treated_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion')->where('status', 'treated'), $dateFilter)->count(),
                            'implemented_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion')->where('status', 'implemented'), $dateFilter)->count(),
                            'partially_implemented_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion')->where('status', 'partially_implemented'), $dateFilter)->count(),
                            'rejected_count' => $this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion')->where('status', 'rejected'), $dateFilter)->count(),
                            // Moyennes ratings par statut avec filtre de période
                            'avg_rating_implemented' => round($this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion')->where('status', 'implemented'), $dateFilter)->avg('rating') ?? 0, 1),
                            'avg_rating_partial' => round($this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion')->where('status', 'partially_implemented'), $dateFilter)->avg('rating') ?? 0, 1),
                            'avg_rating_rejected' => round($this->getFilteredQuery($company->feedbacks()->where('type', 'suggestion')->where('status', 'rejected'), $dateFilter)->avg('rating') ?? 0, 1),
                        ],
                    ],
                    'timeline' => $timeline,
                    'top_services' => $topServices,
                    'client_stats' => [
                        'total_clients' => $totalClients,
                        'recurrent_clients' => $recurrentClients,
                        'retention_rate' => $totalClients > 0 ? round(($recurrentClients / $totalClients) * 100, 1) : 0,
                    ],
                    'validation_stats' => [
                        'pending_count' => $pendingValidations,
                        'expiring_soon' => $company->feedbacks()
                            ->whereNotNull('validation_token')
                            ->where('client_validated', false)
                            ->where('validation_expires_at', '>', now())
                            ->where('validation_expires_at', '<=', now()->addHours(6))
                            ->count(),
                    ]
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
     * Statistiques détaillées par service
     */
    public function serviceStats(Request $request, $serviceId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut analyser n'importe quel service
            if ($user->role === 'super_admin') {
                $service = Service::with(['employees', 'feedbacks', 'company'])->findOrFail($serviceId);
                $company = $service->company;
            } else {
                $company = $user->company;
                $service = $company->services()->with(['employees', 'feedbacks'])->findOrFail($serviceId);
            }

            // KPIs du service
            $totalFeedbacks = $service->feedbacks()->count();
            $positiveFeedbacks = $service->feedbacks()->where('type', 'appreciation')->count();
            $negativeFeedbacks = $service->feedbacks()->where('type', 'incident')->count();
            $suggestions = $service->feedbacks()->where('type', 'suggestion')->count();

            // Évolution mensuelle du service
            $serviceTimeline = $this->getFeedbacksTimelineForService($service, 6);

            // Performance des employés du service
            $employeeStats = $service->employees()
                ->withCount(['feedbacks as total_feedbacks'])
                ->withCount(['feedbacks as positive_feedbacks' => function ($query) {
                    $query->where('type', 'positif');
                }])
                ->withCount(['feedbacks as negative_feedbacks' => function ($query) {
                    $query->where('type', 'negatif');
                }])
                ->get()
                ->map(function ($employee) {
                    $employee->performance_score = $employee->total_feedbacks > 0 
                        ? round((($employee->positive_feedbacks - $employee->negative_feedbacks) / $employee->total_feedbacks + 1) * 50, 1)
                        : 0;
                    return $employee;
                });

            // Comparaison avec les autres services
            $serviceComparison = $company->services()
                ->withCount('feedbacks')
                ->withCount(['feedbacks as positive_count' => function ($query) {
                    $query->where('type', 'positif');
                }])
                ->get()
                ->map(function ($s) {
                    $s->satisfaction_score = $s->feedbacks_count > 0 
                        ? round(($s->positive_count / $s->feedbacks_count) * 100, 1) 
                        : 0;
                    return $s;
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'service' => $service,
                    'stats' => [
                        'total_feedbacks' => $totalFeedbacks,
                        'positive_feedbacks' => $positiveFeedbacks,
                        'negative_feedbacks' => $negativeFeedbacks,
                        'suggestions' => $suggestions,
                        'satisfaction_score' => $totalFeedbacks > 0 ? round(($positiveFeedbacks / $totalFeedbacks) * 100, 1) : 0,
                        'average_kalipoints' => $service->feedbacks()->avg('kalipoints') ?? 0,
                        'quality_percentages' => [
                            'positif' => [
                                'count' => $positiveFeedbacks,
                                'percentage' => $totalFeedbacks > 0 ? round(($positiveFeedbacks / $totalFeedbacks) * 100, 1) : 0,
                            ],
                            'negatif' => [
                                'count' => $negativeFeedbacks,
                                'percentage' => $totalFeedbacks > 0 ? round(($negativeFeedbacks / $totalFeedbacks) * 100, 1) : 0,
                            ],
                            'suggestion' => [
                                'count' => $suggestions,
                                'percentage' => $totalFeedbacks > 0 ? round(($suggestions / $totalFeedbacks) * 100, 1) : 0,
                            ],
                        ],
                    ],
                    'timeline' => $serviceTimeline,
                    'employees' => $employeeStats,
                    'comparison' => $serviceComparison,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Profil détaillé d'un employé
     */
    public function employeeProfile(Request $request, $employeeId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut analyser n'importe quel employé
            if ($user->role === 'super_admin') {
                $employee = Employee::with(['service', 'feedbacks.client', 'company'])
                    ->withCount(['feedbacks as total_feedbacks'])
                    ->withCount(['feedbacks as positive_feedbacks' => function ($query) {
                        $query->where('type', 'appreciation');
                    }])
                    ->withCount(['feedbacks as negative_feedbacks' => function ($query) {
                        $query->where('type', 'incident');
                    }])
                    ->withCount(['feedbacks as suggestions_count' => function ($query) {
                        $query->where('type', 'suggestion');
                    }])
                    ->findOrFail($employeeId);
            } else {
                $company = $user->company;
                $employee = $company->employees()
                    ->with(['service', 'feedbacks.client'])
                    ->withCount(['feedbacks as total_feedbacks'])
                    ->withCount(['feedbacks as positive_feedbacks' => function ($query) {
                        $query->where('type', 'appreciation');
                    }])
                    ->withCount(['feedbacks as negative_feedbacks' => function ($query) {
                        $query->where('type', 'incident');
                    }])
                    ->withCount(['feedbacks as suggestions_count' => function ($query) {
                        $query->where('type', 'suggestion');
                    }])
                    ->findOrFail($employeeId);
            }

            // Évolution des feedbacks de l'employé sur 6 mois
            $employeeTimeline = $this->getFeedbacksTimelineForEmployee($employee, 6);

            // Derniers feedbacks
            $recentFeedbacks = $employee->feedbacks()
                ->with(['client'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Moyennes KaliPoints par type
            $kaliPointsStats = $employee->feedbacks()
                ->select('type', DB::raw('AVG(kalipoints) as avg_points'), DB::raw('SUM(kalipoints) as total_points'))
                ->groupBy('type')
                ->get();

            // Comparaison avec la moyenne du service
            $serviceAverage = null;
            if ($employee->service) {
                $serviceAverage = $employee->service->employees()
                    ->withCount('feedbacks')
                    ->get()
                    ->avg('feedbacks_count');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee,
                    'stats' => [
                        'total_feedbacks' => $employee->total_feedbacks,
                        'positive_feedbacks' => $employee->positive_feedbacks,
                        'negative_feedbacks' => $employee->negative_feedbacks,
                        'suggestions_count' => $employee->suggestions_count,
                        'performance_score' => $employee->total_feedbacks > 0 
                            ? round((($employee->positive_feedbacks - $employee->negative_feedbacks) / $employee->total_feedbacks + 1) * 50, 1)
                            : 0,
                        'service_average' => $serviceAverage,
                        'quality_percentages' => [
                            'positif' => [
                                'count' => $employee->positive_feedbacks,
                                'percentage' => $employee->total_feedbacks > 0 ? round(($employee->positive_feedbacks / $employee->total_feedbacks) * 100, 1) : 0,
                            ],
                            'negatif' => [
                                'count' => $employee->negative_feedbacks,
                                'percentage' => $employee->total_feedbacks > 0 ? round(($employee->negative_feedbacks / $employee->total_feedbacks) * 100, 1) : 0,
                            ],
                            'suggestion' => [
                                'count' => $employee->suggestions_count,
                                'percentage' => $employee->total_feedbacks > 0 ? round(($employee->suggestions_count / $employee->total_feedbacks) * 100, 1) : 0,
                            ],
                        ],
                    ],
                    'timeline' => $employeeTimeline,
                    'recent_feedbacks' => $recentFeedbacks,
                    'kalipoints_stats' => $kaliPointsStats,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Statistiques détaillées des clients
     */
    public function clientsStats(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut spécifier une entreprise avec ?company_id=xxx
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if ($companyId) {
                    $company = Company::findOrFail($companyId);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Super admin doit spécifier company_id en paramètre'
                    ], 400);
                }
            } else {
                $company = $user->company;
            }

            // Clients les plus actifs
            $topClients = Client::select('clients.*')
                ->join('feedbacks', 'clients.id', '=', 'feedbacks.client_id')
                ->where('feedbacks.company_id', $company->id)
                ->groupBy('clients.id')
                ->orderBy('total_feedbacks', 'desc')
                ->limit(10)
                ->get();

            // Évolution nouveaux vs récurrents
            $clientEvolution = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthYear = $date->format('Y-m');
                
                $newClients = Client::join('feedbacks', 'clients.id', '=', 'feedbacks.client_id')
                    ->where('feedbacks.company_id', $company->id)
                    ->whereYear('clients.first_feedback_at', $date->year)
                    ->whereMonth('clients.first_feedback_at', $date->month)
                    ->distinct('clients.id')
                    ->count();

                $recurrentClients = Client::join('feedbacks', 'clients.id', '=', 'feedbacks.client_id')
                    ->where('feedbacks.company_id', $company->id)
                    ->where('clients.total_feedbacks', '>', 1)
                    ->whereYear('feedbacks.created_at', $date->year)
                    ->whereMonth('feedbacks.created_at', $date->month)
                    ->distinct('clients.id')
                    ->count();

                $clientEvolution[] = [
                    'month' => $date->format('M Y'),
                    'new_clients' => $newClients,
                    'recurrent_clients' => $recurrentClients,
                ];
            }

            // Statistiques générales
            $totalClients = $company->feedbacks()->distinct('client_id')->count('client_id');
            $vipClients = Client::join('feedbacks', 'clients.id', '=', 'feedbacks.client_id')
                ->where('feedbacks.company_id', $company->id)
                ->where('clients.status', 'vip')
                ->distinct('clients.id')
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'total_clients' => $totalClients,
                        'vip_clients' => $vipClients,
                        'average_feedbacks_per_client' => $totalClients > 0 ? round($company->total_feedbacks / $totalClients, 1) : 0,
                    ],
                    'top_clients' => $topClients,
                    'client_evolution' => $clientEvolution,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Évolution temporelle des feedbacks
     */
    private function getFeedbacksTimeline($company, $months = 6)
    {
        $timeline = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthYear = $date->format('Y-m');
            
            $counts = $company->feedbacks()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get()
                ->keyBy('type');

            $timeline[] = [
                'month' => $date->format('M Y'),
                'positif' => $counts->get('positif')?->count ?? 0,
                'negatif' => $counts->get('negatif')?->count ?? 0,
                'suggestion' => $counts->get('suggestion')?->count ?? 0,
            ];
        }

        return $timeline;
    }

    /**
     * Évolution temporelle des feedbacks pour un service
     */
    private function getFeedbacksTimelineForService($service, $months = 6)
    {
        $timeline = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            
            $counts = $service->feedbacks()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get()
                ->keyBy('type');

            $timeline[] = [
                'month' => $date->format('M Y'),
                'positif' => $counts->get('positif')?->count ?? 0,
                'negatif' => $counts->get('negatif')?->count ?? 0,
                'suggestion' => $counts->get('suggestion')?->count ?? 0,
            ];
        }

        return $timeline;
    }

    /**
     * Évolution temporelle des feedbacks pour un employé
     */
    private function getFeedbacksTimelineForEmployee($employee, $months = 6)
    {
        $timeline = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            
            $counts = $employee->feedbacks()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get()
                ->keyBy('type');

            $timeline[] = [
                'month' => $date->format('M Y'),
                'positif' => $counts->get('positif')?->count ?? 0,
                'negatif' => $counts->get('negatif')?->count ?? 0,
                'suggestion' => $counts->get('suggestion')?->count ?? 0,
            ];
        }

        return $timeline;
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
     * Appliquer le filtre de date à une requête
     */
    private function getFilteredQuery($query, $dateFilter)
    {
        if ($dateFilter) {
            return $query->where('created_at', '>=', $dateFilter);
        }
        return $query;
    }
}