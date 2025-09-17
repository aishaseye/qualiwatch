<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidationLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'feedback_id',
        'token',
        'client_ip',
        'client_user_agent',
        'validation_status',
        'satisfaction_rating',
        'comment',
        'bonus_points_awarded',
        'validated_at',
    ];

    protected $casts = [
        'satisfaction_rating' => 'integer',
        'bonus_points_awarded' => 'integer',
        'validated_at' => 'datetime',
    ];

    // Relations
    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }

    // Accessors
    public function getValidationStatusLabelAttribute()
    {
        return match($this->validation_status) {
            'satisfied' => 'Satisfait',
            'partially_satisfied' => 'Partiellement satisfait',
            'not_satisfied' => 'Non satisfait',
            default => 'Inconnu'
        };
    }

    // Méthodes statiques
    public static function createFromValidation($feedback, $validationData, $request)
    {
        return static::create([
            'feedback_id' => $feedback->id,
            'token' => $feedback->validation_token,
            'client_ip' => $request->ip(),
            'client_user_agent' => $request->header('User-Agent'),
            'validation_status' => $validationData['status'],
            'satisfaction_rating' => $validationData['rating'] ?? null,
            'comment' => $validationData['comment'] ?? null,
            'bonus_points_awarded' => $validationData['bonus_points'] ?? 0,
            'validated_at' => now(),
        ]);
    }
}