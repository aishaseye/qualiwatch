<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escalation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'feedback_id',
        'sla_rule_id',
        'escalation_level',
        'trigger_reason',
        'escalated_at',
        'notified_at',
        'notified_users',
        'notification_channels_used',
        'is_resolved',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'escalation_level' => 'integer',
        'escalated_at' => 'datetime',
        'notified_at' => 'datetime',
        'notified_users' => 'array',
        'notification_channels_used' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // Relations
    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }

    public function slaRule()
    {
        return $this->belongsTo(SlaRule::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('escalation_level', $level);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('escalated_at', '>=', now()->subHours($hours));
    }

    // Accessors
    public function getLevelLabelAttribute()
    {
        return match($this->escalation_level) {
            1 => 'Niveau 1 - Manager',
            2 => 'Niveau 2 - Direction',
            3 => 'Niveau 3 - PDG',
            default => 'Niveau ' . $this->escalation_level
        };
    }

    public function getLevelColorAttribute()
    {
        return match($this->escalation_level) {
            1 => '#F59E0B', // Orange
            2 => '#EF4444', // Rouge
            3 => '#DC2626', // Rouge foncé
            default => '#6B7280'
        };
    }

    public function getTriggerReasonLabelAttribute()
    {
        return match($this->trigger_reason) {
            'sla_breach' => 'Dépassement SLA',
            'critical_rating' => 'Note critique',
            'multiple_incidents' => 'Incidents multiples',
            'urgent_sentiment' => 'Sentiment urgent',
            default => ucfirst(str_replace('_', ' ', $this->trigger_reason))
        };
    }

    public function getEscalationAgeAttribute()
    {
        return $this->escalated_at->diffForHumans();
    }

    public function getResolutionTimeAttribute()
    {
        if (!$this->resolved_at) return null;
        
        return $this->escalated_at->diffInMinutes($this->resolved_at);
    }

    public function getResolutionTimeHoursAttribute()
    {
        $minutes = $this->resolution_time;
        return $minutes ? round($minutes / 60, 1) : null;
    }

    // Méthodes d'action
    public function resolve($notes = null)
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);

        return $this;
    }

    public function markAsNotified($userIds, $channels)
    {
        $this->update([
            'notified_at' => now(),
            'notified_users' => array_unique(array_merge($this->notified_users ?? [], $userIds)),
            'notification_channels_used' => array_unique(array_merge($this->notification_channels_used ?? [], $channels))
        ]);

        return $this;
    }

    // Méthodes statiques
    public static function createForFeedback(Feedback $feedback, SlaRule $slaRule, $level, $reason)
    {
        return static::create([
            'feedback_id' => $feedback->id,
            'sla_rule_id' => $slaRule->id,
            'escalation_level' => $level,
            'trigger_reason' => $reason,
            'escalated_at' => now(),
        ]);
    }

    public static function getActiveEscalationsCount()
    {
        return static::active()->count();
    }

    public static function getCriticalEscalationsCount()
    {
        return static::active()->byLevel(3)->count();
    }

    public static function getEscalationsByCompany($companyId)
    {
        return static::whereHas('feedback', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        });
    }

    public static function getRecentEscalations($hours = 24)
    {
        return static::recent($hours)->with(['feedback', 'slaRule'])->get();
    }
}