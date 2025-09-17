<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'channel',
        'subject',
        'title_template',
        'message_template',
        'variables',
        'settings',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'variables' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('company_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Methods
    public function render($variables = [])
    {
        $title = $this->renderTemplate($this->title_template, $variables);
        $message = $this->renderTemplate($this->message_template, $variables);
        $subject = $this->subject ? $this->renderTemplate($this->subject, $variables) : null;

        return [
            'title' => $title,
            'message' => $message,
            'subject' => $subject,
        ];
    }

    private function renderTemplate($template, $variables = [])
    {
        $rendered = $template;
        
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $rendered = str_replace($placeholder, $value, $rendered);
        }

        return $rendered;
    }

    public static function findTemplate($companyId, $type, $channel)
    {
        // Try to find company-specific template first
        $template = static::active()
            ->forCompany($companyId)
            ->byType($type)
            ->byChannel($channel)
            ->first();

        // If not found, try global default template
        if (!$template) {
            $template = static::active()
                ->global()
                ->byType($type)
                ->byChannel($channel)
                ->default()
                ->first();
        }

        // If still not found, try any global template for this type/channel
        if (!$template) {
            $template = static::active()
                ->global()
                ->byType($type)
                ->byChannel($channel)
                ->first();
        }

        return $template;
    }

    public static function createDefaultTemplates()
    {
        $templates = [
            // Feedback notifications
            [
                'name' => 'Nouveau Feedback',
                'type' => 'feedback',
                'channel' => 'email',
                'subject' => 'Nouveau feedback reçu - {{company_name}}',
                'title_template' => 'Nouveau feedback de {{client_name}}',
                'message_template' => 'Un nouveau feedback {{feedback_type}} a été reçu de {{client_name}} avec une note de {{rating}}/5.',
                'variables' => ['company_name', 'client_name', 'feedback_type', 'rating', 'message'],
                'is_default' => true,
            ],
            [
                'name' => 'Nouveau Feedback',
                'type' => 'feedback',
                'channel' => 'in_app',
                'title_template' => 'Nouveau feedback',
                'message_template' => '{{client_name}} a laissé un feedback {{feedback_type}}',
                'variables' => ['client_name', 'feedback_type', 'rating'],
                'is_default' => true,
            ],
            
            // Reward notifications
            [
                'name' => 'Récompense Réclamée',
                'type' => 'reward',
                'channel' => 'email',
                'subject' => 'Récompense réclamée - {{reward_name}}',
                'title_template' => 'Félicitations !',
                'message_template' => 'Vous avez réclamé avec succès la récompense "{{reward_name}}" pour {{kalipoints_cost}} KaliPoints. Code de réclamation: {{claim_code}}',
                'variables' => ['reward_name', 'kalipoints_cost', 'claim_code', 'client_name'],
                'is_default' => true,
            ],
            [
                'name' => 'Récompense Approuvée',
                'type' => 'reward',
                'channel' => 'email',
                'subject' => 'Récompense approuvée - {{reward_name}}',
                'title_template' => 'Récompense approuvée',
                'message_template' => 'Votre réclamation pour "{{reward_name}}" a été approuvée. Code: {{claim_code}}',
                'variables' => ['reward_name', 'claim_code', 'client_name'],
                'is_default' => true,
            ],

            // System notifications
            [
                'name' => 'Notification Système',
                'type' => 'system',
                'channel' => 'in_app',
                'title_template' => '{{title}}',
                'message_template' => '{{message}}',
                'variables' => ['title', 'message'],
                'is_default' => true,
            ],

            // Milestone notifications
            [
                'name' => 'Badge Obtenu',
                'type' => 'milestone',
                'channel' => 'email',
                'subject' => 'Nouveau badge obtenu !',
                'title_template' => 'Félicitations !',
                'message_template' => 'Vous avez obtenu le badge "{{badge_name}}" ! {{badge_description}}',
                'variables' => ['badge_name', 'badge_description', 'client_name'],
                'is_default' => true,
            ],
        ];

        foreach ($templates as $template) {
            static::create($template);
        }
    }
}