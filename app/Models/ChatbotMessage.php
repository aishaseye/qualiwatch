<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotMessage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'message',
        'attachments',
        'metadata',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relations
    public function conversation()
    {
        return $this->belongsTo(ChatbotConversation::class);
    }

    // Scopes
    public function scopeBySender($query, $senderType)
    {
        return $query->where('sender_type', $senderType);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}