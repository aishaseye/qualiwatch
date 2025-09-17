<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyStatistic extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'period_type',
        'period_date',
        'total_feedbacks',
        'new_feedbacks',
        'positive_feedbacks',
        'negative_feedbacks',
        'suggestions_count',
        'satisfaction_score',
        'positive_feedback_percentage',
        'negative_feedback_percentage',
        'suggestions_percentage',
        'growth_rate',
        'resolution_rate',
        'implementation_rate',
        'incident_resolution_percentage',
        'suggestion_implementation_percentage',
        'total_clients',
        'new_clients',
        'recurring_clients',
        'client_retention_rate',
        'average_feedbacks_per_client',
        'total_kalipoints_distributed',
        'bonus_kalipoints_distributed',
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
        'validation_links_sent',
        'validations_completed',
        'validation_completion_rate',
        'average_satisfaction_rating',
        'average_response_time_hours',
        'average_resolution_time_hours',
        'peak_hours',
        'peak_days',
        'calculated_at',
    ];

    protected $casts = [
        'period_date' => 'date',
        'satisfaction_score' => 'decimal:2',
        'growth_rate' => 'decimal:2',
        'resolution_rate' => 'decimal:2',
        'implementation_rate' => 'decimal:2',
        'client_retention_rate' => 'decimal:2',
        'average_feedbacks_per_client' => 'decimal:2',
        'average_kalipoints_per_feedback' => 'decimal:2',
        'avg_positive_kalipoints' => 'decimal:2',
        'avg_negative_kalipoints' => 'decimal:2',
        'avg_suggestion_kalipoints' => 'decimal:2',
        'validation_completion_rate' => 'decimal:2',
        'average_satisfaction_rating' => 'decimal:2',
        'average_response_time_hours' => 'decimal:2',
        'average_resolution_time_hours' => 'decimal:2',
        'peak_hours' => 'array',
        'peak_days' => 'array',
        'calculated_at' => 'datetime',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeForPeriod($query, $periodType, $date)
    {
        return $query->where('period_type', $periodType)->where('period_date', $date);
    }

    public function scopeDaily($query)
    {
        return $query->where('period_type', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('period_type', 'weekly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('period_type', 'yearly');
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

    public function getTotalKalipointsAttribute()
    {
        return $this->total_kalipoints_distributed + $this->bonus_kalipoints_distributed;
    }

    public function getResolutionScoreAttribute()
    {
        if ($this->negative_feedbacks == 0) return 100;
        return round(($this->resolution_rate * $this->implementation_rate) / 2, 2);
    }
}