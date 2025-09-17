<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackStatus extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'label',
        'description',
        'color',
        'category',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Relations
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Méthodes statiques utiles
    public static function getByName($name)
    {
        return static::where('name', $name)->where('is_active', true)->first();
    }

    public static function getActiveStatuses()
    {
        return static::active()->ordered()->get();
    }

    public static function getByCategory($category)
    {
        return static::active()->byCategory($category)->ordered()->get();
    }

    // Status par défaut - méthodes de commodité
    public static function getNewStatus()
    {
        return static::getByName('new');
    }

    public static function getInProgressStatus()
    {
        return static::getByName('in_progress');
    }

    public static function getTreatedStatus()
    {
        return static::getByName('treated');
    }

    public static function getResolvedStatus()
    {
        return static::getByName('resolved');
    }

    public static function getPartiallyResolvedStatus()
    {
        return static::getByName('partially_resolved');
    }

    public static function getNotResolvedStatus()
    {
        return static::getByName('not_resolved');
    }

    public static function getImplementedStatus()
    {
        return static::getByName('implemented');
    }

    public static function getPartiallyImplementedStatus()
    {
        return static::getByName('partially_implemented');
    }

    public static function getRejectedStatus()
    {
        return static::getByName('rejected');
    }


    // Vérifications
    public function isNew()
    {
        return $this->name === 'new';
    }

    public function isInProgress()
    {
        return $this->name === 'in_progress';
    }

    public function isTreated()
    {
        return $this->name === 'treated';
    }

    public function isResolved()
    {
        return $this->name === 'resolved';
    }

    public function isPartiallyResolved()
    {
        return $this->name === 'partially_resolved';
    }

    public function isNotResolved()
    {
        return $this->name === 'not_resolved';
    }

    public function isImplemented()
    {
        return $this->name === 'implemented';
    }

    public function isPartiallyImplemented()
    {
        return $this->name === 'partially_implemented';
    }

    public function isRejected()
    {
        return $this->name === 'rejected';
    }



    // Méthodes pour les transitions de statut
    public function canTransitionTo($targetStatus)
    {
        if (is_string($targetStatus)) {
            $targetStatus = static::getByName($targetStatus);
        }

        if (!$targetStatus) return false;

        // Règles de transition (exemples)
        $allowedTransitions = [
            'new' => ['seen', 'in_progress', 'treated'],
            'seen' => ['in_progress', 'treated'],
            'in_progress' => ['treated'],
            'treated' => ['resolved', 'partially_resolved', 'not_resolved', 'implemented', 'partially_implemented', 'rejected'],
            'resolved' => [],
            'partially_resolved' => ['resolved', 'not_resolved'],
            'not_resolved' => ['in_progress', 'resolved'],
            'implemented' => [],
            'partially_implemented' => ['implemented', 'rejected'],
            'rejected' => []
        ];

        $allowedTargets = $allowedTransitions[$this->name] ?? [];
        return in_array($targetStatus->name, $allowedTargets);
    }

    public function getNextPossibleStatuses()
    {
        $allowedTransitions = [
            'new' => ['seen', 'in_progress', 'treated'],
            'seen' => ['in_progress', 'treated'],
            'in_progress' => ['treated'],
            'treated' => ['resolved', 'partially_resolved', 'not_resolved', 'implemented', 'partially_implemented', 'rejected'],
            'resolved' => [],
            'partially_resolved' => ['resolved', 'not_resolved'],
            'not_resolved' => ['in_progress', 'resolved'],
            'implemented' => [],
            'partially_implemented' => ['implemented', 'rejected'],
            'rejected' => []
        ];

        $allowedNames = $allowedTransitions[$this->name] ?? [];
        
        return static::whereIn('name', $allowedNames)
                    ->where('is_active', true)
                    ->ordered()
                    ->get();
    }
}