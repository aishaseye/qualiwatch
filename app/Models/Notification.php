<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'recipient_id',
        'recipient_type',
        'type',
        'title',
        'message',
        'data',
        'channel',
        'status',
        'scheduled_at',
        'sent_at',
        'read_at',
        'retry_count',
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function recipient()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')
                     ->where('scheduled_at', '<=', now())
                     ->where('status', 'pending');
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getIsReadAttribute()
    {
        return !is_null($this->read_at);
    }

    public function getIsSentAttribute()
    {
        return in_array($this->status, ['sent', 'delivered']);
    }

    public function getIsFailedAttribute()
    {
        return $this->status === 'failed';
    }

    public function getCanRetryAttribute()
    {
        return $this->is_failed && $this->retry_count < 3;
    }

    // Methods
    public function markAsRead()
    {
        $this->update([
            'read_at' => now(),
            'status' => 'read'
        ]);
    }

    public function markAsSent()
    {
        $this->update([
            'sent_at' => now(),
            'status' => 'sent'
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered'
        ]);
    }

    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1
        ]);
    }

    public function retry()
    {
        if ($this->can_retry) {
            $this->update([
                'status' => 'pending',
                'error_message' => null
            ]);
        }
    }

    // Static methods for creating notifications
    public static function createForUser($companyId, $userId, $type, $title, $message, $data = [], $channel = 'in_app')
    {
        return static::create([
            'company_id' => $companyId,
            'recipient_id' => $userId,
            'recipient_type' => 'App\Models\User',
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channel' => $channel
        ]);
    }

    public static function createForClient($companyId, $clientId, $type, $title, $message, $data = [], $channel = 'email')
    {
        return static::create([
            'company_id' => $companyId,
            'recipient_id' => $clientId,
            'recipient_type' => 'App\Models\Client',
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channel' => $channel
        ]);
    }

    public static function createBroadcast($companyId, $type, $title, $message, $data = [], $channel = 'in_app')
    {
        return static::create([
            'company_id' => $companyId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channel' => $channel
        ]);
    }
}