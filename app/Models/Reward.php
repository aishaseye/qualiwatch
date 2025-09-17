<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'type',
        'kalipoints_cost',
        'details',
        'stock',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'details' => 'array',
        'kalipoints_cost' => 'integer',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function claims()
    {
        return $this->hasMany(RewardClaim::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('stock')
                          ->orWhere('stock', '>', 0);
                    })
                    ->where(function ($q) {
                        $q->whereNull('valid_from')
                          ->orWhere('valid_from', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>=', now());
                    });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'discount' => 'Remise',
            'gift' => 'Cadeau',
            'service' => 'Service',
            'experience' => 'Expérience',
            'digital' => 'Numérique',
            default => 'Autre'
        };
    }

    public function getClaimsCountAttribute()
    {
        return $this->claims()->count();
    }

    public function getAvailableStockAttribute()
    {
        if ($this->stock === null) {
            return 'Illimité';
        }
        
        $claimed = $this->claims()->whereIn('status', ['pending', 'approved', 'delivered'])->count();
        return max(0, $this->stock - $claimed);
    }

    public function getIsAvailableAttribute()
    {
        if (!$this->is_active) return false;
        
        if ($this->stock !== null) {
            $claimed = $this->claims()->whereIn('status', ['pending', 'approved', 'delivered'])->count();
            if ($this->stock <= $claimed) return false;
        }
        
        if ($this->valid_from && $this->valid_from > now()) return false;
        if ($this->valid_until && $this->valid_until < now()) return false;
        
        return true;
    }

    // Méthodes utilitaires
    public function canBeClaimed()
    {
        return $this->is_available;
    }

    public function decreaseStock()
    {
        if ($this->stock !== null) {
            $this->decrement('stock');
        }
    }
}
