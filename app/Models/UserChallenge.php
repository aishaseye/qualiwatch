<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserChallenge extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'challenge_id',
        'joined_at',
        'is_active',
        'current_value',
        'progress_percentage',
        'progress_data',
        'last_updated_at',
        'is_completed',
        'completed_at',
        'completion_rank',
        'points_earned',
        'rewards_earned',
        'is_winner',
        'final_rank',
        'final_score',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'is_active' => 'boolean',
        'current_value' => 'integer',
        'progress_percentage' => 'decimal:2',
        'progress_data' => 'array',
        'last_updated_at' => 'datetime',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'completion_rank' => 'integer',
        'points_earned' => 'integer',
        'rewards_earned' => 'array',
        'is_winner' => 'boolean',
        'final_rank' => 'integer',
        'final_score' => 'decimal:2',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeInProgress($query)
    {
        return $query->where('is_active', true)
                    ->where('is_completed', false);
    }

    public function scopeWinners($query)
    {
        return $query->where('is_winner', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByChallenge($query, $challengeId)
    {
        return $query->where('challenge_id', $challengeId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('joined_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getProgressStatusAttribute()
    {
        if ($this->is_completed) return 'completed';
        if (!$this->is_active) return 'inactive';
        if ($this->progress_percentage >= 75) return 'near_completion';
        if ($this->progress_percentage >= 25) return 'in_progress';
        return 'started';
    }

    public function getProgressStatusLabelAttribute()
    {
        return match($this->progress_status) {
            'completed' => 'Terminé',
            'inactive' => 'Inactif',
            'near_completion' => 'Presque terminé',
            'in_progress' => 'En cours',
            'started' => 'Commencé',
            default => 'Inconnu'
        };
    }

    public function getProgressStatusColorAttribute()
    {
        return match($this->progress_status) {
            'completed' => '#10B981',      // Vert
            'inactive' => '#6B7280',       // Gris
            'near_completion' => '#F59E0B', // Orange
            'in_progress' => '#3B82F6',     // Bleu
            'started' => '#8B5CF6',        // Violet
            default => '#6B7280'
        };
    }

    public function getRankDisplayAttribute()
    {
        if (!$this->completion_rank) return null;

        return match($this->completion_rank) {
            1 => '🥇 1er',
            2 => '🥈 2ème',
            3 => '🥉 3ème',
            default => "#{$this->completion_rank}"
        };
    }

    public function getProgressBarColorAttribute()
    {
        if ($this->is_completed) return '#10B981';
        if ($this->progress_percentage >= 75) return '#F59E0B';
        if ($this->progress_percentage >= 50) return '#3B82F6';
        return '#8B5CF6';
    }

    public function getTimeToCompleteAttribute()
    {
        if (!$this->is_completed || !$this->completed_at) return null;

        return $this->joined_at->diffForHumans($this->completed_at, true);
    }

    public function getAchievementSummaryAttribute()
    {
        if (!$this->is_completed) return null;

        $summary = [];

        if ($this->is_winner) {
            $summary[] = "🏆 Gagnant";
        }

        if ($this->completion_rank && $this->completion_rank <= 3) {
            $summary[] = "🏅 Podium #{$this->completion_rank}";
        }

        if ($this->points_earned > 0) {
            $summary[] = "{$this->points_earned} points gagnés";
        }

        return implode(' • ', $summary) ?: 'Défi terminé';
    }

    // Méthodes utilitaires
    public function updateProgress($newValue, $progressData = [])
    {
        if (!$this->is_active || $this->is_completed) {
            return false;
        }

        $challenge = $this->challenge;
        $progressPercentage = min(100, ($newValue / $challenge->target_value) * 100);
        $isCompleted = $newValue >= $challenge->target_value;

        $updateData = [
            'current_value' => $newValue,
            'progress_percentage' => $progressPercentage,
            'progress_data' => array_merge($this->progress_data ?? [], $progressData),
            'last_updated_at' => now(),
        ];

        if ($isCompleted && !$this->is_completed) {
            // Calculer le rang de completion
            $completionRank = UserChallenge::where('challenge_id', $this->challenge_id)
                                         ->where('is_completed', true)
                                         ->count() + 1;

            $pointsEarned = $this->calculatePointsEarned($completionRank);

            $updateData = array_merge($updateData, [
                'is_completed' => true,
                'completed_at' => now(),
                'completion_rank' => $completionRank,
                'points_earned' => $pointsEarned,
                'is_winner' => $completionRank === 1,
            ]);

            // Ajouter les récompenses
            if ($challenge->reward_badges) {
                $updateData['rewards_earned'] = [
                    'badges' => $challenge->reward_badges,
                    'points' => $pointsEarned,
                    'rank' => $completionRank,
                ];
            }

            // Ajouter les points à l'utilisateur
            if ($pointsEarned > 0) {
                $this->user->addKaliPoints(
                    $pointsEarned, 
                    true, 
                    "Défi terminé: {$challenge->title}"
                );
            }
        }

        $this->update($updateData);

        // Déclencher événement de progression
        if ($isCompleted) {
            event(new \App\Events\ChallengeCompleted($this->fresh()));
        } else {
            event(new \App\Events\ChallengeProgressUpdated($this->fresh()));
        }

        return $this->fresh();
    }

    private function calculatePointsEarned($rank)
    {
        $basePoints = $this->challenge->reward_points;

        return match($rank) {
            1 => $basePoints * 2,      // Double pour le gagnant
            2 => $basePoints * 1.5,    // 50% bonus pour le second
            3 => $basePoints * 1.25,   // 25% bonus pour le troisième
            default => $basePoints     // Points normaux
        };
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
        return $this;
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
        return $this;
    }

    public function markCompleted($finalScore = null)
    {
        if ($this->is_completed) return $this;

        $completionRank = UserChallenge::where('challenge_id', $this->challenge_id)
                                     ->where('is_completed', true)
                                     ->count() + 1;

        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completion_rank' => $completionRank,
            'final_score' => $finalScore ?? $this->current_value,
            'points_earned' => $this->calculatePointsEarned($completionRank),
            'is_winner' => $completionRank === 1,
        ]);

        event(new \App\Events\ChallengeCompleted($this));

        return $this;
    }

    // Méthodes statiques
    public static function getUserActivechallenges($userId)
    {
        return static::byUser($userId)
                    ->active()
                    ->with(['challenge'])
                    ->orderBy('progress_percentage', 'desc')
                    ->get();
    }

    public static function getUserCompletedChallenges($userId, $limit = 10)
    {
        return static::byUser($userId)
                    ->completed()
                    ->with(['challenge'])
                    ->orderBy('completed_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getChallengeLeaderboard($challengeId, $limit = 10)
    {
        return static::byChallenge($challengeId)
                    ->with(['user'])
                    ->orderBy('is_completed', 'desc')
                    ->orderBy('completed_at', 'asc')
                    ->orderBy('progress_percentage', 'desc')
                    ->orderBy('current_value', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getRecentAchievements($companyId, $limit = 10)
    {
        return static::whereHas('challenge', function ($query) use ($companyId) {
                        $query->where('company_id', $companyId);
                    })
                    ->completed()
                    ->with(['user', 'challenge'])
                    ->orderBy('completed_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getUserChallengeStats($userId)
    {
        $challenges = static::byUser($userId);

        return [
            'total_joined' => $challenges->count(),
            'active' => $challenges->clone()->active()->count(),
            'completed' => $challenges->clone()->completed()->count(),
            'wins' => $challenges->clone()->winners()->count(),
            'total_points_earned' => $challenges->clone()->completed()->sum('points_earned'),
            'completion_rate' => $challenges->count() > 0 ? 
                round(($challenges->clone()->completed()->count() / $challenges->count()) * 100, 1) : 0,
            'avg_completion_time' => static::getAverageCompletionTime($userId),
        ];
    }

    private static function getAverageCompletionTime($userId)
    {
        $completed = static::byUser($userId)
                           ->completed()
                           ->whereNotNull('completed_at')
                           ->get();

        if ($completed->isEmpty()) return null;

        $totalHours = $completed->sum(function ($challenge) {
            return $challenge->joined_at->diffInHours($challenge->completed_at);
        });

        return round($totalHours / $completed->count(), 1);
    }
}