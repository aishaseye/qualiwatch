<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the prefixed ID for display purposes.
     */
    public function getDisplayIdAttribute()
    {
        return 'EMP-' . $this->id;
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
        'service_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'photo',
        'hire_date',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
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

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo ? 
            asset('storage/employees/' . $this->photo) : 
            asset('images/default-employee.png');
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

    public function getTotalKaliPointsGeneratedAttribute()
    {
        return $this->feedbacks()->sum('kalipoints');
    }

    public function getPerformanceScoreAttribute()
    {
        $total = $this->total_feedbacks;
        if ($total == 0) return 0;
        
        $positive = $this->positive_feedbacks;
        $negative = $this->negative_feedbacks;
        
        // Score basé sur le ratio positif/négatif et le nombre total
        $ratio = $total > 0 ? ($positive - $negative) / $total : 0;
        return max(0, min(100, ($ratio + 1) * 50)); // Normalise entre 0 et 100
    }

    public function getRecentFeedbacksAttribute()
    {
        return $this->feedbacks()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    // Métriques par statut pour feedbacks négatifs
    public function getNegativeNewAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->where('status_id', 1)->count();
    }

    public function getNegativeSeenAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->where('status_id', 2)->count();
    }

    public function getNegativeInProgressAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->where('status_id', 3)->count();
    }

    public function getNegativeTreatedAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->where('status_id', 4)->count();
    }

    public function getNegativeResolvedAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->where('status_id', 5)->count();
    }

    public function getNegativePartiallyResolvedAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->where('status_id', 6)->count();
    }

    public function getNegativeNotResolvedAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->where('status_id', 7)->count();
    }

    // Moyennes KaliPoints par statut - Négatifs
    public function getAvgKalipointsNegativeResolvedAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->where('status_id', 5)->avg('kalipoints') ?: 0;
    }

    public function getAvgKalipointsNegativePartialAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->where('status_id', 6)->avg('kalipoints') ?: 0;
    }

    public function getAvgKalipointsNegativeNotResolvedAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->where('status_id', 7)->avg('kalipoints') ?: 0;
    }

    // Métriques par statut pour suggestions
    public function getSuggestionNewAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', 1)->count();
    }

    public function getSuggestionSeenAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', 2)->count();
    }

    public function getSuggestionInProgressAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', 3)->count();
    }

    public function getSuggestionTreatedAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', 4)->count();
    }

    public function getSuggestionImplementedAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', 8)->count();
    }

    public function getSuggestionPartiallyImplementedAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', 9)->count();
    }

    public function getSuggestionRejectedAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', 10)->count();
    }

    // Moyennes KaliPoints par statut - Suggestions
    public function getAvgKalipointsSuggestionImplementedAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', 8)->avg('kalipoints') ?: 0;
    }

    public function getAvgKalipointsSuggestionPartialAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', 9)->avg('kalipoints') ?: 0;
    }

    public function getAvgKalipointsSuggestionRejectedAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->where('status_id', 10)->avg('kalipoints') ?: 0;
    }
}