<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Service;
use App\Models\Employee;
use App\Models\Feedback;
use App\Models\Client;
use App\Models\CompanyStatistic;
use App\Models\ServiceStatistic;
use App\Models\EmployeeStatistic;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticsCalculatorService
{
    /**
     * Calculer toutes les statistiques pour une période donnée
     */
    public function calculateStatisticsForPeriod($periodType = 'daily', $date = null)
    {
        $date = $date ? Carbon::parse($date) : now();
        
        // Calculer pour toutes les entreprises
        Company::chunk(100, function ($companies) use ($periodType, $date) {
            foreach ($companies as $company) {
                $this->calculateCompanyStatistics($company, $periodType, $date);
                $this->calculateServicesStatistics($company, $periodType, $date);
                $this->calculateEmployeesStatistics($company, $periodType, $date);
            }
        });
    }

    /**
     * Calculer les statistiques d'une entreprise
     */
    public function calculateCompanyStatistics(Company $company, $periodType = 'daily', $date = null)
    {
        $date = $date ? Carbon::parse($date) : now();
        $periodStart = $this->getPeriodStart($periodType, $date);
        $periodEnd = $this->getPeriodEnd($periodType, $date);
        
        // Supprimer les anciennes stats pour recalculer
        CompanyStatistic::where('company_id', $company->id)
            ->where('period_type', $periodType)
            ->where('period_date', $date->toDateString())
            ->delete();

        // Feedbacks de la période
        $feedbacks = $company->feedbacks()
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->get();

        $totalFeedbacks = $feedbacks->count();
        $positiveFeedbacks = $feedbacks->where('type', 'appreciation')->count();
        $negativeFeedbacks = $feedbacks->where('type', 'incident')->count();
        $suggestions = $feedbacks->where('type', 'suggestion')->count();

        // Période précédente pour comparaison
        $previousPeriodStart = $this->getPreviousPeriodStart($periodType, $date);
        $previousPeriodEnd = $this->getPreviousPeriodEnd($periodType, $date);
        $previousFeedbacks = $company->feedbacks()
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        $growthRate = $previousFeedbacks > 0 
            ? round((($totalFeedbacks - $previousFeedbacks) / $previousFeedbacks) * 100, 2)
            : 0;

        // Clients
        $clients = Client::join('feedbacks', 'clients.id', '=', 'feedbacks.client_id')
            ->where('feedbacks.company_id', $company->id)
            ->whereBetween('feedbacks.created_at', [$periodStart, $periodEnd])
            ->select('clients.*')
            ->distinct()
            ->get();

        $totalClients = $clients->count();
        $newClients = $clients->where('first_feedback_at', '>=', $periodStart)->count();
        $recurringClients = $clients->where('total_feedbacks', '>', 1)->count();

        // KaliPoints généraux (compatibilité)
        $totalKalipoints = $feedbacks->sum('kalipoints');
        $bonusKalipoints = $feedbacks->sum('bonus_kalipoints');
        $avgKalipoints = $totalFeedbacks > 0 ? round($totalKalipoints / $totalFeedbacks, 2) : 0;

        // KaliPoints par type de feedback
        $positiveKalipoints = $feedbacks->where('type', 'appreciation')->sum('positive_kalipoints');
        $negativeKalipoints = $feedbacks->where('type', 'incident')->sum('negative_kalipoints');
        $suggestionKalipoints = $feedbacks->where('type', 'suggestion')->sum('suggestion_kalipoints');
        
        $positiveBonusKalipoints = $feedbacks->where('type', 'appreciation')->sum('positive_bonus_kalipoints');
        $negativeBonusKalipoints = $feedbacks->where('type', 'incident')->sum('negative_bonus_kalipoints');
        $suggestionBonusKalipoints = $feedbacks->where('type', 'suggestion')->sum('suggestion_bonus_kalipoints');

        // Moyennes par type
        $avgPositiveKalipoints = $positiveFeedbacks > 0 ? round($positiveKalipoints / $positiveFeedbacks, 2) : 0;
        $avgNegativeKalipoints = $negativeFeedbacks > 0 ? round($negativeKalipoints / $negativeFeedbacks, 2) : 0;
        $avgSuggestionKalipoints = $suggestions > 0 ? round($suggestionKalipoints / $suggestions, 2) : 0;

        // Validation
        $validationsSent = $feedbacks->whereNotNull('validation_token')->count();
        $validationsCompleted = $feedbacks->where('client_validated', true)->count();
        $validationRate = $validationsSent > 0 ? round(($validationsCompleted / $validationsSent) * 100, 2) : 0;
        $avgSatisfactionRating = $feedbacks->where('client_validated', true)->avg('client_satisfaction_rating') ?? 0;

        // Temps de traitement
        $treatedFeedbacks = $feedbacks->whereNotNull('treated_at');
        $avgResponseTime = 0;
        $avgResolutionTime = 0;

        if ($treatedFeedbacks->count() > 0) {
            $responseTimes = $treatedFeedbacks->map(function ($feedback) {
                return $feedback->created_at->diffInHours($feedback->treated_at);
            });
            $avgResponseTime = round($responseTimes->avg(), 2);

            $resolvedFeedbacks = $treatedFeedbacks->whereNotNull('resolved_at');
            if ($resolvedFeedbacks->count() > 0) {
                $resolutionTimes = $resolvedFeedbacks->map(function ($feedback) {
                    return $feedback->created_at->diffInHours($feedback->resolved_at);
                });
                $avgResolutionTime = round($resolutionTimes->avg(), 2);
            }
        }

        // Heures de pointe
        $peakHours = $feedbacks->groupBy(function ($feedback) {
            return $feedback->created_at->format('H');
        })->map->count()->toArray();

        $peakDays = $feedbacks->groupBy(function ($feedback) {
            return strtolower($feedback->created_at->format('l'));
        })->map->count()->toArray();

        // Taux de résolution/implémentation
        $resolvedIncidents = $feedbacks->where('type', 'incident')
            ->whereIn('status', ['resolved', 'partially_resolved'])->count();
        $totalIncidents = $negativeFeedbacks;
        $resolutionRate = $totalIncidents > 0 ? round(($resolvedIncidents / $totalIncidents) * 100, 2) : 0;

        $implementedSuggestions = $feedbacks->where('type', 'suggestion')
            ->whereIn('status', ['implemented', 'partially_implemented'])->count();
        $totalSuggestions = $suggestions;
        $implementationRate = $totalSuggestions > 0 ? round(($implementedSuggestions / $totalSuggestions) * 100, 2) : 0;

        // Calculer tous les pourcentages
        $satisfactionScore = $totalFeedbacks > 0 ? round(($positiveFeedbacks / $totalFeedbacks) * 100, 2) : 0;
        $positiveFeedbackPercentage = $totalFeedbacks > 0 ? round(($positiveFeedbacks / $totalFeedbacks) * 100, 2) : 0;
        $negativeFeedbackPercentage = $totalFeedbacks > 0 ? round(($negativeFeedbacks / $totalFeedbacks) * 100, 2) : 0;
        $suggestionsPercentage = $totalFeedbacks > 0 ? round(($suggestions / $totalFeedbacks) * 100, 2) : 0;
        $incidentResolutionPercentage = $negativeFeedbacks > 0 ? round(($resolvedIncidents / $negativeFeedbacks) * 100, 2) : 0;
        $suggestionImplementationPercentage = $suggestions > 0 ? round(($implementedSuggestions / $suggestions) * 100, 2) : 0;

        // Sauvegarder les statistiques
        CompanyStatistic::create([
            'company_id' => $company->id,
            'period_type' => $periodType,
            'period_date' => $date->toDateString(),
            'total_feedbacks' => $totalFeedbacks,
            'new_feedbacks' => $totalFeedbacks, // Nouveaux pour cette période
            'positive_feedbacks' => $positiveFeedbacks,
            'negative_feedbacks' => $negativeFeedbacks,
            'suggestions_count' => $suggestions,
            'satisfaction_score' => $satisfactionScore,
            'positive_feedback_percentage' => $positiveFeedbackPercentage,
            'negative_feedback_percentage' => $negativeFeedbackPercentage,
            'suggestions_percentage' => $suggestionsPercentage,
            'growth_rate' => $growthRate,
            'resolution_rate' => $resolutionRate,
            'implementation_rate' => $implementationRate,
            'incident_resolution_percentage' => $incidentResolutionPercentage,
            'suggestion_implementation_percentage' => $suggestionImplementationPercentage,
            'total_clients' => $totalClients,
            'new_clients' => $newClients,
            'recurring_clients' => $recurringClients,
            'client_retention_rate' => $totalClients > 0 ? round(($recurringClients / $totalClients) * 100, 2) : 0,
            'average_feedbacks_per_client' => $totalClients > 0 ? round($totalFeedbacks / $totalClients, 2) : 0,
            'total_kalipoints_distributed' => $totalKalipoints,
            'bonus_kalipoints_distributed' => $bonusKalipoints,
            'average_kalipoints_per_feedback' => $avgKalipoints,
            'positive_kalipoints' => $positiveKalipoints,
            'negative_kalipoints' => $negativeKalipoints,
            'suggestion_kalipoints' => $suggestionKalipoints,
            'positive_bonus_kalipoints' => $positiveBonusKalipoints,
            'negative_bonus_kalipoints' => $negativeBonusKalipoints,
            'suggestion_bonus_kalipoints' => $suggestionBonusKalipoints,
            'avg_positive_kalipoints' => $avgPositiveKalipoints,
            'avg_negative_kalipoints' => $avgNegativeKalipoints,
            'avg_suggestion_kalipoints' => $avgSuggestionKalipoints,
            'validation_links_sent' => $validationsSent,
            'validations_completed' => $validationsCompleted,
            'validation_completion_rate' => $validationRate,
            'average_satisfaction_rating' => round($avgSatisfactionRating, 2),
            'average_response_time_hours' => $avgResponseTime,
            'average_resolution_time_hours' => $avgResolutionTime,
            'peak_hours' => $peakHours,
            'peak_days' => $peakDays,
            'calculated_at' => now(),
        ]);
    }

    /**
     * Calculer les statistiques des services
     */
    public function calculateServicesStatistics(Company $company, $periodType = 'daily', $date = null)
    {
        $date = $date ? Carbon::parse($date) : now();
        $periodStart = $this->getPeriodStart($periodType, $date);
        $periodEnd = $this->getPeriodEnd($periodType, $date);

        foreach ($company->services as $service) {
            // Supprimer les anciennes stats
            ServiceStatistic::where('service_id', $service->id)
                ->where('period_type', $periodType)
                ->where('period_date', $date->toDateString())
                ->delete();

            $feedbacks = $service->feedbacks()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->get();

            if ($feedbacks->isEmpty()) continue;

            $totalFeedbacks = $feedbacks->count();
            $positiveFeedbacks = $feedbacks->where('type', 'positif')->count();
            $negativeFeedbacks = $feedbacks->where('type', 'negatif')->count();
            $suggestions = $feedbacks->where('type', 'suggestion')->count();

            // Calculer TOUS les pourcentages pour ce service
            $satisfactionScore = $totalFeedbacks > 0 ? round(($positiveFeedbacks / $totalFeedbacks) * 100, 2) : 0;
            $positiveFeedbackPercentage = $totalFeedbacks > 0 ? round(($positiveFeedbacks / $totalFeedbacks) * 100, 2) : 0;
            $negativeFeedbackPercentage = $totalFeedbacks > 0 ? round(($negativeFeedbacks / $totalFeedbacks) * 100, 2) : 0;
            $suggestionsPercentage = $totalFeedbacks > 0 ? round(($suggestions / $totalFeedbacks) * 100, 2) : 0;

            $performanceScore = $this->calculateServicePerformanceScore($feedbacks, $satisfactionScore);

            // Meilleur employé du service pour cette période
            $topEmployeeId = $this->getTopEmployeeForService($service, $periodStart, $periodEnd);

            // Validation stats
            $validationsSent = $feedbacks->whereNotNull('validation_token')->count();
            $validationsCompleted = $feedbacks->where('client_validated', true)->count();
            $validationRate = $validationsSent > 0 ? round(($validationsCompleted / $validationsSent) * 100, 2) : 0;

            // Résolutions avec nombres ET pourcentages
            $incidentsResolved = $feedbacks->where('type', 'negatif')->whereIn('status', ['resolved', 'partially_resolved'])->count();
            $suggestionsImplemented = $feedbacks->where('type', 'suggestion')->whereIn('status', ['implemented', 'partially_implemented'])->count();
            $resolutionRate = $negativeFeedbacks > 0 ? round(($incidentsResolved / $negativeFeedbacks) * 100, 2) : 0;

            ServiceStatistic::create([
                'company_id' => $company->id,
                'service_id' => $service->id,
                'period_type' => $periodType,
                'period_date' => $date->toDateString(),
                // NOMBRES ABSOLUS
                'total_feedbacks' => $totalFeedbacks,
                'positive_feedbacks' => $positiveFeedbacks,
                'negative_feedbacks' => $negativeFeedbacks,
                'suggestions_count' => $suggestions,
                // POURCENTAGES
                'satisfaction_score' => $satisfactionScore,
                'positive_feedback_percentage' => $positiveFeedbackPercentage,
                'negative_feedback_percentage' => $negativeFeedbackPercentage,
                'suggestions_percentage' => $suggestionsPercentage,
                'performance_score' => $performanceScore,
                'rank_in_company' => 0, // Sera calculé après
                'vs_company_average' => 0, // Sera calculé après
                'growth_rate' => 0, // Calculé avec période précédente
                'total_kalipoints_generated' => $feedbacks->sum('kalipoints'),
                'average_kalipoints' => $feedbacks->avg('kalipoints') ?? 0,
                'incidents_resolved' => $feedbacks->where('type', 'incident')->whereIn('status', ['resolved', 'partially_resolved'])->count(),
                'suggestions_implemented' => $feedbacks->where('type', 'suggestion')->whereIn('status', ['implemented', 'partially_implemented'])->count(),
                'resolution_rate' => $negativeFeedbacks > 0 ? round(($feedbacks->where('type', 'incident')->whereIn('status', ['resolved', 'partially_resolved'])->count() / $negativeFeedbacks) * 100, 2) : 0,
                'average_response_time_hours' => 0, // À calculer
                'average_resolution_time_hours' => 0, // À calculer
                'active_employees_count' => $service->employees()->where('is_active', true)->count(),
                'average_feedbacks_per_employee' => 0, // À calculer
                'top_employee_id' => $topEmployeeId,
                'validations_sent' => $validationsSent,
                'validations_completed' => $validationsCompleted,
                'validation_rate' => $validationRate,
                'average_validation_rating' => $feedbacks->where('client_validated', true)->avg('client_satisfaction_rating') ?? 0,
                'calculated_at' => now(),
            ]);
        }
    }

    /**
     * Calculer les statistiques des employés
     */
    public function calculateEmployeesStatistics(Company $company, $periodType = 'daily', $date = null)
    {
        $date = $date ? Carbon::parse($date) : now();
        $periodStart = $this->getPeriodStart($periodType, $date);
        $periodEnd = $this->getPeriodEnd($periodType, $date);

        foreach ($company->employees as $employee) {
            // Supprimer les anciennes stats
            EmployeeStatistic::where('employee_id', $employee->id)
                ->where('period_type', $periodType)
                ->where('period_date', $date->toDateString())
                ->delete();

            $feedbacks = $employee->feedbacks()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->get();

            // if ($feedbacks->isEmpty()) continue; // Commenter pour créer des stats même sans feedbacks

            $totalFeedbacks = $feedbacks->count();
            $positiveFeedbacks = $feedbacks->where('type', 'positif')->count();
            $negativeFeedbacks = $feedbacks->where('type', 'negatif')->count();
            $suggestions = $feedbacks->where('type', 'suggestion')->count();

            // Calculer TOUS les pourcentages pour cet employé
            $satisfactionScore = $totalFeedbacks > 0 ? round(($positiveFeedbacks / $totalFeedbacks) * 100, 2) : 0;
            $positiveFeedbackPercentage = $totalFeedbacks > 0 ? round(($positiveFeedbacks / $totalFeedbacks) * 100, 2) : 0;
            $negativeFeedbackPercentage = $totalFeedbacks > 0 ? round(($negativeFeedbacks / $totalFeedbacks) * 100, 2) : 0;
            $suggestionsPercentage = $totalFeedbacks > 0 ? round(($suggestions / $totalFeedbacks) * 100, 2) : 0;
            
            // Pourcentage de résolution des incidents assignés à cet employé
            $incidentsResolved = $feedbacks->where('type', 'negatif')->whereIn('status', ['resolved', 'partially_resolved'])->count();
            $incidentResolutionPercentage = $negativeFeedbacks > 0 ? round(($incidentsResolved / $negativeFeedbacks) * 100, 2) : 0;
            
            $performanceScore = $this->calculateEmployeePerformanceScore($feedbacks, $satisfactionScore);

            // Badges et reconnaissance
            $badges = $this->calculateEmployeeBadges($employee, $feedbacks, $performanceScore);
            $isEmployeeOfPeriod = $this->isEmployeeOfPeriod($employee, $company, $periodType, $date);

            // Recommandations
            $trainingRecommendations = $this->generateTrainingRecommendations($employee, $feedbacks);
            $recognitionSuggestions = $this->generateRecognitionSuggestions($employee, $performanceScore, $badges);

            EmployeeStatistic::create([
                'company_id' => $company->id,
                'service_id' => $employee->service_id,
                'employee_id' => $employee->id,
                'period_type' => $periodType,
                'period_date' => $date->toDateString(),
                // NOMBRES ABSOLUS
                'total_feedbacks' => $totalFeedbacks,
                'positive_feedbacks' => $positiveFeedbacks,
                'negative_feedbacks' => $negativeFeedbacks,
                'suggestions_received' => $suggestions,
                // POURCENTAGES
                'satisfaction_score' => $satisfactionScore,
                'positive_feedback_percentage' => $positiveFeedbackPercentage,
                'negative_feedback_percentage' => $negativeFeedbackPercentage,
                'suggestions_percentage' => $suggestionsPercentage,
                'incident_resolution_percentage' => $incidentResolutionPercentage,
                'performance_score' => $performanceScore,
                'rank_in_service' => 0, // Sera calculé après
                'rank_in_company' => 0, // Sera calculé après
                'vs_service_average' => 0, // Sera calculé après
                'vs_company_average' => 0, // Sera calculé après
                'growth_rate' => 0, // Calculé avec période précédente
                'total_kalipoints_generated' => $feedbacks->sum('kalipoints'),
                'average_kalipoints_per_feedback' => $feedbacks->avg('kalipoints') ?? 0,
                'incidents_assigned' => $negativeFeedbacks,
                'incidents_resolved' => $feedbacks->where('type', 'incident')->whereIn('status', ['resolved', 'partially_resolved'])->count(),
                'suggestions_about_employee' => $suggestions,
                'average_response_time_hours' => 0, // À calculer
                'average_resolution_time_hours' => 0, // À calculer
                'badges_earned' => $badges,
                'employee_of_period' => $isEmployeeOfPeriod,
                'consistency_score' => $this->calculateConsistencyScore($employee, $feedbacks),
                'improvement_trend' => 0, // Calculé avec historique
                'strengths' => $this->identifyStrengths($feedbacks),
                'areas_for_improvement' => $this->identifyAreasForImprovement($feedbacks),
                'validations_related' => $feedbacks->whereNotNull('validation_token')->count(),
                'validation_satisfaction_avg' => $feedbacks->where('client_validated', true)->avg('client_satisfaction_rating') ?? 0,
                'training_recommendations' => $trainingRecommendations,
                'recognition_suggestions' => $recognitionSuggestions,
                'calculated_at' => now(),
            ]);
        }
    }

    // Méthodes utilitaires pour les calculs

    private function getPeriodStart($periodType, $date)
    {
        return match($periodType) {
            'daily' => $date->copy()->startOfDay(),
            'weekly' => $date->copy()->startOfWeek(),
            'monthly' => $date->copy()->startOfMonth(),
            'yearly' => $date->copy()->startOfYear(),
            default => $date->copy()->startOfDay()
        };
    }

    private function getPeriodEnd($periodType, $date)
    {
        return match($periodType) {
            'daily' => $date->copy()->endOfDay(),
            'weekly' => $date->copy()->endOfWeek(),
            'monthly' => $date->copy()->endOfMonth(),
            'yearly' => $date->copy()->endOfYear(),
            default => $date->copy()->endOfDay()
        };
    }

    private function getPreviousPeriodStart($periodType, $date)
    {
        return match($periodType) {
            'daily' => $date->copy()->subDay()->startOfDay(),
            'weekly' => $date->copy()->subWeek()->startOfWeek(),
            'monthly' => $date->copy()->subMonth()->startOfMonth(),
            'yearly' => $date->copy()->subYear()->startOfYear(),
            default => $date->copy()->subDay()->startOfDay()
        };
    }

    private function getPreviousPeriodEnd($periodType, $date)
    {
        return match($periodType) {
            'daily' => $date->copy()->subDay()->endOfDay(),
            'weekly' => $date->copy()->subWeek()->endOfWeek(),
            'monthly' => $date->copy()->subMonth()->endOfMonth(),
            'yearly' => $date->copy()->subYear()->endOfYear(),
            default => $date->copy()->subDay()->endOfDay()
        };
    }

    private function calculateServicePerformanceScore($feedbacks, $satisfactionScore)
    {
        // Algorithme de calcul du score de performance du service
        $baseScore = $satisfactionScore;
        
        // Bonus pour résolution rapide
        $avgResolutionBonus = 0; // À implémenter
        
        // Malus pour incidents non résolus
        $unresolvedPenalty = 0; // À implémenter
        
        return max(0, min(100, $baseScore + $avgResolutionBonus - $unresolvedPenalty));
    }

    private function calculateEmployeePerformanceScore($feedbacks, $satisfactionScore)
    {
        // Algorithme similaire pour les employés
        return $satisfactionScore; // Simplifié pour l'exemple
    }

    private function getTopEmployeeForService($service, $periodStart, $periodEnd)
    {
        return $service->employees()
            ->withCount(['feedbacks' => function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('created_at', [$periodStart, $periodEnd])
                      ->where('type', 'appreciation');
            }])
            ->orderBy('feedbacks_count', 'desc')
            ->first()?->id;
    }

    private function calculateEmployeeBadges($employee, $feedbacks, $performanceScore)
    {
        $badges = [];
        
        if ($performanceScore >= 95) $badges[] = 'top_performer';
        if ($feedbacks->where('type', 'appreciation')->count() >= 10) $badges[] = 'client_favorite';
        if ($feedbacks->where('type', 'incident')->where('status', 'resolved')->count() >= 5) $badges[] = 'problem_solver';
        
        return $badges;
    }

    private function isEmployeeOfPeriod($employee, $company, $periodType, $date)
    {
        // Logique pour déterminer l'employé de la période
        return false; // Simplifié
    }

    private function calculateConsistencyScore($employee, $feedbacks)
    {
        // Calcul de la régularité des performances
        return 75; // Simplifié
    }

    private function identifyStrengths($feedbacks)
    {
        $strengths = [];
        
        $positiveCount = $feedbacks->where('type', 'appreciation')->count();
        $totalCount = $feedbacks->count();
        
        if ($totalCount > 0 && ($positiveCount / $totalCount) > 0.8) {
            $strengths[] = 'client_satisfaction';
        }
        
        return $strengths;
    }

    private function identifyAreasForImprovement($feedbacks)
    {
        $areas = [];
        
        $negativeCount = $feedbacks->where('type', 'incident')->count();
        $totalCount = $feedbacks->count();
        
        if ($totalCount > 0 && ($negativeCount / $totalCount) > 0.2) {
            $areas[] = 'incident_reduction';
        }
        
        return $areas;
    }

    private function generateTrainingRecommendations($employee, $feedbacks)
    {
        $recommendations = [];
        
        $incidents = $feedbacks->where('type', 'incident');
        if ($incidents->count() > 3) {
            $recommendations[] = 'customer_service_training';
        }
        
        return $recommendations;
    }

    private function generateRecognitionSuggestions($employee, $performanceScore, $badges)
    {
        $suggestions = [];
        
        if ($performanceScore >= 90) {
            $suggestions[] = 'public_recognition';
        }
        
        if (count($badges) >= 2) {
            $suggestions[] = 'employee_spotlight';
        }
        
        return $suggestions;
    }
}