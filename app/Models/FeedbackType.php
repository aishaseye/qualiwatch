<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackType extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'label',
        'color',
        'icon',
        'description',
        'requires_validation',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_validation' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relations
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }
    
    public function sentiments()
    {
        return $this->belongsToMany(Sentiment::class, 'feedback_type_sentiments')
                    ->withPivot('is_default', 'sort_order')
                    ->withTimestamps()
                    ->orderBy('pivot_sort_order');
    }
    
    public function defaultSentiment()
    {
        return $this->belongsToMany(Sentiment::class, 'feedback_type_sentiments')
                    ->withPivot('is_default', 'sort_order')
                    ->wherePivot('is_default', true)
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeRequiringValidation($query)
    {
        return $query->where('requires_validation', true);
    }

    // Accessors
    public function getAvailableSentimentsListAttribute()
    {
        return $this->sentiments;
    }

    public function getSentimentOptionsAttribute()
    {
        return $this->sentiments->map(function ($sentiment) {
            return [
                'id' => $sentiment->id,
                'name' => $sentiment->name,
                'description' => $sentiment->description,
                'is_default' => $sentiment->pivot->is_default,
                'sort_order' => $sentiment->pivot->sort_order
            ];
        });
    }

    // Méthodes utilitaires
    public function getDefaultSentiment()
    {
        return $this->defaultSentiment()->first();
    }
    
    public function hasSentiment($sentimentId)
    {
        return $this->sentiments()->where('sentiment_id', $sentimentId)->exists();
    }

    // Méthodes statiques utiles
    public static function getByName($name)
    {
        return static::where('name', $name)->where('is_active', true)->first();
    }

    public static function getActiveTypes()
    {
        return static::active()->ordered()->get();
    }

    public static function getTypesRequiringValidation()
    {
        return static::active()->requireValidation()->get();
    }

    // Types par défaut
    public static function getPositifType()
    {
        return static::getByName('positif');
    }

    public static function getNegatifType()
    {
        return static::getByName('negatif');
    }

    public static function getIncidentType()
    {
        return static::getByName('incident');
    }

    // Vérifications
    public function isPositif()
    {
        return $this->name === 'positif';
    }

    public function isNegatif()
    {
        return $this->name === 'negatif';
    }

    public function isIncident()
    {
        return $this->name === 'incident';
    }

    public function needsValidation()
    {
        return $this->requires_validation;
    }
}