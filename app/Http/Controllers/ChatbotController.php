<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\Company;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    /**
     * Démarrer une nouvelle conversation
     */
    public function startConversation(Request $request, $companyId)
    {
        try {
            $request->validate([
                'user_identifier' => 'nullable|string|max:255',
                'context' => 'nullable|in:feedback,support,info,complaint,suggestion',
                'initial_message' => 'required|string|max:1000'
            ]);

            $company = Company::findOrFail($companyId);
            $sessionId = Str::uuid();

            // Créer la conversation
            $conversation = ChatbotConversation::create([
                'company_id' => $companyId,
                'session_id' => $sessionId,
                'user_identifier' => $request->user_identifier,
                'context' => $request->context ?? 'support',
                'last_activity_at' => now(),
                'metadata' => [
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'started_at' => now()->toISOString(),
                ]
            ]);

            // Ajouter le message initial de l'utilisateur
            $userMessage = ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'user',
                'message' => $request->initial_message,
                'metadata' => [
                    'timestamp' => now()->toISOString(),
                ]
            ]);

            // Générer la réponse du bot
            $botResponse = $this->generateBotResponse($conversation, $request->initial_message);

            $botMessage = ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'bot',
                'message' => $botResponse['message'],
                'metadata' => $botResponse['metadata']
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'session_id' => $sessionId,
                    'messages' => [
                        [
                            'id' => $userMessage->id,
                            'sender_type' => 'user',
                            'message' => $userMessage->message,
                            'timestamp' => $userMessage->created_at->toISOString(),
                        ],
                        [
                            'id' => $botMessage->id,
                            'sender_type' => 'bot',
                            'message' => $botMessage->message,
                            'timestamp' => $botMessage->created_at->toISOString(),
                        ]
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage de la conversation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer un message dans une conversation existante
     */
    public function sendMessage(Request $request, $conversationId)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000',
                'session_id' => 'required|string'
            ]);

            $conversation = ChatbotConversation::where('id', $conversationId)
                                             ->where('session_id', $request->session_id)
                                             ->where('status', 'active')
                                             ->firstOrFail();

            // Ajouter le message utilisateur
            $userMessage = ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'user',
                'message' => $request->message,
                'metadata' => [
                    'timestamp' => now()->toISOString(),
                ]
            ]);

            // Mettre à jour l'activité
            $conversation->update(['last_activity_at' => now()]);

            // Générer la réponse du bot
            $botResponse = $this->generateBotResponse($conversation, $request->message);

            $botMessage = ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'bot',
                'message' => $botResponse['message'],
                'metadata' => $botResponse['metadata']
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_message' => [
                        'id' => $userMessage->id,
                        'sender_type' => 'user',
                        'message' => $userMessage->message,
                        'timestamp' => $userMessage->created_at->toISOString(),
                    ],
                    'bot_message' => [
                        'id' => $botMessage->id,
                        'sender_type' => 'bot',
                        'message' => $botMessage->message,
                        'timestamp' => $botMessage->created_at->toISOString(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer l'historique d'une conversation
     */
    public function getConversation(Request $request, $conversationId)
    {
        try {
            $sessionId = $request->get('session_id');
            
            $conversation = ChatbotConversation::where('id', $conversationId)
                                             ->where('session_id', $sessionId)
                                             ->with(['messages' => function($query) {
                                                 $query->orderBy('created_at');
                                             }])
                                             ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => [
                        'id' => $conversation->id,
                        'session_id' => $conversation->session_id,
                        'status' => $conversation->status,
                        'context' => $conversation->context,
                        'last_activity_at' => $conversation->last_activity_at->toISOString(),
                    ],
                    'messages' => $conversation->messages->map(function($message) {
                        return [
                            'id' => $message->id,
                            'sender_type' => $message->sender_type,
                            'message' => $message->message,
                            'timestamp' => $message->created_at->toISOString(),
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Fermer une conversation
     */
    public function closeConversation(Request $request, $conversationId)
    {
        try {
            $sessionId = $request->get('session_id');
            
            $conversation = ChatbotConversation::where('id', $conversationId)
                                             ->where('session_id', $sessionId)
                                             ->firstOrFail();

            $conversation->update(['status' => 'closed']);

            // Message de fermeture du bot
            ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'bot',
                'message' => 'Conversation fermée. Merci d\'avoir contacté ' . $conversation->company->name . ' !',
                'metadata' => [
                    'type' => 'system',
                    'timestamp' => now()->toISOString(),
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation fermée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la fermeture de la conversation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transférer vers un agent humain (Admin uniquement)
     */
    public function transferToAgent(Request $request, $conversationId)
    {
        try {
            $user = $request->user();
            if (!$user || ($user->role !== 'super_admin' && !$user->company)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $conversation = ChatbotConversation::where('id', $conversationId)
                                             ->when($user->role !== 'super_admin', function($query) use ($user) {
                                                 $query->where('company_id', $user->company->id);
                                             })
                                             ->firstOrFail();

            $conversation->update(['status' => 'transferred']);

            // Message de transfert
            ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'bot',
                'message' => 'Vous avez été transféré vers un agent humain qui va vous assister.',
                'metadata' => [
                    'type' => 'transfer',
                    'transferred_by' => $user->id,
                    'timestamp' => now()->toISOString(),
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation transférée vers un agent'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du transfert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des conversations pour les admins
     */
    public function adminConversations(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if ($companyId) {
                    $query = ChatbotConversation::where('company_id', $companyId);
                } else {
                    $query = ChatbotConversation::query();
                }
            } else {
                $query = ChatbotConversation::where('company_id', $user->company->id);
            }

            // Filtres
            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->context) {
                $query->where('context', $request->context);
            }

            $conversations = $query->with(['company', 'client'])
                                  ->withCount('messages')
                                  ->orderBy('last_activity_at', 'desc')
                                  ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversations' => $conversations->items(),
                    'pagination' => [
                        'total' => $conversations->total(),
                        'per_page' => $conversations->perPage(),
                        'current_page' => $conversations->currentPage(),
                        'last_page' => $conversations->lastPage(),
                    ],
                    'stats' => [
                        'active' => ChatbotConversation::where('company_id', $user->company?->id)->where('status', 'active')->count(),
                        'transferred' => ChatbotConversation::where('company_id', $user->company?->id)->where('status', 'transferred')->count(),
                        'closed_today' => ChatbotConversation::where('company_id', $user->company?->id)->where('status', 'closed')->whereDate('updated_at', today())->count(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des conversations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer une réponse automatique du bot
     */
    private function generateBotResponse($conversation, $userMessage)
    {
        $message = strtolower($userMessage);
        $intent = $this->detectIntent($message);
        $confidence = $this->calculateConfidence($message, $intent);

        $response = match($intent) {
            'greeting' => $this->getGreetingResponse($conversation),
            'feedback_complaint' => $this->getFeedbackResponse($conversation),
            'help' => $this->getHelpResponse($conversation),
            'goodbye' => $this->getGoodbyeResponse($conversation),
            'info' => $this->getInfoResponse($conversation),
            'reward' => $this->getRewardResponse($conversation),
            default => $this->getDefaultResponse($conversation)
        };

        return [
            'message' => $response,
            'metadata' => [
                'intent' => $intent,
                'confidence' => $confidence,
                'timestamp' => now()->toISOString(),
                'automated' => true
            ]
        ];
    }

    private function detectIntent($message)
    {
        $patterns = [
            'greeting' => ['bonjour', 'salut', 'hello', 'hey', 'bonsoir'],
            'goodbye' => ['au revoir', 'bye', 'à bientôt', 'merci', 'fin'],
            'feedback_complaint' => ['problème', 'plainte', 'insatisfait', 'mauvais', 'défaut', 'erreur'],
            'help' => ['aide', 'help', 'assistance', 'comment', 'pouvez-vous'],
            'info' => ['information', 'horaires', 'contact', 'adresse', 'téléphone'],
            'reward' => ['récompense', 'kalipoints', 'badge', 'points', 'cadeau']
        ];

        foreach ($patterns as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return $intent;
                }
            }
        }

        return 'unknown';
    }

    private function calculateConfidence($message, $intent)
    {
        return $intent === 'unknown' ? 0.3 : 0.8;
    }

    private function getGreetingResponse($conversation)
    {
        return "Bonjour ! Je suis l'assistant virtuel de " . $conversation->company->name . ". Comment puis-je vous aider aujourd'hui ?";
    }

    private function getFeedbackResponse($conversation)
    {
        return "Je comprends que vous rencontrez un problème. Pouvez-vous me donner plus de détails ? Je peux vous aider à soumettre un feedback détaillé à notre équipe.";
    }

    private function getHelpResponse($conversation)
    {
        return "Je peux vous aider avec :\n- Soumettre un feedback\n- Obtenir des informations sur nos services\n- Vous renseigner sur nos récompenses\n- Vous mettre en contact avec notre équipe\n\nQue souhaitez-vous faire ?";
    }

    private function getGoodbyeResponse($conversation)
    {
        return "Merci d'avoir contacté " . $conversation->company->name . " ! N'hésitez pas à revenir si vous avez d'autres questions. Bonne journée !";
    }

    private function getInfoResponse($conversation)
    {
        $company = $conversation->company;
        return "Voici nos informations :\n📍 Adresse: " . $company->location . "\n📧 Email: " . $company->email . "\n☎️ Téléphone: " . $company->phone . "\n\nAvez-vous besoin d'autres informations ?";
    }

    private function getRewardResponse($conversation)
    {
        return "Nous avons un système de récompenses avec des KaliPoints ! Vous pouvez gagner des points en laissant des feedbacks et les échanger contre des récompenses. Souhaitez-vous en savoir plus ?";
    }

    private function getDefaultResponse($conversation)
    {
        return "Je ne suis pas sûr de comprendre votre demande. Pouvez-vous reformuler ou me dire comment je peux vous aider ? Vous pouvez également être transféré vers un membre de notre équipe si nécessaire.";
    }
}