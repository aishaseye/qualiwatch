<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Challenge extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'name',
        'title',
        'description',
        'icon',
        'color',
        'type',
        'category',
        'objectives',
        'target_value',
        'target_unit',
        'start_date',
        'end_date',
        'duration_type',
        'reward_points',
        'reward_badges',
        'reward_description',
        'max_participants',
        'current_participants',
        'participant_criteria',
        'status',
        'is_featured',
        'auto_enroll',
        'created_by',
        'requires_approval',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'objectives' => 'array',
        'target_value' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'reward_points' => 'integer',
        'reward_badges' => 'array',
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'participant_criteria' => 'array',
        'is_featured' => 'boolean',
        'auto_enroll' => 'boolean',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'user_challenges')
                    ->withPivot([
                        'joined_at', 'is_active', 'current_value', 'progress_percentage',
                        'progress_data', 'last_updated_at', 'is_completed', 'completed_at',
                        'completion_rank', 'points_earned', 'rewards_earned', 'is_winner',
                        'final_rank', 'final_score'
                    ])
                    ->withTimestamps();
    }

    public function userChallenges()
    {
        return $this->hasMany(UserChallenge::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->whereRaw('(max_participants IS NULL OR current_participants < max_participants)');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed')
                    ->orWhere('end_date', '<', now());
    }

    // Accessors
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'individual' => 'Individuel',
            'team' => 'Équipe',
            'company' => 'Entreprise',
            default => 'Inconnu'
        };
    }

    public function getCategoryLabelAttribute()
    {
        return match($this->category) {
            'performance' => 'Performance',
            'satisfaction' => 'Satisfaction',
            'speed' => 'Rapidité',
            'consistency' => 'Régularité',
            'collaboration' => 'Collaboration',
            default => 'Général'
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'active' => 'Actif',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            default => 'Inconnu'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'draft' => '#6B7280',
            'active' => '#10B981',
            'completed' => '#3B82F6',
            'cancelled' => '#EF4444',
            default => '#6B7280'
        };
    }

    public function getDurationLabelAttribute()
    {
        return match($this->duration_type) {
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire', 
            'monthly' => 'Mensuel',
            'custom' => 'Personnalisé',
            default => 'Personnalisé'
        };
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->status !== 'active') return 0;
        
        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getIsExpiredAttribute()
    {
        return now()->isAfter($this->end_date);
    }

    public function getCanJoinAttribute()
    {
        return $this->status === 'active' &&
               !$this->is_expired &&
               ($this->max_participants === null || $this->current_participants < $this->max_participants);
    }

    public function getCompletionRateAttribute()
    {
        if ($this->current_participants === 0) return 0;

        $completed = $this->userChallenges()->where('is_completed', true)->count();
        return round(($completed / $this->current_participants) * 100, 1);
    }

    public function getTargetFormattedAttribute()
    {
        return number_format($this->target_value) . ' ' . $this->target_unit;
    }

    public function getRewardSummaryAttribute()
    {
        $rewards = [];
        
        if ($this->reward_points > 0) {
            $rewards[] = $this->reward_points . ' KaliPoints';
        }
        
        if ($this->reward_badges) {
            $rewards[] = count($this->reward_badges) . ' badge(s)';
        }
        
        return implode(' + ', $rewards) ?: 'Aucune récompense';
    }

    // Méthodes utilitaires
    public function userCanJoin(User $user)
    {
        // Vérifier si le challenge est disponible
        if (!$this->can_join) return false;

        // Vérifier si l'utilisateur a déjà rejoint
        if ($this->participants()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Vérifier les critères de participation
        if ($this->participant_criteria) {
            return $this->checkParticipantCriteria($user);
        }

        return true;
    }

    private function checkParticipantCriteria(User $user)
    {
        $criteria = $this->participant_criteria;

        // Vérifier le service si spécifié
        if (isset($criteria['service_ids']) && 
            !in_array($user->employee?->service_id, $criteria['service_ids'])) {
            return false;
        }

        // Vérifier l'ancienneté minimale
        if (isset($criteria['min_tenure_months'])) {
            $tenureMonths = $user->created_at->diffInMonths(now());
            if ($tenureMonths < $criteria['min_tenure_months']) {
                return false;
            }
        }

        // Vérifier le niveau minimum
        if (isset($criteria['min_level']) && 
            $user->level < $criteria['min_level']) {
            return false;
        }

        return true;
    }

    public function addParticipant(User $user)
    {
        if (!$this->userCanJoin($user)) {
            return false;
        }

        $userChallenge = UserChallenge::create([
            'user_id' => $user->id,
            'challenge_id' => $this->id,
            'joined_at' => now(),
            'is_active' => true,
            'current_value' => 0,
            'progress_percentage' => 0,
        ]);

        $this->increment('current_participants');

        // Auto-enroll si nécessaire
        if ($this->auto_enroll) {
            $userChallenge->activate();
        }

        return $userChallenge;
    }

    public function updateProgress(User $user, $newValue, $progressData = [])
    {
        $userChallenge = $this->userChallenges()
                             ->where('user_id', $user->id)
                             ->where('is_active', true)
                             ->first();

        if (!$userChallenge) return false;

        $progressPercentage = min(100, ($newValue / $this->target_value) * 100);
        $isCompleted = $newValue >= $this->target_value;

        $updateData = [
            'current_value' => $newValue,
            'progress_percentage' => $progressPercentage,
            'progress_data' => $progressData,
            'last_updated_at' => now(),
        ];

        if ($isCompleted && !$userChallenge->is_completed) {
            $completionRank = $this->userChallenges()
                                  ->where('is_completed', true)
                                  ->count() + 1;

            $updateData = array_merge($updateData, [
                'is_completed' => true,
                'completed_at' => now(),
                'completion_rank' => $completionRank,
                'points_earned' => $this->calculatePointsEarned($completionRank),
                'is_winner' => $completionRank === 1,
            ]);

            // Ajouter les points à l'utilisateur
            if ($updateData['points_earned'] > 0) {
                $user->addKaliPoints(
                    $updateData['points_earned'], 
                    true, 
                    "Défi: {$this->title}"
                );
            }

            // Déclencher événement de completion
            event(new \App\Events\ChallengeCompleted($userChallenge->fresh()));
        }

        $userChallenge->update($updateData);

        return $userChallenge->fresh();
    }

    private function calculatePointsEarned($rank)
    {
        $basePoints = $this->reward_points;

        return match($rank) {
            1 => $basePoints * 2,      // Double points pour le premier
            2 => $basePoints * 1.5,    // 50% bonus pour le second  
            3 => $basePoints * 1.25,   // 25% bonus pour le troisième
            default => $basePoints     // Points normaux pour les autres
        };
    }

    public function activate()
    {
        $this->update(['status' => 'active']);

        // Inscription automatique si configuré
        if ($this->auto_enroll) {
            $this->autoEnrollEligibleUsers();
        }

        return $this;
    }

    private function autoEnrollEligibleUsers()
    {
        $eligibleUsers = User::where('company_id', $this->company_id)
                            ->whereHas('employee')
                            ->get()
                            ->filter(function ($user) {
                                return $this->userCanJoin($user);
                            });

        foreach ($eligibleUsers as $user) {
            $this->addParticipant($user);
        }
    }

    public function complete()
    {
        $this->update(['status' => 'completed']);

        // Calculer les classements finaux
        $this->calculateFinalRankings();

        return $this;
    }

    private function calculateFinalRankings()
    {
        $participants = $this->userChallenges()
                            ->orderBy('is_completed', 'desc')
                            ->orderBy('completed_at', 'asc')
                            ->orderBy('current_value', 'desc')
                            ->get();

        foreach ($participants as $index => $participant) {
            $participant->update([
                'final_rank' => $index + 1,
                'final_score' => $participant->current_value,
            ]);
        }
    }

    // Méthodes statiques
    public static function getAvailableChallenges($companyId, $userId = null)
    {
        $query = static::byCompany($companyId)->available();

        if ($userId) {
            $query->whereDoesntHave('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        return $query->with(['createdBy', 'company'])
                    ->orderBy('is_featured', 'desc')
                    ->orderBy('start_date', 'desc')
                    ->get();
    }

    public static function getFeaturedChallenges($companyId, $limit = 3)
    {
        return static::byCompany($companyId)
                    ->active()
                    ->featured()
                    ->orderBy('current_participants', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getCompanyStats($companyId)
    {
        return [
            'total_challenges' => static::byCompany($companyId)->count(),
            'active_challenges' => static::byCompany($companyId)->active()->count(),
            'completed_challenges' => static::byCompany($companyId)->completed()->count(),
            'total_participants' => static::byCompany($companyId)->sum('current_participants'),
            'completion_rate' => static::byCompany($companyId)->avg('completion_rate'),
        ];
    }
}