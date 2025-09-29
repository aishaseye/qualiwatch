<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\FeedbackStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $apiUrl;
    private $accessToken;
    private $phoneNumberId;
    private $enabled;

    public function __construct()
    {
        $this->apiUrl = config('whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->accessToken = config('whatsapp.access_token');
        $this->phoneNumberId = config('whatsapp.phone_number_id');
        $this->enabled = config('whatsapp.enabled', false);
    }

    /**
     * Envoyer un message WhatsApp pour un feedback traité
     */
    public function sendTreatedFeedbackMessage(Feedback $feedback)
    {
        try {
            if (!$this->enabled) {
                Log::info('WhatsApp service disabled, skipping message');
                return false;
            }

            if (!$feedback->client || !$feedback->client->phone) {
                Log::warning('Cannot send WhatsApp message: no client phone number', [
                    'feedback_id' => $feedback->id
                ]);
                return false;
            }

            // Nettoyer le numéro de téléphone
            $phoneNumber = $this->formatPhoneNumber($feedback->client->phone);

            if (!$phoneNumber) {
                Log::warning('Invalid phone number format', [
                    'feedback_id' => $feedback->id,
                    'phone' => $feedback->client->phone
                ]);
                return false;
            }

            // Générer le token de validation SANS déclencher les événements
            $token = $feedback->withoutEvents(function () use ($feedback) {
                return $feedback->generateValidationToken();
            });

            // Récupérer les IDs des statuts resolved et not_resolved
            $resolvedStatus = FeedbackStatus::getResolvedStatus();
            $notResolvedStatus = FeedbackStatus::getNotResolvedStatus();

            // Créer les URLs de validation directes avec les statuts
            $resolvedUrl = config('app.frontend_url') . '/api/validate/' . $token . '?status_id=' . $resolvedStatus->id;
            $notResolvedUrl = config('app.frontend_url') . '/api/validate/' . $token . '?status_id=' . $notResolvedStatus->id;

            // Créer le message WhatsApp
            $message = $this->createTreatedFeedbackMessage($feedback, $resolvedUrl, $notResolvedUrl);

            // Envoyer le message
            $response = $this->sendMessage($phoneNumber, $message);

            if ($response['success']) {
                Log::info('WhatsApp message sent successfully for treated feedback', [
                    'feedback_id' => $feedback->id,
                    'client_phone' => $phoneNumber,
                    'message_id' => $response['message_id'] ?? null
                ]);
                return true;
            } else {
                Log::error('Failed to send WhatsApp message for treated feedback', [
                    'feedback_id' => $feedback->id,
                    'client_phone' => $phoneNumber,
                    'error' => $response['error'] ?? 'Unknown error'
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp treated feedback message: ' . $e->getMessage(), [
                'feedback_id' => $feedback->id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Créer le message pour un feedback traité
     */
    private function createTreatedFeedbackMessage(Feedback $feedback, string $resolvedUrl, string $notResolvedUrl)
    {
        $clientName = $feedback->client->full_name;
        $companyName = $feedback->company->name;
        $feedbackRef = $feedback->reference;
        $feedbackTitle = $feedback->title ?? 'Votre demande';

        $message = "🎯 *{$companyName}*\n\n";
        $message .= "Bonjour {$clientName},\n\n";
        $message .= "✅ Bonne nouvelle ! Votre feedback *{$feedbackRef}* concernant \"{$feedbackTitle}\" a été traité par notre équipe.\n\n";

        if ($feedback->admin_resolution_description) {
            $message .= "📋 *Résolution apportée :*\n";
            $message .= $feedback->admin_resolution_description . "\n\n";
        }

        $message .= "💬 *Votre avis nous intéresse !*\n";
        $message .= "Pouvez-vous nous confirmer si le problème a été résolu ?\n\n";

        $message .= "👆 Cliquez sur l'un des liens ci-dessous :\n\n";
        $message .= "✅ *PROBLÈME RÉSOLU* :\n";
        $message .= $resolvedUrl . "\n\n";
        $message .= "❌ *PROBLÈME NON RÉSOLU* :\n";
        $message .= $notResolvedUrl . "\n\n";

        $message .= "⏰ *Important :* Ce lien expire dans 48h\n\n";
        $message .= "Merci de votre confiance ! 🙏\n";
        $message .= "_L'équipe {$companyName}_";

        return $message;
    }

    /**
     * Envoyer un message WhatsApp via l'API Facebook
     */
    private function sendMessage(string $phoneNumber, string $message)
    {
        try {
            if (!$this->accessToken || !$this->phoneNumberId) {
                return [
                    'success' => false,
                    'error' => 'WhatsApp API credentials not configured'
                ];
            }

            $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

            $response = Http::withToken($this->accessToken)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $phoneNumber,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => true,
                        'body' => $message
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                    'response' => $data
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->json()['error']['message'] ?? 'API request failed',
                    'status_code' => $response->status(),
                    'response' => $response->json()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Formater le numéro de téléphone pour WhatsApp
     */
    private function formatPhoneNumber(string $phone)
    {
        // Supprimer tous les caractères non numériques sauf le +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Si le numéro commence par +, le garder tel quel
        if (str_starts_with($cleaned, '+')) {
            return $cleaned;
        }

        // Si le numéro commence par 0 (format français), remplacer par +33
        if (str_starts_with($cleaned, '0')) {
            return '+33' . substr($cleaned, 1);
        }

        // Si le numéro ne commence pas par +, ajouter +33 (par défaut France)
        if (!str_starts_with($cleaned, '+')) {
            return '+33' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Vérifier si le service WhatsApp est activé et configuré
     */
    public function isEnabled()
    {
        return $this->enabled && $this->accessToken && $this->phoneNumberId;
    }

    /**
     * Tester la connexion WhatsApp
     */
    public function testConnection()
    {
        try {
            if (!$this->isEnabled()) {
                return [
                    'success' => false,
                    'error' => 'WhatsApp service not enabled or not configured'
                ];
            }

            $url = "{$this->apiUrl}/{$this->phoneNumberId}";

            $response = Http::withToken($this->accessToken)->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'WhatsApp API connection successful',
                    'phone_number_info' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to connect to WhatsApp API',
                    'response' => $response->json()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}