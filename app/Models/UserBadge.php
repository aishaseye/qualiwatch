<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBadge extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'badge_id',
        'company_id',
        'earned_date',
        'period',
        'achievement_data',
        'points_earned',
        'achievement_score',
        'rank_position',
        'metrics_snapshot',
        'is_featured',
        'is_announced',
        'announced_at',
        'awarded_by',
        'award_message',
    ];

    protected $casts = [
        'earned_date' => 'date',
        'achievement_data' => 'array',
        'points_earned' => 'integer',
        'achievement_score' => 'decimal:2',
        'rank_position' => 'integer',
        'metrics_snapshot' => 'array',
        'is_featured' => 'boolean',
        'is_announced' => 'boolean',
        'announced_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function badge()
    {
        return $this->belongsTo(Badge::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function awardedBy()
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }

    // Scopes
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('earned_date', '>=', now()->subDays($days));
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeTopPerformers($query, $limit = 10)
    {
        return $query->join('badges', 'user_badges.badge_id', '=', 'badges.id')
                    ->orderBy('badges.rarity', 'desc')
                    ->orderBy('user_badges.achievement_score', 'desc')
                    ->limit($limit);
    }

    // Accessors
    public function getEarnedAgoAttribute()
    {
        return $this->earned_date->diffForHumans();
    }

    public function getRarityAttribute()
    {
        return $this->badge?->rarity ?? 'common';
    }

    public function getRarityColorAttribute()
    {
        return $this->badge?->rarity_color ?? '#6B7280';
    }

    public function getBadgeIconAttribute()
    {
        return $this->badge?->icon ?? 'star';
    }

    public function getBadgeColorAttribute()
    {
        return $this->badge?->color ?? '#F59E0B';
    }

    // Méthodes utilitaires
    public function markAsFeatured($message = null)
    {
        $this->update([
            'is_featured' => true,
            'award_message' => $message
        ]);

        return $this;
    }

    public function announce()
    {
        $this->update([
            'is_announced' => true,
            'announced_at' => now()
        ]);

        // Déclencher événement d'annonce
        event(new \App\Events\BadgeEarned($this));

        return $this;
    }

    public function getShareableMessage()
    {
        $user = $this->user;
        $badge = $this->badge;
        
        return "🏆 {$user->name} vient d'obtenir le badge \"{$badge->title}\" ! {$badge->description}";
    }

    // Méthodes statiques
    public static function getRecentAchievements($companyId, $limit = 10)
    {
        return static::byCompany($companyId)
                    ->with(['user', 'badge'])
                    ->recent()
                    ->orderBy('earned_date', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getTopBadgeEarners($companyId, $period = 'monthly', $limit = 10)
    {
        $query = static::byCompany($companyId)
                      ->with(['user', 'badge']);

        switch ($period) {
            case 'monthly':
                $query->whereMonth('earned_date', now()->month)
                      ->whereYear('earned_date', now()->year);
                break;
            case 'weekly':
                $query->whereBetween('earned_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'daily':
                $query->whereDate('earned_date', today());
                break;
        }

        return $query->select('user_id', \DB::raw('COUNT(*) as badges_count'), \DB::raw('SUM(points_earned) as total_points'))
                    ->groupBy('user_id')
                    ->orderBy('badges_count', 'desc')
                    ->orderBy('total_points', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getBadgeDistribution($companyId)
    {
        return static::byCompany($companyId)
                    ->join('badges', 'user_badges.badge_id', '=', 'badges.id')
                    ->select('badges.category', \DB::raw('COUNT(*) as count'))
                    ->groupBy('badges.category')
                    ->get()
                    ->pluck('count', 'category');
    }
}