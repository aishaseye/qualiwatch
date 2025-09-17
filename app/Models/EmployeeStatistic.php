<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeStatistic extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'service_id',
        'employee_id',
        'period_type',
        'period_date',
        'total_feedbacks',
        'positive_feedbacks',
        'negative_feedbacks',
        'suggestions_received',
        'satisfaction_score',
        'positive_feedback_percentage',
        'negative_feedback_percentage',
        'suggestions_percentage',
        'incident_resolution_percentage',
        'performance_score',
        'rank_in_service',
        'rank_in_company',
        'vs_service_average',
        'vs_company_average',
        'growth_rate',
        'total_kalipoints_generated',
        'average_kalipoints_per_feedback',
        'positive_kalipoints',
        'negative_kalipoints',
        'suggestion_kalipoints',
        'positive_bonus_kalipoints',
        'negative_bonus_kalipoints',
        'suggestion_bonus_kalipoints',
        'avg_positive_kalipoints',
        'avg_negative_kalipoints',
        'avg_suggestion_kalipoints',
        'incidents_assigned',
        'incidents_resolved',
        'suggestions_about_employee',
        'average_response_time_hours',
        'average_resolution_time_hours',
        'badges_earned',
        'employee_of_period',
        'consistency_score',
        'improvement_trend',
        'strengths',
        'areas_for_improvement',
        'validations_related',
        'validation_satisfaction_avg',
        'training_recommendations',
        'recognition_suggestions',
        'calculated_at',
    ];

    protected $casts = [
        'period_date' => 'date',
        'satisfaction_score' => 'decimal:2',
        'positive_feedback_percentage' => 'decimal:2',
        'negative_feedback_percentage' => 'decimal:2',
        'suggestions_percentage' => 'decimal:2',
        'incident_resolution_percentage' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'vs_service_average' => 'decimal:2',
        'vs_company_average' => 'decimal:2',
        'growth_rate' => 'decimal:2',
        'average_kalipoints_per_feedback' => 'decimal:2',
        'avg_positive_kalipoints' => 'decimal:2',
        'avg_negative_kalipoints' => 'decimal:2',
        'avg_suggestion_kalipoints' => 'decimal:2',
        'average_response_time_hours' => 'decimal:2',
        'average_resolution_time_hours' => 'decimal:2',
        'badges_earned' => 'array',
        'employee_of_period' => 'boolean',
        'consistency_score' => 'decimal:2',
        'improvement_trend' => 'decimal:2',
        'strengths' => 'array',
        'areas_for_improvement' => 'array',
        'validation_satisfaction_avg' => 'decimal:2',
        'training_recommendations' => 'array',
        'recognition_suggestions' => 'array',
        'calculated_at' => 'datetime',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Scopes
    public function scopeForPeriod($query, $periodType, $date)
    {
        return $query->where('period_type', $periodType)->where('period_date', $date);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeTopPerformers($query, $limit = 10)
    {
        return $query->orderBy('performance_score', 'desc')->limit($limit);
    }

    public function scopeEmployeeOfPeriod($query)
    {
        return $query->where('employee_of_period', true);
    }

    public function scopeNeedsImprovement($query)
    {
        return $query->where('performance_score', '<', 60);
    }

    // Accessors
    public function getFormattedPeriodAttribute()
    {
        return match($this->period_type) {
            'daily' => $this->period_date->format('d/m/Y'),
            'weekly' => 'Semaine du ' . $this->period_date->format('d/m/Y'),
            'monthly' => $this->period_date->format('F Y'),
            'yearly' => $this->period_date->format('Y'),
            default => $this->period_date->format('d/m/Y')
        };
    }

    public function getPerformanceLevelAttribute()
    {
        return match(true) {
            $this->performance_score >= 90 => 'excellent',
            $this->performance_score >= 75 => 'good',
            $this->performance_score >= 60 => 'average',
            $this->performance_score >= 40 => 'needs_improvement',
            default => 'critical'
        };
    }

    public function getTrendStatusAttribute()
    {
        return match(true) {
            $this->improvement_trend > 10 => 'improving_fast',
            $this->improvement_trend > 0 => 'improving',
            $this->improvement_trend > -5 => 'stable',
            $this->improvement_trend > -15 => 'declining',
            default => 'critical_decline'
        };
    }

    public function getBadgeCountAttribute()
    {
        return is_array($this->badges_earned) ? count($this->badges_earned) : 0;
    }

    public function getHasRecognitionAttribute()
    {
        return $this->employee_of_period || $this->badge_count > 0;
    }

    public function getConsistencyLevelAttribute()
    {
        return match(true) {
            $this->consistency_score >= 80 => 'very_consistent',
            $this->consistency_score >= 60 => 'consistent',
            $this->consistency_score >= 40 => 'somewhat_consistent',
            default => 'inconsistent'
        };
    }

    public function getRecommendationCountAttribute()
    {
        $training = is_array($this->training_recommendations) ? count($this->training_recommendations) : 0;
        $recognition = is_array($this->recognition_suggestions) ? count($this->recognition_suggestions) : 0;
        return $training + $recognition;
    }

    public function getOverallRatingAttribute()
    {
        // Note globale basée sur plusieurs critères
        $performanceWeight = 0.4;
        $consistencyWeight = 0.2;
        $improvementWeight = 0.2;
        $validationWeight = 0.2;

        $performanceScore = $this->performance_score;
        $consistencyScore = $this->consistency_score;
        $improvementScore = max(0, min(100, 50 + $this->improvement_trend * 2));
        $validationScore = $this->validation_satisfaction_avg * 20; // Convert 5-star to 100-scale

        return round(
            ($performanceScore * $performanceWeight) +
            ($consistencyScore * $consistencyWeight) +
            ($improvementScore * $improvementWeight) +
            ($validationScore * $validationWeight),
            1
        );
    }
}