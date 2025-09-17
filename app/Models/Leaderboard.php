<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'user_id',
        'service_id',
        'period_type',
        'period_date',
        'metric_type',
        'score',
        'rank_overall',
        'rank_in_service',
        'total_participants',
        'detailed_metrics',
        'improvement_percentage',
        'is_improvement',
        'points_earned',
        'badges_eligible',
        'is_winner',
        'podium_position',
        'is_published',
        'published_at',
        'calculated_at',
    ];

    protected $casts = [
        'period_date' => 'date',
        'score' => 'decimal:2',
        'rank_overall' => 'integer',
        'rank_in_service' => 'integer',
        'total_participants' => 'integer',
        'detailed_metrics' => 'array',
        'improvement_percentage' => 'decimal:2',
        'is_improvement' => 'boolean',
        'points_earned' => 'integer',
        'badges_eligible' => 'array',
        'is_winner' => 'boolean',
        'podium_position' => 'integer',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'calculated_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeByPeriod($query, $periodType, $periodDate)
    {
        return $query->where('period_type', $periodType)
                    ->where('period_date', $periodDate);
    }

    public function scopeByMetric($query, $metricType)
    {
        return $query->where('metric_type', $metricType);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public function scopeWinners($query)
    {
        return $query->where('is_winner', true);
    }

    public function scopePodium($query)
    {
        return $query->whereNotNull('podium_position')
                    ->orderBy('podium_position');
    }

    public function scopeTopPerformers($query, $limit = 10)
    {
        return $query->orderBy('rank_overall')->limit($limit);
    }

    public function scopeCurrentMonth($query)
    {
        return $query->where('period_type', 'monthly')
                    ->where('period_date', now()->startOfMonth());
    }

    public function scopeCurrentWeek($query)
    {
        return $query->where('period_type', 'weekly')
                    ->where('period_date', now()->startOfWeek());
    }

    // Accessors
    public function getPeriodLabelAttribute()
    {
        return match($this->period_type) {
            'daily' => $this->period_date->format('d/m/Y'),
            'weekly' => 'Semaine du ' . $this->period_date->format('d/m/Y'),
            'monthly' => $this->period_date->format('F Y'),
            'yearly' => $this->period_date->format('Y'),
            default => $this->period_date->format('d/m/Y')
        };
    }

    public function getMetricLabelAttribute()
    {
        return match($this->metric_type) {
            'satisfaction_score' => 'Score de satisfaction',
            'total_feedbacks' => 'Total feedbacks',
            'positive_feedbacks' => 'Feedbacks positifs',
            'resolution_time' => 'Temps de résolution',
            'response_time' => 'Temps de réponse',
            'kalipoints_earned' => 'KaliPoints gagnés',
            'badges_earned' => 'Badges obtenus',
            'consistency_score' => 'Score de régularité',
            'overall_performance' => 'Performance globale',
            default => ucfirst(str_replace('_', ' ', $this->metric_type))
        };
    }

    public function getRankDisplayAttribute()
    {
        if ($this->podium_position) {
            return match($this->podium_position) {
                1 => '🥇 1er',
                2 => '🥈 2ème', 
                3 => '🥉 3ème',
                default => "#{$this->rank_overall}"
            };
        }
        
        return "#{$this->rank_overall}";
    }

    public function getPodiumColorAttribute()
    {
        return match($this->podium_position) {
            1 => '#FFD700', // Or
            2 => '#C0C0C0', // Argent
            3 => '#CD7F32', // Bronze
            default => '#6B7280'
        };
    }

    public function getImprovementIconAttribute()
    {
        if ($this->improvement_percentage === null) return null;
        
        if ($this->improvement_percentage > 0) return 'trending-up';
        if ($this->improvement_percentage < 0) return 'trending-down';
        return 'minus';
    }

    public function getImprovementColorAttribute()
    {
        if ($this->improvement_percentage === null) return '#6B7280';
        
        if ($this->improvement_percentage > 0) return '#10B981'; // Vert
        if ($this->improvement_percentage < 0) return '#EF4444'; // Rouge
        return '#6B7280'; // Gris
    }

    public function getScoreFormattedAttribute()
    {
        return match($this->metric_type) {
            'satisfaction_score', 'consistency_score', 'overall_performance' => number_format($this->score, 1) . '%',
            'resolution_time', 'response_time' => number_format($this->score, 1) . 'h',
            'kalipoints_earned', 'badges_earned', 'total_feedbacks', 'positive_feedbacks' => number_format($this->score, 0),
            default => number_format($this->score, 2)
        };
    }

    // Méthodes utilitaires
    public function publish()
    {
        $this->update([
            'is_published' => true,
            'published_at' => now()
        ]);

        // Déclencher événement de publication
        event(new \App\Events\LeaderboardPublished($this));

        return $this;
    }

    public function awardPoints()
    {
        if ($this->points_earned > 0 && !$this->user->hasReceivedLeaderboardPoints($this->id)) {
            $this->user->addKaliPoints(
                $this->points_earned, 
                true, 
                "Classement {$this->rank_display} - {$this->metric_label}"
            );
            
            // Marquer comme récompensé
            $this->user->leaderboardPointsGiven()->attach($this->id);
        }

        return $this;
    }

    // Méthodes statiques
    public static function getCurrentRankings($companyId, $metricType = 'satisfaction_score', $periodType = 'monthly')
    {
        $periodDate = match($periodType) {
            'daily' => today(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            'yearly' => now()->startOfYear(),
            default => now()->startOfMonth()
        };

        return static::byCompany($companyId)
                    ->byPeriod($periodType, $periodDate)
                    ->byMetric($metricType)
                    ->published()
                    ->with(['user', 'service'])
                    ->orderBy('rank_overall')
                    ->get();
    }

    public static function getTopPerformersAllTime($companyId, $limit = 10)
    {
        return static::byCompany($companyId)
                    ->published()
                    ->select('user_id', \DB::raw('COUNT(*) as appearances'), \DB::raw('AVG(rank_overall) as avg_rank'), \DB::raw('SUM(points_earned) as total_points'))
                    ->whereNotNull('podium_position')
                    ->groupBy('user_id')
                    ->orderBy('appearances', 'desc')
                    ->orderBy('avg_rank', 'asc')
                    ->with('user')
                    ->limit($limit)
                    ->get();
    }

    public static function getServiceComparison($companyId, $periodType = 'monthly', $metricType = 'satisfaction_score')
    {
        $periodDate = match($periodType) {
            'monthly' => now()->startOfMonth(),
            'weekly' => now()->startOfWeek(),
            default => now()->startOfMonth()
        };

        return static::byCompany($companyId)
                    ->byPeriod($periodType, $periodDate)
                    ->byMetric($metricType)
                    ->published()
                    ->select('service_id', \DB::raw('AVG(score) as avg_score'), \DB::raw('COUNT(*) as employee_count'))
                    ->whereNotNull('service_id')
                    ->groupBy('service_id')
                    ->with('service')
                    ->orderBy('avg_score', 'desc')
                    ->get();
    }

    public static function getImprovementTrends($companyId, $userId, $metricType = 'satisfaction_score', $months = 6)
    {
        return static::byCompany($companyId)
                    ->where('user_id', $userId)
                    ->where('period_type', 'monthly')
                    ->byMetric($metricType)
                    ->where('period_date', '>=', now()->subMonths($months)->startOfMonth())
                    ->orderBy('period_date')
                    ->get();
    }

    public static function calculateMonthlyRankings($companyId, $month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        $periodDate = \Carbon\Carbon::create($year, $month, 1);

        // Calculer pour chaque métrique
        $metrics = ['satisfaction_score', 'total_feedbacks', 'positive_feedbacks', 'kalipoints_earned'];
        
        foreach ($metrics as $metric) {
            static::calculateRankingForMetric($companyId, 'monthly', $periodDate, $metric);
        }
    }

    private static function calculateRankingForMetric($companyId, $periodType, $periodDate, $metricType)
    {
        // Implementation spécifique selon le type de métrique
        // Ceci serait dans un service séparé pour plus de clarté
    }
}