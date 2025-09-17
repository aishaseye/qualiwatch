<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackAlert extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'feedback_id',
        'severity',
        'alert_type',
        'detected_keywords',
        'sentiment_score',
        'alert_reason',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolution_notes',
        'is_escalated',
        'escalated_at',
    ];

    protected $casts = [
        'detected_keywords' => 'array',
        'sentiment_score' => 'float',
        'is_escalated' => 'boolean',
        'acknowledged_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUnacknowledged($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeCritical($query)
    {
        return $query->whereIn('severity', ['critical', 'catastrophic']);
    }

    public function scopeEscalated($query)
    {
        return $query->where('is_escalated', true);
    }

    // Methods
    public function acknowledge($userId)
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
    }

    public function startProgress()
    {
        $this->update(['status' => 'in_progress']);
    }

    public function resolve($notes = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $notes,
        ]);
    }

    public function dismiss($notes = null)
    {
        $this->update([
            'status' => 'dismissed',
            'resolution_notes' => $notes,
        ]);
    }

    public function escalate()
    {
        $this->update([
            'is_escalated' => true,
            'escalated_at' => now(),
        ]);
    }

    public function getSeverityLevelAttribute()
    {
        $levels = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
            'catastrophic' => 5,
        ];

        return $levels[$this->severity] ?? 1;
    }

    public function getIsHighPriorityAttribute()
    {
        return in_array($this->severity, ['critical', 'catastrophic']);
    }

    public function getFormattedDetectedKeywordsAttribute()
    {
        return $this->detected_keywords ? implode(', ', $this->detected_keywords) : '';
    }
}