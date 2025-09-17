<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceStatistic extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'service_id',
        'period_type',
        'period_date',
        'total_feedbacks',
        'positive_feedbacks',
        'negative_feedbacks',
        'suggestions_count',
        'satisfaction_score',
        'positive_feedback_percentage',
        'negative_feedback_percentage',
        'suggestions_percentage',
        'performance_score',
        'rank_in_company',
        'vs_company_average',
        'growth_rate',
        'total_kalipoints_generated',
        'average_kalipoints',
        'positive_kalipoints',
        'negative_kalipoints',
        'suggestion_kalipoints',
        'positive_bonus_kalipoints',
        'negative_bonus_kalipoints',
        'suggestion_bonus_kalipoints',
        'avg_positive_kalipoints',
        'avg_negative_kalipoints',
        'avg_suggestion_kalipoints',
        'incidents_resolved',
        'suggestions_implemented',
        'resolution_rate',
        'average_response_time_hours',
        'average_resolution_time_hours',
        'active_employees_count',
        'average_feedbacks_per_employee',
        'top_employee_id',
        'validations_sent',
        'validations_completed',
        'validation_rate',
        'average_validation_rating',
        'calculated_at',
    ];

    protected $casts = [
        'period_date' => 'date',
        'satisfaction_score' => 'decimal:2',
        'positive_feedback_percentage' => 'decimal:2',
        'negative_feedback_percentage' => 'decimal:2',
        'suggestions_percentage' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'vs_company_average' => 'decimal:2',
        'growth_rate' => 'decimal:2',
        'average_kalipoints' => 'decimal:2',
        'avg_positive_kalipoints' => 'decimal:2',
        'avg_negative_kalipoints' => 'decimal:2',
        'avg_suggestion_kalipoints' => 'decimal:2',
        'resolution_rate' => 'decimal:2',
        'average_response_time_hours' => 'decimal:2',
        'average_resolution_time_hours' => 'decimal:2',
        'average_feedbacks_per_employee' => 'decimal:2',
        'validation_rate' => 'decimal:2',
        'average_validation_rating' => 'decimal:2',
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

    public function topEmployee()
    {
        return $this->belongsTo(Employee::class, 'top_employee_id');
    }

    // Scopes
    public function scopeForPeriod($query, $periodType, $date)
    {
        return $query->where('period_type', $periodType)->where('period_date', $date);
    }

    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public function scopeTopPerformers($query, $limit = 5)
    {
        return $query->orderBy('performance_score', 'desc')->limit($limit);
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

    public function getComparisonStatusAttribute()
    {
        if ($this->vs_company_average > 10) return 'excellent';
        if ($this->vs_company_average > 0) return 'good';
        if ($this->vs_company_average > -10) return 'average';
        return 'needs_improvement';
    }

    public function getGrowthTrendAttribute()
    {
        if ($this->growth_rate > 10) return 'high_growth';
        if ($this->growth_rate > 0) return 'growing';
        if ($this->growth_rate > -10) return 'stable';
        return 'declining';
    }

    public function getEfficiencyScoreAttribute()
    {
        // Score basé sur résolution et temps de réponse
        $resolutionScore = $this->resolution_rate;
        $speedScore = $this->average_response_time_hours > 0 
            ? max(0, 100 - ($this->average_response_time_hours * 2)) 
            : 0;
        
        return round(($resolutionScore + $speedScore) / 2, 2);
    }
}