<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaRule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'feedback_type_id',
        'name',
        'description',
        'conditions',
        'priority_level',
        'first_response_sla',
        'resolution_sla',
        'escalation_level_1',
        'escalation_level_2',
        'escalation_level_3',
        'level_1_recipients',
        'level_2_recipients',
        'level_3_recipients',
        'notification_channels',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'conditions' => 'array',
        'priority_level' => 'integer',
        'first_response_sla' => 'integer',
        'resolution_sla' => 'integer',
        'escalation_level_1' => 'integer',
        'escalation_level_2' => 'integer',
        'escalation_level_3' => 'integer',
        'level_1_recipients' => 'array',
        'level_2_recipients' => 'array',
        'level_3_recipients' => 'array',
        'notification_channels' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function feedbackType()
    {
        return $this->belongsTo(FeedbackType::class);
    }

    public function escalations()
    {
        return $this->hasMany(Escalation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhere('company_id', '00000000-0000-0000-0000-000000000000'); // Templates globaux
        });
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority_level', $priority);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority_level', 'desc')->orderBy('sort_order');
    }

    // Méthodes utilitaires
    public function getPriorityLabelAttribute()
    {
        return match($this->priority_level) {
            1 => 'Faible',
            2 => 'Normal',
            3 => 'Élevé',
            4 => 'Critique',
            5 => 'Urgence',
            default => 'Normal'
        };
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priority_level) {
            1 => '#10B981', // Vert
            2 => '#3B82F6', // Bleu
            3 => '#F59E0B', // Orange
            4 => '#EF4444', // Rouge
            5 => '#DC2626', // Rouge foncé
            default => '#6B7280'
        };
    }

    public function getFirstResponseSlaHoursAttribute()
    {
        return round($this->first_response_sla / 60, 1);
    }

    public function getResolutionSlaHoursAttribute()
    {
        return round($this->resolution_sla / 60, 1);
    }

    // Vérifier si cette règle s'applique à un feedback
    public function appliesTo(Feedback $feedback)
    {
        // Vérifier le type de feedback
        if ($this->feedback_type_id !== $feedback->feedback_type_id) {
            return false;
        }

        // Vérifier les conditions
        if (!$this->conditions) return true;

        foreach ($this->conditions as $field => $condition) {
            switch ($field) {
                case 'rating':
                    if (!$this->checkRatingCondition($feedback->rating, $condition)) {
                        return false;
                    }
                    break;
                
                case 'sentiment':
                    if ($feedback->sentiment !== $condition) {
                        return false;
                    }
                    break;
                
            }
        }

        return true;
    }

    private function checkRatingCondition($rating, $condition)
    {
        if (is_string($condition) && str_contains($condition, '<=')) {
            $threshold = (int) str_replace('<=', '', $condition);
            return $rating <= $threshold;
        }

        if (is_string($condition) && str_contains($condition, '>=')) {
            $threshold = (int) str_replace('>=', '', $condition);
            return $rating >= $threshold;
        }

        if (is_string($condition) && str_contains($condition, '<')) {
            $threshold = (int) str_replace('<', '', $condition);
            return $rating < $threshold;
        }

        if (is_string($condition) && str_contains($condition, '>')) {
            $threshold = (int) str_replace('>', '', $condition);
            return $rating > $threshold;
        }

        return $rating == $condition;
    }

    // Trouver la règle SLA applicable pour un feedback
    public static function findApplicableRule(Feedback $feedback)
    {
        return static::forCompany($feedback->company_id)
                    ->active()
                    ->ordered()
                    ->get()
                    ->first(function ($rule) use ($feedback) {
                        return $rule->appliesTo($feedback);
                    });
    }

    // Calculer les échéances SLA
    public function calculateDeadlines($feedbackCreatedAt)
    {
        $createdAt = is_string($feedbackCreatedAt) ? 
            \Carbon\Carbon::parse($feedbackCreatedAt) : 
            $feedbackCreatedAt;

        return [
            'first_response_deadline' => $createdAt->copy()->addMinutes($this->first_response_sla),
            'resolution_deadline' => $createdAt->copy()->addMinutes($this->resolution_sla),
            'escalation_1_deadline' => $createdAt->copy()->addMinutes($this->escalation_level_1),
            'escalation_2_deadline' => $createdAt->copy()->addMinutes($this->escalation_level_2),
            'escalation_3_deadline' => $createdAt->copy()->addMinutes($this->escalation_level_3),
        ];
    }

    // Vérifier si SLA est dépassé
    public function isSlaBreached($feedbackCreatedAt, $currentTime = null)
    {
        $currentTime = $currentTime ?: now();
        $deadlines = $this->calculateDeadlines($feedbackCreatedAt);
        
        return $currentTime->gt($deadlines['first_response_deadline']);
    }

    // Vérifier quel niveau d'escalade doit être déclenché
    public function getEscalationLevel($feedbackCreatedAt, $currentTime = null)
    {
        $currentTime = $currentTime ?: now();
        $deadlines = $this->calculateDeadlines($feedbackCreatedAt);
        
        if ($currentTime->gt($deadlines['escalation_3_deadline'])) return 3;
        if ($currentTime->gt($deadlines['escalation_2_deadline'])) return 2;
        if ($currentTime->gt($deadlines['escalation_1_deadline'])) return 1;
        
        return 0; // Pas d'escalade nécessaire
    }
}