<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserOtp extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'otp',
        'type',
        'expires_at',
        'verified_at',
        'is_used',
        'temp_first_name',
        'temp_last_name',
        'temp_phone',
        'temp_password_hash',
        'temp_role'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Méthodes utilitaires
    public function isExpired()
    {
        return Carbon::now()->isAfter($this->expires_at);
    }

    public function isValid()
    {
        return !$this->is_used && !$this->isExpired();
    }

    // Générer un OTP
    public static function generateOtp()
    {
        return str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    // Créer un nouvel OTP avec données temporaires
    public static function createOtpWithTempData($email, $tempData, $type = 'registration')
    {
        // Supprimer les anciens OTP non utilisés pour cet email
        self::where('email', $email)
            ->where('type', $type)
            ->where('is_used', false)
            ->delete();

        $otp = self::generateOtp();
        
        return self::create([
            'user_id' => null, // Pas encore créé
            'email' => $email,
            'otp' => $otp,
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes(10),
            'temp_first_name' => $tempData['first_name'] ?? null,
            'temp_last_name' => $tempData['last_name'] ?? null,
            'temp_phone' => $tempData['phone'] ?? null,
            'temp_password_hash' => $tempData['password_hash'] ?? null,
            'temp_role' => $tempData['role'] ?? 'manager',
        ]);
    }

    // Créer un nouvel OTP (méthode existante pour compatibilité)
    public static function createOtp($userId, $email, $type = 'registration')
    {
        // Supprimer les anciens OTP non utilisés
        if ($userId) {
            self::where('user_id', $userId)
                ->where('type', $type)
                ->where('is_used', false)
                ->delete();
        } else {
            self::where('email', $email)
                ->where('type', $type)
                ->where('is_used', false)
                ->delete();
        }

        $otp = self::generateOtp();
        
        return self::create([
            'user_id' => $userId,
            'email' => $email,
            'otp' => $otp,
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes(10), // OTP valide 10 minutes
        ]);
    }

    // Vérifier un OTP
    public static function verifyOtp($email, $otp, $type = 'registration')
    {
        $otpRecord = self::where('email', $email)
            ->where('otp', $otp)
            ->where('type', $type)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($otpRecord) {
            $otpRecord->update([
                'is_used' => true,
                'verified_at' => Carbon::now()
            ]);
            return $otpRecord;
        }

        return null;
    }
}