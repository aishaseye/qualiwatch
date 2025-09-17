<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\Feedback;
use App\Models\FeedbackAlert;
use App\Models\RewardClaim;
use App\Models\Client;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendFeedbackNotification(Feedback $feedback)
    {
        try {
            Log::info('=== sendFeedbackNotification called ===', [
                'feedback_id' => $feedback->id,
                'feedback_type' => $feedback->type,
                'feedback_type_raw' => json_encode($feedback->type),
                'strtolower_type' => strtolower($feedback->type),
                'call_stack' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ]);

            $company = $feedback->company;
            if (!$company) return;

            // Send email to company manager using custom template (seulement à la création)
            // UNIQUEMENT pour le manager - PAS pour le client
            if ($company->manager && $company->manager->email && $feedback->client && $feedback->client->email && $company->manager->email !== $feedback->client->email) {
                Mail::to($company->manager->email)
                    ->send(new \App\Mail\FeedbackNotificationMail($feedback));

                Log::info('Feedback notification email sent to manager ONLY', [
                    'company' => $company->name,
                    'manager_email' => $company->manager->email,
                    'client_email' => $feedback->client->email,
                    'feedback_rating' => $feedback->rating,
                ]);
            }

            // Send in-app notification to company manager (legacy format for dashboard)
            $variables = [
                'company_name' => $company->name,
                'client_name' => $feedback->client?->full_name ?? 'Client Anonyme',
                'feedback_type' => $this->getFeedbackTypeLabel($feedback->type),
                'rating' => $feedback->rating ?? 'N/A',
                'message' => $feedback->message ?? '',
                'feedback_id' => $feedback->id,
            ];

            if ($company->manager) {
                $this->sendNotification(
                    $company->id,
                    $company->manager->id,
                    'App\Models\User',
                    'feedback',
                    'in_app',
                    $variables
                );
            }

            // Send apology email to client for ALL negative feedbacks (negatif/incident types)
            Log::info('Checking apology email conditions', [
                'feedback_type' => $feedback->type,
                'client_exists' => $feedback->client ? 'yes' : 'no',
                'client_email' => $feedback->client?->email ?? 'none',
                'condition_result' => ((strtolower($feedback->type) === 'negatif' || strtolower($feedback->type) === 'incident') && $feedback->client && $feedback->client->email) ? 'SEND' : 'SKIP'
            ]);
            
            if ((strtolower($feedback->type) === 'negatif' || strtolower($feedback->type) === 'incident') && $feedback->client && $feedback->client->email) {
                try {
                    Mail::to($feedback->client->email)
                        ->send(new \App\Mail\ClientApologyMail($feedback));
                    
                    Log::info('Apology email sent to client for negative feedback', [
                        'company' => $company->name,
                        'client_email' => $feedback->client->email,
                        'feedback_type' => $feedback->type,
                        'feedback_rating' => $feedback->rating,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send apology email', [
                        'error' => $e->getMessage(),
                        'client_email' => $feedback->client->email,
                        'feedback_type' => $feedback->type,
                    ]);
                }
            }

            // Send thank you email to client for suggestions
            if ($feedback->type === 'suggestion' && $feedback->client && $feedback->client->email) {
                try {
                    Mail::to($feedback->client->email)
                        ->send(new \App\Mail\SuggestionThankYouMail($feedback));

                    Log::info('SUCCESS: Thank you email sent to client for suggestion', [
                        'company' => $company->name,
                        'client_email' => $feedback->client->email,
                        'feedback_type' => $feedback->type,
                        'feedback_rating' => $feedback->rating,
                    ]);
                } catch (\Exception $e) {
                    Log::error('ERROR: Failed to send suggestion thank you email', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'client_email' => $feedback->client->email,
                        'feedback_type' => $feedback->type,
                    ]);
                }
            }

            // If feedback is negative (incident), create escalation notifications
            if ($feedback->type === 'incident' && $feedback->rating <= 2) {
                $this->createEscalationNotification($feedback);
            }

        } catch (\Exception $e) {
            Log::error('Error sending feedback notification: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer notification quand feedback passe à "treated" - SEULEMENT AU CLIENT
     */
    public function sendTreatedNotification(Feedback $feedback)
    {
        try {
            // SEULEMENT envoyer au client, PAS au manager
            if (!$feedback->client || !$feedback->client->email) {
                Log::warning('Cannot send treated notification: no client email', [
                    'feedback_id' => $feedback->id
                ]);
                return;
            }

            // Générer le token de validation SANS déclencher les événements
            $token = $feedback->withoutEvents(function () use ($feedback) {
                return $feedback->generateValidationToken();
            });
            
            // Récupérer les IDs des statuts resolved et not_resolved
            $resolvedStatus = \App\Models\FeedbackStatus::getResolvedStatus();
            $notResolvedStatus = \App\Models\FeedbackStatus::getNotResolvedStatus();
            
            // Créer les URLs de validation directes avec les statuts
            $resolvedUrl = config('app.frontend_url') . '/api/validate/' . $token . '?status_id=' . $resolvedStatus->id;
            $notResolvedUrl = config('app.frontend_url') . '/api/validate/' . $token . '?status_id=' . $notResolvedStatus->id;

            // Envoyer email de validation avec deux boutons
            $emailSent = $this->sendTwoButtonValidationRequest($feedback, $resolvedUrl, $notResolvedUrl);

            if ($emailSent) {
                Log::info('Treated notification with two-button validation sent to client', [
                    'feedback_id' => $feedback->id,
                    'client_email' => $feedback->client->email,
                    'validation_token' => $token,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error sending treated notification: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer email de validation avec deux boutons directs (Résolu/Non résolu)
     */
    public function sendTwoButtonValidationRequest(Feedback $feedback, string $resolvedUrl, string $notResolvedUrl)
    {
        try {
            if (!$feedback->client || !$feedback->client->email) {
                \Log::error('Cannot send two-button validation request: no client email');
                return false;
            }

            $company = $feedback->company;
            if (!$company) return false;

            $variables = [
                'client_name' => $feedback->client->full_name,
                'client_email' => $feedback->client->email,
                'feedback_reference' => $feedback->reference,
                'feedback_title' => $feedback->title,
                'feedback_description' => $feedback->description,
                'feedback_type' => ucfirst($feedback->type),
                'admin_resolution' => $feedback->admin_resolution_description ?? 'Le problème a été traité par notre équipe.',
                'company_name' => $company->name,
                'resolved_url' => $resolvedUrl,
                'not_resolved_url' => $notResolvedUrl,
                'expires_at' => $feedback->validation_expires_at->format('d/m/Y à H:i'),
                // Variables pour compatibilité template
                'isReminder' => false,
                'hoursRemaining' => 48,
            ];

            // Créer la notification
            $notification = Notification::create([
                'company_id' => $company->id,
                'recipient_id' => $feedback->client->id,
                'recipient_type' => 'App\Models\Client',
                'recipient_email' => $feedback->client->email,
                'type' => 'feedback',
                'channel' => 'email',
                'title' => "Votre feedback a été traité - {$variables['feedback_reference']}",
                'message' => "Merci de nous indiquer si le problème a été résolu",
                'data' => $variables,
                'scheduled_at' => now(),
            ]);

            // Envoyer directement l'email avec le template de validation
            $emailSent = $this->sendValidationEmailWithButtons($feedback->client->email, $variables);

            if ($emailSent) {
                $notification->update([
                    'sent_at' => now(),
                    'status' => 'sent'
                ]);
            } else {
                $notification->update([
                    'status' => 'failed',
                    'error_message' => 'Failed to send email'
                ]);
            }

            return $emailSent;

        } catch (\Exception $e) {
            \Log::error('Error sending two-button validation request: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer email avec template de validation à deux boutons
     */
    private function sendValidationEmailWithButtons(string $recipientEmail, array $variables)
    {
        try {
            Mail::send('emails.feedback-validation', $variables, function ($message) use ($recipientEmail, $variables) {
                $message->to($recipientEmail)
                       ->subject("Votre feedback a été traité - {$variables['feedback_reference']}")
                       ->from(config('mail.from.address'), config('mail.from.name', 'QualyWatch'));
            });

            return true;

        } catch (\Exception $e) {
            \Log::error('Error sending validation email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendRewardClaimNotification(RewardClaim $claim)
    {
        try {
            $variables = [
                'client_name' => $claim->client?->full_name ?? 'Client',
                'reward_name' => $claim->reward?->name ?? 'Récompense',
                'kalipoints_cost' => $claim->kalipoints_spent,
                'claim_code' => $claim->claim_code,
                'company_name' => $claim->company?->name ?? 'Entreprise',
            ];

            // Send email to client
            if ($claim->client && $claim->client->email) {
                $this->sendNotification(
                    $claim->company_id,
                    $claim->client_id,
                    'App\Models\Client',
                    'reward',
                    'email',
                    $variables
                );
            }

            // Send in-app notification to company manager about new claim
            if ($claim->company->manager) {
                $this->sendNotification(
                    $claim->company_id,
                    $claim->company->manager->id,
                    'App\Models\User',
                    'reward',
                    'in_app',
                    array_merge($variables, [
                        'title' => 'Nouvelle réclamation de récompense',
                        'message' => "{$variables['client_name']} a réclamé la récompense \"{$variables['reward_name']}\""
                    ])
                );
            }

        } catch (\Exception $e) {
            Log::error('Error sending reward claim notification: ' . $e->getMessage());
        }
    }

    public function sendRewardStatusNotification(RewardClaim $claim, string $newStatus)
    {
        try {
            if (!$claim->client || !$claim->client->email) return;

            $variables = [
                'client_name' => $claim->client->full_name,
                'reward_name' => $claim->reward->name,
                'claim_code' => $claim->claim_code,
                'status' => $this->getClaimStatusLabel($newStatus),
                'company_name' => $claim->company->name,
            ];

            $type = 'reward';
            $title = '';
            $message = '';

            switch ($newStatus) {
                case 'approved':
                    $title = 'Récompense approuvée';
                    $message = "Votre réclamation pour \"{$variables['reward_name']}\" a été approuvée.";
                    break;
                case 'delivered':
                    $title = 'Récompense livrée';
                    $message = "Votre récompense \"{$variables['reward_name']}\" a été livrée.";
                    break;
                case 'cancelled':
                    $title = 'Réclamation annulée';
                    $message = "Votre réclamation pour \"{$variables['reward_name']}\" a été annulée.";
                    break;
            }

            $variables['title'] = $title;
            $variables['message'] = $message;

            $this->sendNotification(
                $claim->company_id,
                $claim->client_id,
                'App\Models\Client',
                $type,
                'email',
                $variables
            );

        } catch (\Exception $e) {
            Log::error('Error sending reward status notification: ' . $e->getMessage());
        }
    }

    public function sendMilestoneNotification($clientId, $companyId, $badgeName, $badgeDescription)
    {
        try {
            $client = Client::find($clientId);
            if (!$client || !$client->email) return;

            $variables = [
                'client_name' => $client->full_name,
                'badge_name' => $badgeName,
                'badge_description' => $badgeDescription,
            ];

            $this->sendNotification(
                $companyId,
                $clientId,
                'App\Models\Client',
                'milestone',
                'email',
                $variables
            );

        } catch (\Exception $e) {
            Log::error('Error sending milestone notification: ' . $e->getMessage());
        }
    }

    public function sendSystemNotification($companyId, $recipientId, $recipientType, $title, $message, $channel = 'in_app')
    {
        try {
            $variables = [
                'title' => $title,
                'message' => $message,
            ];

            $this->sendNotification(
                $companyId,
                $recipientId,
                $recipientType,
                'system',
                $channel,
                $variables
            );

        } catch (\Exception $e) {
            Log::error('Error sending system notification: ' . $e->getMessage());
        }
    }

    private function sendNotification($companyId, $recipientId, $recipientType, $type, $channel, $variables = [])
    {
        try {
            // Find appropriate template
            $template = NotificationTemplate::findTemplate($companyId, $type, $channel);
            
            if (!$template) {
                Log::warning("No template found for company {$companyId}, type {$type}, channel {$channel}");
                return;
            }

            // Render template with variables
            $rendered = $template->render($variables);

            // Create notification
            $notification = Notification::create([
                'company_id' => $companyId,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'type' => $type,
                'title' => $rendered['title'],
                'message' => $rendered['message'],
                'data' => $variables,
                'channel' => $channel,
                'status' => 'pending',
            ]);

            // Process notification based on channel
            $this->processNotification($notification, $rendered);

        } catch (\Exception $e) {
            Log::error('Error creating notification: ' . $e->getMessage());
        }
    }

    private function processNotification(Notification $notification, array $rendered)
    {
        try {
            switch ($notification->channel) {
                case 'email':
                    $this->sendEmailNotification($notification, $rendered);
                    break;
                case 'sms':
                    $this->sendSmsNotification($notification);
                    break;
                case 'in_app':
                    // In-app notifications are already created, just mark as sent
                    $notification->markAsSent();
                    break;
                case 'push':
                    $this->sendPushNotification($notification);
                    break;
                case 'webhook':
                    $this->sendWebhookNotification($notification);
                    break;
            }
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            Log::error('Error processing notification: ' . $e->getMessage());
        }
    }

    private function sendEmailNotification(Notification $notification, array $rendered)
    {
        try {
            $recipient = $notification->recipient;
            if (!$recipient || !$recipient->email) {
                throw new \Exception('No email address found for recipient');
            }

            // Envoyer l'email avec Laravel Mail
            $recipientEmail = $notification->recipient_email ?? $recipient->email ?? null;
            
            if (!$recipientEmail) {
                throw new \Exception('No email address found for recipient');
            }
            
            Mail::send([], [], function ($message) use ($recipientEmail, $rendered, $notification) {
                $message->to($recipientEmail)
                       ->subject($rendered['subject'] ?? $rendered['title'] ?? 'Notification QualyWatch')
                       ->html($rendered['content'] ?? $rendered['message'] ?? '');
            });
            
            Log::info('Email notification sent successfully', [
                'to' => $recipientEmail,
                'subject' => $rendered['subject'] ?? $rendered['title'],
            ]);

            $notification->markAsSent();
            
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function sendSmsNotification(Notification $notification)
    {
        try {
            $recipient = $notification->recipient;
            if (!$recipient || !$recipient->phone) {
                throw new \Exception('No phone number found for recipient');
            }

            // Here you would integrate with your SMS service
            Log::info('Sending SMS notification', [
                'to' => $recipient->phone,
                'message' => $notification->message,
            ]);

            $notification->markAsSent();
            
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function sendPushNotification(Notification $notification)
    {
        try {
            // Here you would integrate with your push notification service
            Log::info('Sending push notification', [
                'recipient_id' => $notification->recipient_id,
                'title' => $notification->title,
                'message' => $notification->message,
            ]);

            $notification->markAsSent();
            
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function sendWebhookNotification(Notification $notification)
    {
        try {
            // Here you would send webhook to external systems
            Log::info('Sending webhook notification', [
                'notification_id' => $notification->id,
                'data' => $notification->data,
            ]);

            $notification->markAsSent();
            
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function createEscalationNotification(Feedback $feedback)
    {
        // This will be expanded when we implement the SLA/Escalation system
        $this->sendSystemNotification(
            $feedback->company_id,
            $feedback->company->manager->id,
            'App\Models\User',
            'Feedback négatif nécessite attention',
            "Un feedback incident avec note {$feedback->rating}/5 nécessite une attention urgente.",
            'in_app'
        );
    }

    private function getFeedbackTypeLabel($type)
    {
        return match($type) {
            'appreciation' => 'd\'appréciation',
            'incident' => 'incident',
            'suggestion' => 'de suggestion',
            default => $type
        };
    }

    private function getClaimStatusLabel($status)
    {
        return match($status) {
            'pending' => 'en attente',
            'approved' => 'approuvé',
            'delivered' => 'livré',
            'cancelled' => 'annulé',
            default => $status
        };
    }

    public function processScheduledNotifications()
    {
        $notifications = Notification::scheduled()->limit(100)->get();

        foreach ($notifications as $notification) {
            try {
                $template = NotificationTemplate::findTemplate(
                    $notification->company_id,
                    $notification->type,
                    $notification->channel
                );

                if ($template) {
                    $rendered = $template->render($notification->data ?? []);
                    $this->processNotification($notification, $rendered);
                }
            } catch (\Exception $e) {
                $notification->markAsFailed($e->getMessage());
                Log::error('Error processing scheduled notification: ' . $e->getMessage());
            }
        }
    }

    public function retryFailedNotifications()
    {
        $notifications = Notification::failed()
            ->where('retry_count', '<', 3)
            ->limit(50)
            ->get();

        foreach ($notifications as $notification) {
            if ($notification->can_retry) {
                $notification->retry();
                
                $template = NotificationTemplate::findTemplate(
                    $notification->company_id,
                    $notification->type,
                    $notification->channel
                );

                if ($template) {
                    $rendered = $template->render($notification->data ?? []);
                    $this->processNotification($notification, $rendered);
                }
            }
        }
    }

    public function sendFeedbackAlert(FeedbackAlert $alert)
    {
        try {
            $company = $alert->company;
            if (!$company) return;

            $companyUsers = User::where('company_id', $company->id)
                ->where('role', 'manager')
                ->get();

            $variables = [
                'alert_id' => $alert->id,
                'feedback_id' => $alert->feedback_id,
                'severity' => ucfirst($alert->severity),
                'alert_type' => $this->formatAlertType($alert->alert_type),
                'alert_reason' => $alert->alert_reason,
                'detected_keywords' => $alert->formatted_detected_keywords,
                'client_name' => $alert->feedback->client?->full_name ?? 'Client anonyme',
                'feedback_title' => $alert->feedback->title ?? 'Sans titre',
                'feedback_description' => substr($alert->feedback->description ?? '', 0, 200),
                'company_name' => $company->name,
                'dashboard_url' => config('app.frontend_url') . '/feedback-alerts/' . $alert->id,
            ];

            foreach ($companyUsers as $user) {
                $notification = Notification::create([
                    'company_id' => $company->id,
                    'recipient_id' => $user->id,
                    'recipient_type' => 'user',
                    'type' => 'feedback_alert',
                    'channel' => $alert->is_high_priority ? 'email' : 'in_app',
                    'data' => $variables,
                    'scheduled_at' => now(),
                ]);

                $template = NotificationTemplate::findTemplate(
                    $company->id,
                    'feedback_alert',
                    $notification->channel
                );

                if ($template) {
                    $rendered = $template->render($variables);
                    $this->processNotification($notification, $rendered);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error sending feedback alert notification: ' . $e->getMessage());
        }
    }

    public function sendFeedbackAlertUpdate(FeedbackAlert $alert, string $action)
    {
        try {
            if (!$alert->acknowledgedBy) return;

            $variables = [
                'alert_id' => $alert->id,
                'action' => $this->formatAlertAction($action),
                'severity' => ucfirst($alert->severity),
                'handled_by' => $alert->acknowledgedBy->full_name ?? 'Utilisateur',
                'resolution_notes' => $alert->resolution_notes ?? '',
                'feedback_title' => $alert->feedback->title ?? 'Sans titre',
                'company_name' => $alert->company->name,
            ];

            $notification = Notification::create([
                'company_id' => $alert->company_id,
                'recipient_id' => $alert->acknowledgedBy->id,
                'recipient_type' => 'user',
                'type' => 'feedback_alert_update',
                'channel' => 'in_app',
                'data' => $variables,
                'scheduled_at' => now(),
            ]);

            $template = NotificationTemplate::findTemplate(
                $alert->company_id,
                'feedback_alert_update',
                'in_app'
            );

            if ($template) {
                $rendered = $template->render($variables);
                $this->processNotification($notification, $rendered);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending feedback alert update notification: ' . $e->getMessage());
        }
    }

    private function formatAlertType(string $type): string
    {
        return match($type) {
            'negative_sentiment' => 'Sentiment négatif',
            'critical_keywords' => 'Mots-clés critiques',
            'low_rating' => 'Note très faible',
            'multiple_issues' => 'Problèmes multiples',
            'vip_client' => 'Client VIP',
            default => 'Alerte'
        };
    }

    private function formatAlertAction(string $action): string
    {
        return match($action) {
            'acknowledged' => 'prise en charge',
            'in_progress' => 'mise en traitement',
            'resolved' => 'résolue',
            'dismissed' => 'rejetée',
            'escalated' => 'escaladée',
            default => $action
        };
    }

    public function sendValidationRequest(Feedback $feedback, string $validationUrl)
    {
        try {
            if (!$feedback->client || !$feedback->client->email) {
                \Log::error('Cannot send validation request: no client email');
                return false;
            }

            $company = $feedback->company;
            if (!$company) return false;

            $variables = [
                'client_name' => $feedback->client->full_name,
                'client_email' => $feedback->client->email,
                'feedback_reference' => $feedback->reference,
                'feedback_title' => $feedback->title,
                'feedback_description' => $feedback->description,
                'feedback_type' => ucfirst($feedback->type),
                'admin_resolution' => $feedback->admin_resolution_description ?? 'Le problème a été traité par notre équipe.',
                'company_name' => $company->name,
                'validation_url' => $validationUrl,
                'expires_at' => $feedback->validation_expires_at->format('d/m/Y à H:i'),
                'validation_instructions' => 'Cliquez sur le lien ci-dessus pour nous indiquer si le problème a été résolu à votre satisfaction.',
            ];

            // Créer la notification
            $notification = Notification::create([
                'company_id' => $company->id,
                'recipient_id' => $feedback->client->id,
                'recipient_type' => 'App\Models\Client',
                'recipient_email' => $feedback->client->email,
                'type' => 'feedback',
                'channel' => 'email',
                'title' => "Validation requise - {$variables['feedback_reference']}",
                'message' => "Votre feedback nécessite une validation",
                'data' => $variables,
                'scheduled_at' => now(),
            ]);

            // Chercher le template de validation
            $template = NotificationTemplate::findTemplate(
                $company->id,
                'feedback',
                'email'
            );

            if ($template) {
                $rendered = $template->render($variables);
                $this->processNotification($notification, $rendered);
            } else {
                // Template par défaut si pas trouvé
                $defaultTemplate = $this->getDefaultValidationTemplate();
                $rendered = $defaultTemplate->render($variables);
                $this->processNotification($notification, $rendered);
            }

            return true;

        } catch (\Exception $e) {
            \Log::error('Error sending validation request: ' . $e->getMessage());
            return false;
        }
    }

    private function getDefaultValidationTemplate()
    {
        // Template par défaut pour les demandes de validation
        return (object) [
            'render' => function($variables) {
                return [
                    'subject' => "Validation requise - {$variables['feedback_reference']} - {$variables['company_name']}",
                    'content' => "
Bonjour {$variables['client_name']},

Votre feedback \"{$variables['feedback_title']}\" a été traité par notre équipe.

DÉTAILS DE VOTRE FEEDBACK :
- Référence : {$variables['feedback_reference']}
- Type : {$variables['feedback_type']}
- Description : {$variables['feedback_description']}

RÉSOLUTION APPORTÉE :
{$variables['admin_resolution']}

VOTRE AVIS EST IMPORTANT !
Nous aimerions savoir si cette résolution vous satisfait.
Cliquez sur le lien ci-dessous pour nous donner votre retour :

👉 {$variables['validation_url']}

{$variables['validation_instructions']}

⚠️ Ce lien expire le {$variables['expires_at']}

Merci de votre confiance,
L'équipe {$variables['company_name']}
                    "
                ];
            }
        ];
    }
}