<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the prefixed ID for display purposes.
     */
    public function getDisplayIdAttribute()
    {
        return 'CMP-' . $this->id;
    }

    protected $fillable = [
        'manager_id',
        'name',
        'email',
        'location',
        'business_sector_id',
        'employee_count_id',
        'creation_year',
        'phone',
        'business_description',
        'logo',
        'qr_code',
    ];

    protected $casts = [
        'creation_year' => 'integer',
    ];

    protected $appends = [
        'display_id'
    ];

    // Relations
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function businessSector()
    {
        return $this->belongsTo(BusinessSector::class, 'business_sector_id');
    }

    public function employeeCount()
    {
        return $this->belongsTo(EmployeeCount::class, 'employee_count_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    public function rewardClaims()
    {
        return $this->hasMany(RewardClaim::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationTemplates()
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    public function slaRules()
    {
        return $this->hasMany(SlaRule::class);
    }

    // Accessors
    public function getLogoUrlAttribute()
    {
        return $this->logo ? 
            asset('storage/companies/' . $this->logo) : 
            asset('images/default-company.png');
    }

    public function getQrCodeUrlAttribute()
    {
        return $this->qr_code ? 
            asset('storage/qr-codes/' . $this->qr_code) : 
            null;
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

    public function getSatisfactionScoreAttribute()
    {
        $total = $this->total_feedbacks;
        if ($total == 0) return 0;
        
        return round(($this->positive_feedbacks / $total) * 100, 1);
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

    public function getBusinessSectorLabelAttribute()
    {
        return $this->businessSector?->name ?? 'Non spécifié';
    }

    public function getBusinessSectorCodeAttribute()
    {
        return $this->businessSector?->code;
    }

    public function getBusinessSectorColorAttribute()
    {
        return $this->businessSector?->color ?? '#6B7280';
    }

    public function getBusinessSectorIconAttribute()
    {
        return $this->businessSector?->icon ?? 'briefcase';
    }

    // Méthode statique pour récupérer les options de secteurs depuis BusinessSector
    public static function getBusinessSectorOptions()
    {
        return \App\Models\BusinessSector::getActiveOptions();
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