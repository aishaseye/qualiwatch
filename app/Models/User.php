<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'company_id',
        'profile_photo',
        'otp',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relations
    public function company()
    {
        return $this->hasOne(Company::class, 'manager_id');
    }

    public function treatedFeedbacks()
    {
        return $this->hasMany(Feedback::class, 'treated_by_user_id');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'recipient');
    }

    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo ? 
            asset('storage/profiles/' . $this->profile_photo) : 
            asset('images/default-avatar.png');
    }
}