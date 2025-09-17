<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'type',
        'criteria',
        'points_reward',
        'is_active',
        'rarity',
    ];

    protected $casts = [
        'criteria' => 'array',
        'points_reward' => 'integer',
        'level' => 'integer',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relations
    public function userBadges()
    {
        return $this->hasMany(UserBadge::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
                    ->withPivot('earned_date', 'achievement_score', 'points_earned')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByRarity($query, $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('rarity', 'desc')
                    ->orderBy('level', 'desc')
                    ->orderBy('sort_order');
    }

    // Accessors
    public function getRarityLabelAttribute()
    {
        return match($this->rarity) {
            'common' => 'Commun',
            'uncommon' => 'Peu commun',
            'rare' => 'Rare',
            'epic' => 'Épique',
            'legendary' => 'Légendaire',
            default => 'Inconnu'
        };
    }

    public function getRarityColorAttribute()
    {
        return match($this->rarity) {
            'common' => '#6B7280',    // Gris
            'uncommon' => '#10B981',  // Vert
            'rare' => '#3B82F6',      // Bleu
            'epic' => '#8B5CF6',      // Violet
            'legendary' => '#F59E0B', // Or
            default => '#6B7280'
        };
    }

    public function getCategoryLabelAttribute()
    {
        return match($this->category) {
            'performance' => 'Performance',
            'satisfaction' => 'Satisfaction',
            'speed' => 'Rapidité',
            'consistency' => 'Régularité',
            'leadership' => 'Leadership',
            'innovation' => 'Innovation',
            'teamwork' => 'Esprit d\'équipe',
            'special' => 'Spécial',
            default => 'Général'
        };
    }

    public function getFrequencyLabelAttribute()
    {
        return match($this->frequency) {
            'once' => 'Une fois',
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'yearly' => 'Annuel',
            default => 'Une fois'
        };
    }

    public function getEarningsCountAttribute()
    {
        return $this->userBadges()->count();
    }

    public function getLatestEarningsAttribute()
    {
        return $this->userBadges()
                    ->with('user')
                    ->latest('earned_date')
                    ->limit(5)
                    ->get();
    }

    // Méthodes utilitaires
    public function checkEligibility(User $user, $period = null)
    {
        $criteria = $this->criteria;
        
        if (!$criteria) return false;

        switch ($criteria['type']) {
            case 'satisfaction_rate':
                return $this->checkSatisfactionRate($user, $criteria, $period);
                
            case 'avg_resolution_time':
                return $this->checkResolutionTime($user, $criteria, $period);
                
            case 'total_positive_feedbacks':
                return $this->checkTotalPositiveFeedbacks($user, $criteria);
                
            case 'resolution_rate':
                return $this->checkResolutionRate($user, $criteria, $period);
                
            case 'monthly_ranking':
                return $this->checkMonthlyRanking($user, $criteria, $period);
                
            default:
                return false;
        }
    }

    private function checkSatisfactionRate(User $user, $criteria, $period)
    {
        $query = \App\Models\Feedback::where('employee_id', $user->id)
                    ->whereNotNull('rating');

        if ($period) {
            $query->whereMonth('created_at', $period['month'])
                  ->whereYear('created_at', $period['year']);
        }

        $feedbacks = $query->get();
        
        if ($feedbacks->count() < ($criteria['min_feedbacks'] ?? 0)) {
            return false;
        }

        $avgRating = $feedbacks->avg('rating');
        $satisfactionRate = ($avgRating / 5) * 100;

        return $satisfactionRate >= $criteria['threshold'];
    }

    private function checkResolutionTime(User $user, $criteria, $period)
    {
        $query = \App\Models\Feedback::where('employee_id', $user->id)
                    ->whereNotNull('resolved_at')
                    ->whereIn('type', ['incident', 'negatif']);

        if ($period) {
            $query->whereMonth('created_at', $period['month'])
                  ->whereYear('created_at', $period['year']);
        }

        $incidents = $query->get();
        
        if ($incidents->count() < ($criteria['min_incidents'] ?? 0)) {
            return false;
        }

        $avgResolutionHours = $incidents->map(function ($incident) {
            return $incident->created_at->diffInHours($incident->resolved_at);
        })->avg();

        return $avgResolutionHours <= $criteria['max_hours'];
    }

    private function checkTotalPositiveFeedbacks(User $user, $criteria)
    {
        $count = \App\Models\Feedback::where('employee_id', $user->id)
                    ->where('type', 'appreciation')
                    ->count();

        return $count >= $criteria['threshold'];
    }

    private function checkResolutionRate(User $user, $criteria, $period)
    {
        $query = \App\Models\Feedback::where('employee_id', $user->id)
                    ->whereIn('type', ['incident', 'negatif']);

        if ($period) {
            $query->whereMonth('created_at', $period['month'])
                  ->whereYear('created_at', $period['year']);
        }

        $totalIncidents = $query->count();
        
        if ($totalIncidents < ($criteria['min_incidents'] ?? 0)) {
            return false;
        }

        $resolvedIncidents = $query->clone()
                                 ->whereNotNull('resolved_at')
                                 ->count();

        $resolutionRate = ($resolvedIncidents / $totalIncidents) * 100;

        return $resolutionRate >= $criteria['threshold'];
    }

    private function checkMonthlyRanking(User $user, $criteria, $period)
    {
        // Vérifier le classement mensuel
        $leaderboard = \App\Models\Leaderboard::where('user_id', $user->id)
                        ->where('period_type', 'monthly')
                        ->where('metric_type', $criteria['metric'])
                        ->when($period, function ($query) use ($period) {
                            $query->whereMonth('period_date', $period['month'])
                                  ->whereYear('period_date', $period['year']);
                        })
                        ->first();

        if (!$leaderboard) return false;

        return $leaderboard->rank_overall <= $criteria['position'] &&
               $leaderboard->detailed_metrics['total_feedbacks'] >= ($criteria['min_feedbacks'] ?? 0);
    }

    public function awardToUser(User $user, $achievementData = [], $awardedBy = null)
    {
        $period = $this->getCurrentPeriod();
        
        // Vérifier si déjà obtenu pour cette période (pour badges périodiques)
        if ($this->frequency !== 'once') {
            $existing = UserBadge::where('user_id', $user->id)
                                 ->where('badge_id', $this->id)
                                 ->where('period', $period)
                                 ->first();
                                 
            if ($existing) return $existing;
        }

        $userBadge = UserBadge::create([
            'user_id' => $user->id,
            'badge_id' => $this->id,
            'company_id' => $user->company_id,
            'earned_date' => now()->toDateString(),
            'period' => $period,
            'achievement_data' => $achievementData,
            'points_earned' => $this->points_reward,
            'achievement_score' => $achievementData['score'] ?? null,
            'rank_position' => $achievementData['rank'] ?? null,
            'awarded_by' => $awardedBy?->id,
        ]);

        // Ajouter les KaliPoints à l'utilisateur
        if ($this->points_reward > 0) {
            $user->addKaliPoints($this->points_reward, true, "Badge: {$this->title}");
        }

        return $userBadge;
    }

    private function getCurrentPeriod()
    {
        return match($this->frequency) {
            'daily' => now()->format('Y-m-d'),
            'weekly' => now()->format('Y-\WW'),
            'monthly' => now()->format('Y-m'),
            'yearly' => now()->format('Y'),
            default => null
        };
    }

    public static function getBadgesByCategory()
    {
        return static::active()->public()->ordered()
                    ->get()
                    ->groupBy('category');
    }

    public static function getTopBadges($limit = 10)
    {
        return static::active()->public()
                    ->withCount('userBadges')
                    ->orderBy('user_badges_count', 'desc')
                    ->orderBy('rarity', 'desc')
                    ->limit($limit)
                    ->get();
    }
}