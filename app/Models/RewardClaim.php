<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RewardClaim extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'client_id',
        'reward_id',
        'company_id',
        'kalipoints_spent',
        'status',
        'claim_details',
        'claim_code',
        'claimed_at',
        'delivered_at',
        'notes',
    ];

    protected $casts = [
        'claim_details' => 'array',
        'kalipoints_spent' => 'integer',
        'claimed_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            default => 'Inconnue'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => '#F59E0B',    // Orange
            'approved' => '#3B82F6',   // Bleu
            'delivered' => '#10B981',  // Vert
            'cancelled' => '#EF4444',  // Rouge
            default => '#6B7280'       // Gris
        };
    }

    // Événements
    protected static function booted()
    {
        static::creating(function ($claim) {
            $claim->claim_code = 'RC-' . strtoupper(Str::random(8));
            $claim->claimed_at = now();
        });

        static::created(function ($claim) {
            // Déduire les KaliPoints du client
            $claim->client->deductKaliPoints($claim->kalipoints_spent, "Échange récompense: {$claim->reward->name}");
        });
    }

    // Méthodes utilitaires
    public function approve($notes = null)
    {
        $this->update([
            'status' => 'approved',
            'notes' => $notes
        ]);
    }

    public function deliver($notes = null)
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'notes' => $notes
        ]);
    }

    public function cancel($notes = null)
    {
        // Rembourser les KaliPoints
        $this->client->addKaliPoints($this->kalipoints_spent, "Remboursement annulation: {$this->reward->name}");
        
        $this->update([
            'status' => 'cancelled',
            'notes' => $notes
        ]);
    }
}
