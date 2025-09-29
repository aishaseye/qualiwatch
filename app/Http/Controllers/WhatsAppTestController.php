<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use App\Models\Feedback;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WhatsAppTestController extends Controller
{
    private $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Tester la connexion WhatsApp
     */
    public function testConnection(): JsonResponse
    {
        try {
            $result = $this->whatsappService->testConnection();

            return response()->json([
                'success' => true,
                'message' => 'Test de connexion WhatsApp effectué',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connexion WhatsApp',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier si le service WhatsApp est activé
     */
    public function checkStatus(): JsonResponse
    {
        try {
            $isEnabled = $this->whatsappService->isEnabled();

            return response()->json([
                'success' => true,
                'data' => [
                    'whatsapp_enabled' => $isEnabled,
                    'config' => [
                        'api_url' => config('whatsapp.api_url'),
                        'access_token_configured' => !empty(config('whatsapp.access_token')),
                        'phone_number_id_configured' => !empty(config('whatsapp.phone_number_id')),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut WhatsApp',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester l'envoi d'un message WhatsApp pour un feedback spécifique
     */
    public function testFeedbackMessage(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'feedback_id' => 'required|exists:feedbacks,id',
            ]);

            $feedback = Feedback::with(['client', 'company'])->findOrFail($request->feedback_id);

            if (!$feedback->client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce feedback n\'a pas de client associé'
                ], 400);
            }

            if (!$feedback->client->phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le client n\'a pas de numéro de téléphone'
                ], 400);
            }

            // Tester l'envoi du message
            $result = $this->whatsappService->sendTreatedFeedbackMessage($feedback);

            return response()->json([
                'success' => true,
                'message' => $result ? 'Message WhatsApp envoyé avec succès' : 'Échec de l\'envoi du message WhatsApp',
                'data' => [
                    'feedback_id' => $feedback->id,
                    'client_name' => $feedback->client->full_name,
                    'client_phone' => $feedback->client->phone,
                    'message_sent' => $result
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test d\'envoi de message WhatsApp',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester l'envoi d'un message WhatsApp avec des données de test
     */
    public function testWithSampleData(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'phone_number' => 'required|string',
                'company_id' => 'nullable|exists:companies,id',
            ]);

            // Créer des données de test
            $company = $request->company_id ?
                Company::findOrFail($request->company_id) :
                Company::first();

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune entreprise trouvée. Veuillez créer une entreprise d\'abord.'
                ], 400);
            }

            // Créer un client de test temporaire
            $testClient = new Client([
                'first_name' => 'Test',
                'last_name' => 'Client',
                'phone' => $request->phone_number,
                'email' => 'test@example.com'
            ]);

            // Créer un feedback de test temporaire
            $testFeedback = new Feedback([
                'id' => 'test-' . uniqid(),
                'reference' => 'TEST-' . time(),
                'title' => 'Test de message WhatsApp',
                'description' => 'Ceci est un test du système de notification WhatsApp',
                'type' => 'incident',
                'status' => 'treated',
                'admin_resolution_description' => 'Test de résolution pour vérifier l\'envoi des messages WhatsApp.',
                'validation_expires_at' => now()->addHours(48),
                'company_id' => $company->id
            ]);

            // Associer les relations
            $testFeedback->setRelation('client', $testClient);
            $testFeedback->setRelation('company', $company);

            // Simuler la génération de token
            $testFeedback->validation_token = 'test-token-' . uniqid();

            // Tester l'envoi du message
            $result = $this->whatsappService->sendTreatedFeedbackMessage($testFeedback);

            return response()->json([
                'success' => true,
                'message' => $result ? 'Message de test WhatsApp envoyé avec succès' : 'Échec de l\'envoi du message de test WhatsApp',
                'data' => [
                    'test_phone' => $request->phone_number,
                    'company_name' => $company->name,
                    'message_sent' => $result,
                    'feedback_reference' => $testFeedback->reference
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test avec données d\'exemple',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir des informations de débogage sur la configuration WhatsApp
     */
    public function debugInfo(): JsonResponse
    {
        try {
            $config = [
                'enabled' => config('whatsapp.enabled'),
                'api_url' => config('whatsapp.api_url'),
                'access_token_set' => !empty(config('whatsapp.access_token')),
                'access_token_length' => strlen(config('whatsapp.access_token', '')),
                'phone_number_id_set' => !empty(config('whatsapp.phone_number_id')),
                'phone_number_id' => config('whatsapp.phone_number_id'),
                'service_enabled' => $this->whatsappService->isEnabled(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'config' => $config,
                    'environment_variables' => [
                        'WHATSAPP_ENABLED' => env('WHATSAPP_ENABLED'),
                        'WHATSAPP_ACCESS_TOKEN_SET' => !empty(env('WHATSAPP_ACCESS_TOKEN')),
                        'WHATSAPP_PHONE_NUMBER_ID' => env('WHATSAPP_PHONE_NUMBER_ID'),
                        'WHATSAPP_API_URL' => env('WHATSAPP_API_URL'),
                    ],
                    'instructions' => [
                        'Pour activer WhatsApp, configurez les variables suivantes dans votre .env :',
                        '1. WHATSAPP_ENABLED=true',
                        '2. WHATSAPP_ACCESS_TOKEN=votre_token_facebook',
                        '3. WHATSAPP_PHONE_NUMBER_ID=votre_id_numero_whatsapp',
                        '4. Redémarrez le serveur après modification du .env'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations de débogage',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}