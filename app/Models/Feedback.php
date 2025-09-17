<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Feedback extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the prefixed ID for display purposes.
     */
    public function getDisplayIdAttribute()
    {
        return 'FBK-' . $this->id;
    }

    protected $table = 'feedbacks';

    protected $fillable = [
        'company_id',
        'client_id',
        'employee_id',
        'service_id',
        'feedback_type_id',
        'feedback_status_id',
        'status_id', // Nouvelle colonne pour les statuts
        'type', // Garde pour compatibilité temporaire
        'status', // Garde pour compatibilité temporaire
        'title',
        'description',
        'rating',
        'kalipoints',
        'bonus_kalipoints',
        'positive_kalipoints',
        'negative_kalipoints',
        'suggestion_kalipoints',
        'positive_bonus_kalipoints',
        'negative_bonus_kalipoints',
        'suggestion_bonus_kalipoints',
        'attachment_url',
        'audio_url',
        'video_url',
        'media_type',
        'sentiment_id',
        'validation_token',
        'validation_expires_at',
        'client_validated',
        'client_validation_status',
        'client_validation_comment',
        'client_satisfaction_rating',
        'validation_reminded_at',
        'admin_comments',
        'admin_resolution_description',
        'treated_by_user_id',
        'treated_at',
        'resolved_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'kalipoints' => 'integer',
        'bonus_kalipoints' => 'integer',
        'positive_kalipoints' => 'integer',
        'negative_kalipoints' => 'integer',
        'suggestion_kalipoints' => 'integer',
        'positive_bonus_kalipoints' => 'integer',
        'negative_bonus_kalipoints' => 'integer',
        'suggestion_bonus_kalipoints' => 'integer',
        'client_validated' => 'boolean',
        'client_satisfaction_rating' => 'integer',
        'validation_expires_at' => 'datetime',
        'validation_reminded_at' => 'datetime',
        'treated_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected $appends = [
        'display_id'
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function treatedByUser()
    {
        return $this->belongsTo(User::class, 'treated_by_user_id');
    }

    public function feedbackAlerts()
    {
        return $this->hasMany(FeedbackAlert::class);
    }

    public function validationLogs()
    {
        return $this->hasMany(ValidationLog::class);
    }

    public function feedbackType()
    {
        return $this->belongsTo(FeedbackType::class);
    }

    public function feedbackStatus()
    {
        return $this->belongsTo(FeedbackStatus::class);
    }

    public function status()
    {
        return $this->belongsTo(FeedbackStatus::class, 'status_id');
    }

    public function escalations()
    {
        return $this->hasMany(Escalation::class);
    }

    public function activeEscalations()
    {
        return $this->hasMany(Escalation::class)->where('is_resolved', false);
    }

    public function sentiment()
    {
        return $this->belongsTo(Sentiment::class);
    }

    // Accessors
    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'appreciation' => '#F97316', // Orange
            'incident' => '#EF4444',     // Rouge
            'suggestion' => '#3B82F6',   // Bleu
            default => '#6B7280'
        };
    }

    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'appreciation' => 'star',
            'incident' => 'exclamation-triangle',
            'suggestion' => 'lightbulb',
            default => 'chat'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'new' => $this->type === 'incident' ? '#FCA5A5' : ($this->type === 'suggestion' ? '#93C5FD' : '#FD9745'),
            'in_progress' => '#F97316', // Orange
            'treated' => '#3B82F6',     // Bleu
            'resolved', 'implemented' => '#10B981', // Vert
            'partially_resolved', 'partially_implemented' => '#F59E0B', // Jaune
            'not_resolved' => '#DC2626', // Rouge foncé
            'rejected' => '#EF4444',     // Rouge
            'archived' => '#6B7280',     // Gris
            default => '#6B7280'
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'new' => 'Nouveau',
            'in_progress' => 'En cours',
            'treated' => 'Traité',
            'resolved' => 'Résolu',
            'partially_resolved' => 'Partiellement résolu',
            'not_resolved' => 'Non résolu',
            'implemented' => 'Implémenté',
            'partially_implemented' => 'Partiellement implémenté',
            'rejected' => 'Rejeté',
            'archived' => 'Archivé',
            default => 'Inconnu'
        };
    }

    public function getReferenceAttribute()
    {
        return 'QW-' . date('Y', strtotime($this->created_at)) . '-' . str_pad($this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    public function getAttachmentUrlFullAttribute()
    {
        return $this->attachment_url ? 
            asset('storage/attachments/' . $this->attachment_url) : 
            null;
    }

    public function getIsValidationExpiredAttribute()
    {
        return $this->validation_expires_at && $this->validation_expires_at->isPast();
    }

    public function getCanBeValidatedAttribute()
    {
        return $this->validation_token && 
               !$this->client_validated && 
               !$this->is_validation_expired &&
               ($this->feedbackType?->requires_validation ?? false) &&
               $this->status === 'treated';
    }

    // Nouveaux accesseurs utilisant FeedbackType
    public function getTypeNameAttribute()
    {
        return $this->feedbackType?->name ?? $this->type;
    }

    public function getTypeLabelAttribute()
    {
        return $this->feedbackType?->label ?? $this->type;
    }

    public function getTypeColorNewAttribute()
    {
        return $this->feedbackType?->color ?? '#6B7280';
    }

    public function getTypeIconNewAttribute()
    {
        return $this->feedbackType?->icon ?? 'chat';
    }

    public function getAvailableSentimentsAttribute()
    {
        return $this->feedbackType?->available_sentiments ?? [];
    }

    public function getRequiresValidationAttribute()
    {
        return $this->feedbackType?->requires_validation ?? false;
    }

    // Nouveaux accesseurs utilisant FeedbackStatus
    public function getStatusNameAttribute()
    {
        return $this->feedbackStatus?->name ?? $this->status;
    }

    public function getStatusLabelNewAttribute()
    {
        return $this->feedbackStatus?->label ?? $this->status;
    }

    public function getStatusColorNewAttribute()
    {
        return $this->feedbackStatus?->color ?? '#6B7280';
    }

    public function getStatusIconNewAttribute()
    {
        return $this->feedbackStatus?->icon ?? 'circle';
    }

    public function getStatusIsFinalAttribute()
    {
        return $this->feedbackStatus?->is_final ?? false;
    }

    public function getStatusRequiresAdminActionAttribute()
    {
        return $this->feedbackStatus?->requires_admin_action ?? false;
    }

    public function getNextPossibleStatusesAttribute()
    {
        return $this->feedbackStatus?->getNextPossibleStatuses() ?? collect();
    }

    // Méthodes pour la validation client
    public function generateValidationToken()
    {
        $this->validation_token = Str::random(64);
        $this->validation_expires_at = now()->addHours(config('app.validation_token_expiry_hours', 48));
        $this->save();

        return $this->validation_token;
    }

    public function getValidationUrl()
    {
        if (!$this->validation_token) {
            return null;
        }

        return config('app.validation_base_url') . '/feedback/' . $this->validation_token;
    }

    public function validateByClient($status, $rating = null, $comment = null, $bonusPoints = 0, $feedbackStatusId = null)
    {
        $this->client_validated = true;
        
        // Mapper les statuts vers les valeurs ENUM correctes
        $validationStatusMapping = [
            'resolved' => 'satisfied',
            'partially_resolved' => 'partially_satisfied',
            'not_resolved' => 'not_satisfied',
        ];
        
        $this->client_validation_status = $validationStatusMapping[$status] ?? 'not_satisfied';
        $this->client_satisfaction_rating = $rating;
        $this->client_validation_comment = $comment;
        $this->bonus_kalipoints = $bonusPoints;

        // Mettre à jour le statut du feedback avec l'ID du FeedbackStatus
        if ($feedbackStatusId) {
            $this->feedback_status_id = $feedbackStatusId;
        }
        
        // Mettre à jour aussi le champ status pour compatibilité
        $this->status = $status;

        // Marquer comme résolu si nécessaire
        if (in_array($status, ['resolved', 'partially_resolved', 'implemented', 'partially_implemented'])) {
            $this->resolved_at = now();
        }

        $this->save();

        // Ajouter les points bonus au client
        if ($bonusPoints > 0 && $this->client) {
            $this->client->addKaliPoints($bonusPoints, true);
        }

        return true;
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePositive($query)
    {
        return $query->where('type', 'appreciation');
    }

    public function scopeNegative($query)
    {
        return $query->where('type', 'incident');
    }

    public function scopeSuggestions($query)
    {
        return $query->where('type', 'suggestion');
    }

    public function scopePendingValidation($query)
    {
        return $query->whereNotNull('validation_token')
                    ->where('client_validated', false)
                    ->where('validation_expires_at', '>', now());
    }

    public function scopeValidationExpired($query)
    {
        return $query->whereNotNull('validation_token')
                    ->where('client_validated', false)
                    ->where('validation_expires_at', '<=', now());
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accesseurs pour KaliPoints par type
    public function getKalipointsForTypeAttribute()
    {
        return match($this->type) {
            'appreciation' => $this->positive_kalipoints,
            'incident' => $this->negative_kalipoints,
            'suggestion' => $this->suggestion_kalipoints,
            default => 0
        };
    }

    public function getBonusKalipointsForTypeAttribute()
    {
        return match($this->type) {
            'appreciation' => $this->positive_bonus_kalipoints,
            'incident' => $this->negative_bonus_kalipoints,
            'suggestion' => $this->suggestion_bonus_kalipoints,
            default => 0
        };
    }

    public function getTotalKalipointsForTypeAttribute()
    {
        return $this->kalipoints_for_type + $this->bonus_kalipoints_for_type;
    }

    // Méthode pour attribuer les KaliPoints selon le type
    public function assignKaliPointsByType($basePoints, $bonusPoints = 0)
    {
        switch ($this->type) {
            case 'appreciation':
                $this->positive_kalipoints = $basePoints;
                $this->positive_bonus_kalipoints = $bonusPoints;
                break;
            case 'incident':
                $this->negative_kalipoints = $basePoints;
                $this->negative_bonus_kalipoints = $bonusPoints;
                break;
            case 'suggestion':
                $this->suggestion_kalipoints = $basePoints;
                $this->suggestion_bonus_kalipoints = $bonusPoints;
                break;
        }

        // Mettre à jour aussi les totaux généraux pour compatibilité
        $this->kalipoints = $basePoints;
        $this->bonus_kalipoints = $bonusPoints;
        
        $this->save();
    }

    // Méthode pour calculer les KaliPoints basé sur la note (1-5)
    public function calculateKaliPointsFromRating()
    {
        if (!$this->rating) return 0;

        $basePoints = match($this->rating) {
            1 => 5,   // Note très faible = peu de points
            2 => 10,  // Note faible
            3 => 15,  // Note moyenne
            4 => 20,  // Bonne note
            5 => 25,  // Excellente note = maximum de points
            default => 10
        };

        // Ajustement selon le type de feedback
        $typeMultiplier = match($this->type) {
            'appreciation' => 1.2,  // Bonus pour les appréciations
            'incident' => 0.8,      // Moins de points pour incidents
            'suggestion' => 1.0,    // Points normaux pour suggestions
            default => 1.0
        };

        return round($basePoints * $typeMultiplier);
    }

    // Accesseurs pour sentiment et média
    public function getSentimentLabelAttribute()
    {
        return match($this->sentiment) {
            // Positifs
            'content' => 'Content',
            'heureux' => 'Heureux',
            'extremement_satisfait' => 'Extrêmement satisfait',
            // Négatifs
            'mecontent' => 'Mécontent',
            'en_colere' => 'En colère',
            'laisse_a_desirer' => 'Laisse à désirer',
            // Suggestions
            'constructif' => 'Constructif',
            'amelioration' => 'Amélioration',
            'proposition' => 'Proposition',
            default => 'Non spécifié'
        };
    }

    public function getSentimentColorAttribute()
    {
        return match($this->sentiment) {
            // Positifs - nuances de vert/orange
            'content' => '#F97316',           // Orange
            'heureux' => '#10B981',           // Vert
            'extremement_satisfait' => '#059669', // Vert foncé
            // Négatifs - nuances de rouge
            'mecontent' => '#F59E0B',         // Jaune-orange (attention)
            'en_colere' => '#DC2626',         // Rouge foncé
            'laisse_a_desirer' => '#EF4444', // Rouge
            // Suggestions - nuances de bleu
            'constructif' => '#3B82F6',       // Bleu
            'amelioration' => '#1D4ED8',      // Bleu foncé
            'proposition' => '#6366F1',       // Indigo
            default => '#6B7280'              // Gris
        };
    }

    public function getMediaIconAttribute()
    {
        return match($this->media_type) {
            'audio' => 'microphone',
            'video' => 'video-camera',
            'mixed' => 'collection',
            'text' => 'document-text',
            default => 'document-text'
        };
    }

    public function getHasMediaAttribute()
    {
        return $this->audio_url || $this->video_url;
    }

    public function getMediaUrlsAttribute()
    {
        $urls = [];
        if ($this->audio_url) $urls['audio'] = asset('storage/media/audio/' . $this->audio_url);
        if ($this->video_url) $urls['video'] = asset('storage/media/video/' . $this->video_url);
        return $urls;
    }

    // Méthodes pour les sentiments par type
    public static function getSentimentsByType($type)
    {
        return match($type) {
            'appreciation' => [
                'content' => 'Content',
                'heureux' => 'Heureux', 
                'extremement_satisfait' => 'Extrêmement satisfait'
            ],
            'incident' => [
                'mecontent' => 'Mécontent',
                'en_colere' => 'En colère',
                'laisse_a_desirer' => 'Laisse à désirer'
            ],
            'suggestion' => [
                'constructif' => 'Constructif',
                'amelioration' => 'Amélioration',
                'proposition' => 'Proposition'
            ],
            default => []
        };
    }

    // Méthodes pour le système d'escalade
    public function getHasActiveEscalationsAttribute()
    {
        return $this->activeEscalations()->exists();
    }

    public function getHighestEscalationLevelAttribute()
    {
        return $this->activeEscalations()->max('escalation_level') ?? 0;
    }

    public function getEscalationStatusAttribute()
    {
        $level = $this->highest_escalation_level;
        
        return match($level) {
            0 => 'normal',
            1 => 'escalated_manager',
            2 => 'escalated_director', 
            3 => 'escalated_ceo',
            default => 'unknown'
        };
    }

    public function getEscalationColorAttribute()
    {
        return match($this->escalation_status) {
            'normal' => '#10B981',           // Vert
            'escalated_manager' => '#F59E0B', // Orange
            'escalated_director' => '#EF4444', // Rouge
            'escalated_ceo' => '#DC2626',     // Rouge foncé
            default => '#6B7280'
        };
    }

    public function getEscalationLabelAttribute()
    {
        return match($this->escalation_status) {
            'normal' => 'Normal',
            'escalated_manager' => 'Escaladé Manager',
            'escalated_director' => 'Escaladé Direction',
            'escalated_ceo' => 'Escaladé PDG',
            default => 'Inconnu'
        };
    }

    public function getSlaRuleAttribute()
    {
        return \App\Models\SlaRule::findApplicableRule($this);
    }

    public function getSlaDeadlinesAttribute()
    {
        $slaRule = $this->sla_rule;
        
        if (!$slaRule) return null;
        
        return $slaRule->calculateDeadlines($this->created_at);
    }

    public function getIsSlaBreachedAttribute()
    {
        $slaRule = $this->sla_rule;
        
        if (!$slaRule) return false;
        
        return $slaRule->isSlaBreached($this->created_at);
    }

    public function getTimeToEscalationAttribute()
    {
        $slaRule = $this->sla_rule;
        
        if (!$slaRule) return null;
        
        $deadlines = $slaRule->calculateDeadlines($this->created_at);
        $now = now();
        
        // Vérifier la prochaine échéance
        foreach (['escalation_1_deadline', 'escalation_2_deadline', 'escalation_3_deadline'] as $deadline) {
            if ($now->lt($deadlines[$deadline])) {
                return $deadlines[$deadline]->diffInMinutes($now);
            }
        }
        
        return 0; // Toutes les escalades ont été dépassées
    }

    public function getPriorityLevelAttribute()
    {
        $slaRule = $this->sla_rule;
        return $slaRule?->priority_level ?? 2; // Normal par défaut
    }

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

    // Méthodes d'action pour les escalades
    public function triggerEscalationCheck()
    {
        $escalationService = app(\App\Services\EscalationService::class);
        return $escalationService->checkFeedbackForEscalation($this);
    }

    public function resolveAllEscalations($notes = null)
    {
        $escalationService = app(\App\Services\EscalationService::class);
        return $escalationService->resolveAllEscalationsForFeedback($this, $notes);
    }

    // Scopes pour les escalades
    public function scopeWithEscalations($query)
    {
        return $query->with(['activeEscalations', 'escalations']);
    }

    public function scopeEscalated($query)
    {
        return $query->whereHas('activeEscalations');
    }

    public function scopeEscalatedLevel($query, $level)
    {
        return $query->whereHas('activeEscalations', function ($q) use ($level) {
            $q->where('escalation_level', $level);
        });
    }

    public function scopeCriticalPriority($query)
    {
        return $query->whereHas('slaRule', function ($q) {
            $q->where('priority_level', '>=', 4);
        });
    }

    public function scopeSlaBreached($query)
    {
        return $query->where(function ($q) {
            $q->whereRaw('
                EXISTS (
                    SELECT 1 FROM sla_rules sr 
                    WHERE sr.feedback_type_id = feedbacks.feedback_type_id 
                    AND sr.company_id IN (feedbacks.company_id, "00000000-0000-0000-0000-000000000000")
                    AND TIMESTAMPDIFF(MINUTE, feedbacks.created_at, NOW()) > sr.first_response_sla
                    AND sr.is_active = 1
                )
            ');
        });
    }
}