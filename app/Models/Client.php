<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'phone',
        'total_feedbacks',
        'total_kalipoints',
        'bonus_kalipoints',
        'status',
        'first_feedback_at',
        'last_feedback_at',
    ];

    protected $casts = [
        'total_feedbacks' => 'integer',
        'total_kalipoints' => 'integer',
        'bonus_kalipoints' => 'integer',
        'first_feedback_at' => 'datetime',
        'last_feedback_at' => 'datetime',
    ];

    // Relations
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        if (!$this->first_name && !$this->last_name) {
            return 'Client Anonyme';
        }
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getIsRecurrentAttribute()
    {
        return $this->total_feedbacks > 1;
    }

    public function getContactInfoAttribute()
    {
        return $this->email ?: $this->phone ?: 'Anonyme';
    }

    // Méthodes pour les statistiques
    public function getPositiveFeedbacksCountAttribute()
    {
        return $this->feedbacks()->where('type', 'appreciation')->count();
    }

    public function getNegativeFeedbacksCountAttribute()
    {
        return $this->feedbacks()->where('type', 'incident')->count();
    }

    public function getSuggestionsCountAttribute()
    {
        return $this->feedbacks()->where('type', 'suggestion')->count();
    }

    // Méthodes utilitaires
    public static function findOrCreateByContact($email = null, $phone = null, $firstName = null, $lastName = null)
    {
        $query = static::query();
        
        if ($email) {
            $query->where('email', $email);
        } elseif ($phone) {
            $query->where('phone', $phone);
        } else {
            // Créer un client anonyme
            return static::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'first_feedback_at' => now(),
                'last_feedback_at' => now(),
                'total_feedbacks' => 1,
            ]);
        }

        $client = $query->first();

        if (!$client) {
            $client = static::create([
                'email' => $email,
                'phone' => $phone,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'first_feedback_at' => now(),
                'last_feedback_at' => now(),
                'total_feedbacks' => 1,
            ]);
        } else {
            // Mettre à jour les informations si elles sont fournies
            $updates = [];
            if ($firstName && !$client->first_name) $updates['first_name'] = $firstName;
            if ($lastName && !$client->last_name) $updates['last_name'] = $lastName;
            if ($email && !$client->email) $updates['email'] = $email;
            if ($phone && !$client->phone) $updates['phone'] = $phone;
            
            $updates['last_feedback_at'] = now();
            $updates['total_feedbacks'] = $client->total_feedbacks + 1;

            $client->update($updates);
        }

        return $client;
    }

    public function addKaliPoints($points, $isBonus = false, $reason = null)
    {
        if ($isBonus) {
            $this->increment('bonus_kalipoints', $points);
        } else {
            $this->increment('total_kalipoints', $points);
        }
    }

    public function deductKaliPoints($points, $reason = null)
    {
        // Vérifier qu'il y a assez de points
        if ($this->total_kalipoints < $points) {
            throw new \Exception("Points KaliPoints insuffisants");
        }
        
        $this->decrement('total_kalipoints', $points);
    }

    // Relations pour les récompenses
    public function rewardClaims()
    {
        return $this->hasMany(RewardClaim::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'recipient');
    }

    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }
}