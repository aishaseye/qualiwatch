<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the prefixed ID for display purposes.
     */
    public function getDisplayIdAttribute()
    {
        return 'SRV-' . $this->id;
    }

    private static $statusIds = null;

    private static function getStatusIds()
    {
        if (self::$statusIds === null) {
            self::$statusIds = \App\Models\FeedbackStatus::pluck('id', 'name')->toArray();
        }
        return self::$statusIds;
    }

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'icon',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'display_id'
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    // Méthodes pour les statistiques
    public function getTotalFeedbacksAttribute()
    {
        return $this->feedbacks()->count();
    }

    public function getPositiveFeedbacksAttribute()
    {
        return $this->feedbacks()->where('type', 'appreciation')->count();
    }

    public function getNegativeFeedbacksAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->count();
    }

    public function getSuggestionsAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->count();
    }

    public function getAverageKaliPointsAttribute()
    {
        return $this->feedbacks()->avg('kalipoints') ?: 0;
    }

    public function getAverageKalipointsPositifAttribute()
    {
        return $this->feedbacks()->where('type', 'appreciation')->avg('kalipoints') ?: 0;
    }

    public function getAverageKalipointsNegatifAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->avg('kalipoints') ?: 0;
    }

    public function getAverageKalipointsSuggestionAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->avg('kalipoints') ?: 0;
    }

    // Métriques par statut pour feedbacks négatifs
    public function getNegativeNewAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'incident')->where('status_id', $statusIds['new'] ?? null)->count();
    }

    public function getNegativeSeenAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'incident')->where('status_id', $statusIds['seen'] ?? null)->count();
    }

    public function getNegativeInProgressAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'incident')->where('status_id', $statusIds['in_progress'] ?? null)->count();
    }

    public function getNegativeTreatedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'incident')->where('status_id', $statusIds['treated'] ?? null)->count();
    }

    public function getNegativeResolvedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'incident')->where('status_id', $statusIds['resolved'] ?? null)->count();
    }

    public function getNegativePartiallyResolvedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'incident')->where('status_id', $statusIds['partially_resolved'] ?? null)->count();
    }

    public function getNegativeNotResolvedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'incident')->where('status_id', $statusIds['not_resolved'] ?? null)->count();
    }

    // Moyennes KaliPoints par statut pour feedbacks négatifs
    public function getAvgKalipointsNegativeResolvedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'incident')->where('status_id', $statusIds['resolved'] ?? null)->avg('kalipoints') ?: 0;
    }

    public function getAvgKalipointsNegativePartialAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'incident')->where('status_id', $statusIds['partially_resolved'] ?? null)->avg('kalipoints') ?: 0;
    }

    public function getAvgKalipointsNegativeNotResolvedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'incident')->where('status_id', $statusIds['not_resolved'] ?? null)->avg('kalipoints') ?: 0;
    }

    // Métriques par statut pour suggestions
    public function getSuggestionNewAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', $statusIds['new'] ?? null)->count();
    }

    public function getSuggestionSeenAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', $statusIds['seen'] ?? null)->count();
    }

    public function getSuggestionInProgressAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', $statusIds['in_progress'] ?? null)->count();
    }

    public function getSuggestionTreatedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', $statusIds['treated'] ?? null)->count();
    }

    public function getSuggestionImplementedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', $statusIds['implemented'] ?? null)->count();
    }

    public function getSuggestionPartiallyImplementedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', $statusIds['partially_implemented'] ?? null)->count();
    }

    public function getSuggestionRejectedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', $statusIds['rejected'] ?? null)->count();
    }

    // Moyennes KaliPoints par statut pour suggestions
    public function getAvgKalipointsSuggestionImplementedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', $statusIds['implemented'] ?? null)->avg('kalipoints') ?: 0;
    }

    public function getAvgKalipointsSuggestionPartialAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', $statusIds['partially_implemented'] ?? null)->avg('kalipoints') ?: 0;
    }

    public function getAvgKalipointsSuggestionRejectedAttribute()
    {
        $statusIds = self::getStatusIds();
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', $statusIds['rejected'] ?? null)->avg('kalipoints') ?: 0;
    }

    public function getSatisfactionScoreAttribute()
    {
        $total = $this->total_feedbacks;
        if ($total == 0) return 0;
        
        return round(($this->positive_feedbacks / $total) * 100, 1);
    }

    public function getActiveEmployeesCountAttribute()
    {
        return $this->employees()->where('is_active', true)->count();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}