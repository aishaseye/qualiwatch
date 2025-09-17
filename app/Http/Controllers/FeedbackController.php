<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Feedback;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Http\Requests\CreateFeedbackRequest;
use Illuminate\Support\Facades\Storage;
use App\Services\AlertDetectionService;
use App\Services\NotificationService;
use App\Services\SentimentService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    /**
     * Récupérer les sentiments disponibles pour un type de feedback
     */
    public function getSentiments($feedbackTypeId)
    {
        try {
            $sentiments = SentimentService::getSentimentsByFeedbackType($feedbackTypeId);
            $defaultSentiment = SentimentService::getDefaultSentimentByFeedbackType($feedbackTypeId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'sentiments' => $sentiments,
                    'default_sentiment' => $defaultSentiment ? [
                        'id' => $defaultSentiment->id,
                        'name' => $defaultSentiment->name,
                        'label' => $defaultSentiment->label,
                    ] : null
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des sentiments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupérer les informations d'une entreprise pour le formulaire QR
     */
    public function getCompanyInfo($companyId)
    {
        try {
            $company = Company::with(['services' => function($query) {
                $query->where('is_active', true);
            }])->findOrFail($companyId);

            return response()->json([
                'success' => true,
                'data' => [
                    'company' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'logo_url' => $company->logo_url,
                    ],
                    'services' => $company->services->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'name' => $service->name,
                            'color' => $service->color,
                            'icon' => $service->icon,
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Entreprise non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Créer un nouveau feedback via QR code
     */
    public function store(Request $request, $companyId)
    {
        // Récupérer le type de feedback pour validation conditionnelle
        $feedbackType = \App\Models\FeedbackType::find($request->feedback_type_id);
        
        // Validation de base
        $rules = [
            'feedback_type_id' => 'required|uuid|exists:feedback_types,id',
            'title' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:2000',
            'service_id' => 'nullable|uuid',
            'employee_id' => 'nullable|uuid',
            'rating' => 'required|integer|min:1|max:5',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'video' => 'nullable|file|mimes:mp4,mov,avi,wmv|max:51200',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg|max:10240',
            'sentiment_id' => 'nullable|integer',
        ];
        
        // Validation conditionnelle selon le type de feedback
        if ($feedbackType && $feedbackType->name === 'positif') {
            // Feedback positif : informations client optionnelles
            $rules = array_merge($rules, [
                'first_name' => 'nullable|string|max:50',
                'last_name' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:100',
                'phone' => 'nullable|string|max:20',
            ]);
        } else {
            // Feedback négatif/incident : informations client obligatoires
            $rules = array_merge($rules, [
                'title' => 'required|string|max:200',
                'description' => 'required|string|max:2000',
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'email' => 'required|email|max:100',
                'phone' => 'nullable|string|max:20',
            ]);
        }
        
        $request->validate($rules);

        try {
            $company = Company::findOrFail($companyId);

            // Gérer les uploads de médias
            $photoUrl = null;
            $videoUrl = null; 
            $audioUrl = null;
            $mediaType = 'text'; // Valeur par défaut (enum: text, audio, video, mixed)
            $mediaCount = 0;

            // Debug: voir ce qui est reçu
            \Log::info('Files received:', [
                'has_photo' => $request->hasFile('photo'),
                'has_video' => $request->hasFile('video'),
                'has_audio' => $request->hasFile('audio'),
                'photo_valid' => $request->hasFile('photo') && $request->file('photo')->isValid(),
                'video_valid' => $request->hasFile('video') && $request->file('video')->isValid(),
            ]);

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('feedback/photos', 'public');
                $photoUrl = basename($photoPath);
                $mediaType = 'video'; // Photo considérée comme video dans l'enum
                $mediaCount++;
            }

            if ($request->hasFile('video')) {
                $videoPath = $request->file('video')->store('feedback/videos', 'public');
                $videoUrl = basename($videoPath);
                $mediaType = 'video';
                $mediaCount++;
            }

            if ($request->hasFile('audio')) {
                $audioPath = $request->file('audio')->store('feedback/audio', 'public');
                $audioUrl = basename($audioPath);
                $mediaType = 'audio';
                $mediaCount++;
            }

            // Si plusieurs types de médias, utiliser "mixed"
            if ($mediaCount > 1) {
                $mediaType = 'mixed';
            }

            // Trouver ou créer le client (anonyme si feedback positif sans infos)
            $client = null;
            if ($request->email || $request->first_name) {
                $client = Client::findOrCreateByContact(
                    $request->email,
                    $request->phone,
                    $request->first_name ?: 'Client',
                    $request->last_name ?: 'Anonyme'
                );
            } else {
                // Client anonyme pour feedback positif
                $client = Client::findOrCreateByContact(
                    null,
                    null,
                    'Client',
                    'Anonyme'
                );
            }

            // Trouver l'employé si spécifié  
            $employee = null;
            $employeeId = null;
            if ($request->employee_id) {
                $employee = Employee::where('company_id', $company->id)
                    ->find($request->employee_id);
                $employeeId = $employee ? $employee->id : null;
            }

            // Trouver le service si spécifié
            $service = null;
            $serviceId = null;
            if ($request->service_id) {
                $service = Service::where('company_id', $company->id)
                    ->find($request->service_id);
                $serviceId = $service ? $service->id : null;
            }

            // Récupérer le type de feedback
            $feedbackType = \App\Models\FeedbackType::find($request->feedback_type_id);
            
            // Récupérer le statut "nouveau" par défaut - méthode alternative
            $newStatus = null;
            try {
                $newStatus = \App\Models\FeedbackStatus::where('name', 'new')->first();
                if (!$newStatus) {
                    // Fallback: prendre le premier statut disponible
                    $newStatus = \App\Models\FeedbackStatus::orderBy('sort_order')->first();
                }
            } catch (\Exception $e) {
                \Log::error('Erreur récupération statut: ' . $e->getMessage());
            }
            
            // Vérification de sécurité finale
            if (!$newStatus || !$newStatus->id) {
                throw new \Exception('Aucun statut de feedback disponible. Vérifiez la configuration des statuts.');
            }
            
            // Debug pour voir l'UUID
            \Log::info('Status UUID found: ' . $newStatus->id . ' (name: ' . $newStatus->name . ')');
            
            // Calculer les KaliPoints basés sur le rating et le type de feedback
            $rating = $request->rating;
            $kalipoints = $this->calculateKaliPointsByRating($feedbackType->label, $rating);
            
            // Gérer le sentiment selon le type de feedback
            $sentimentId = $request->sentiment_id;
            $sentimentType = SentimentService::getSentimentTypeByFeedbackType($request->feedback_type_id);
            
            // Valider le sentiment si fourni
            if ($sentimentId && $sentimentType) {
                $validation = SentimentService::validateSentimentForFeedbackType($sentimentId, $request->feedback_type_id);
                if (!$validation || !$validation['is_valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le sentiment sélectionné ne correspond pas au type de feedback'
                    ], 422);
                }
                $sentimentType = $validation['sentiment_type'];
            }
            
            // Si pas de sentiment fourni, prendre le défaut ET s'assurer que sentiment_type est défini
            if (!$sentimentId && $sentimentType) {
                $defaultSentiment = SentimentService::getDefaultSentimentByFeedbackType($request->feedback_type_id);
                if ($defaultSentiment) {
                    $sentimentId = $defaultSentiment->id;
                }
            }
            
            // S'assurer que sentiment_type est toujours défini même sans sentiment_id
            if (!$sentimentType) {
                $sentimentType = SentimentService::getSentimentTypeByFeedbackType($request->feedback_type_id);
            }

            // Préparer les données du feedback
            $feedbackData = [
                'company_id' => $company->id,
                'client_id' => $client->id,
                'employee_id' => $employeeId,
                'service_id' => $serviceId,
                'feedback_type_id' => $request->feedback_type_id,
                'feedback_status_id' => $newStatus->id,
                'type' => $feedbackType->name,
                'status' => 'new',
                'title' => $request->title,
                'description' => $request->description,
                'rating' => $rating,
                'kalipoints' => $kalipoints,
                'media_type' => $mediaType,
                'attachment_url' => $photoUrl,
                'video_url' => $videoUrl,
                'audio_url' => $audioUrl,
            ];
            
            // Ajouter le sentiment
            if ($sentimentId && $sentimentType) {
                $feedbackData['sentiment_id'] = $sentimentId;
                $feedbackData['sentiment_type'] = $sentimentType;
            }
            
            // Créer le feedback
            $feedback = Feedback::create($feedbackData);

            // Ajouter les KaliPoints au client (tous les types maintenant)
            if ($kalipoints > 0) {
                $client->addKaliPoints($kalipoints);
            }

            // 🚨 Envoyer les notifications automatiques
            try {
                // Les notifications générales sont maintenant gérées par FeedbackObserver
                // (évite la duplication d'emails)

                // Analyser le feedback pour les alertes spécifiques
                $alertService = new AlertDetectionService();
                $alert = $alertService->analyzeFeedback($feedback);
                if ($alert) {
                    // Envoyer notification d'alerte immédiate aux managers
                    $notificationService = new NotificationService();
                    $notificationService->sendFeedbackAlert($alert);
                }
            } catch (\Exception $e) {
                // Ne pas faire échouer la création du feedback si l'analyse d'alerte échoue
                \Log::error('Erreur analyse alerte feedback: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Merci pour votre feedback !',
                'data' => [
                    'feedback_id' => $feedback->id,
                    'reference' => $feedback->reference ?? $feedback->id,
                    'type' => $feedbackType->name,
                    'kalipoints_earned' => $kalipoints,
                    'total_kalipoints' => $client->fresh()->total_kalipoints ?? 0,
                    'media_uploaded' => $mediaType,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister les feedbacks pour l'admin (avec filtres)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir tous les feedbacks
            if ($user->role === 'super_admin') {
                $query = \App\Models\Feedback::with(['client', 'employee', 'service', 'treatedByUser', 'company', 'feedbackAlerts']);
            } else {
                // Manager ne voit que les feedbacks de son entreprise
                $company = $user->company;
                $query = $company->feedbacks()
                    ->with(['client', 'employee', 'service', 'treatedByUser', 'feedbackAlerts']);
            }

            // Filtres
            if ($request->type) {
                $query->where('type', $request->type);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->service_id) {
                $query->where('service_id', $request->service_id);
            }

            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Tri
            $query->orderBy('created_at', 'desc');

            // Pagination
            $feedbacks = $query->paginate(50);

            return response()->json([
                'success' => true,
                'data' => [
                    'feedbacks' => $feedbacks->items(),
                    'pagination' => [
                        'total' => $feedbacks->total(),
                        'per_page' => $feedbacks->perPage(),
                        'current_page' => $feedbacks->currentPage(),
                        'last_page' => $feedbacks->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des feedbacks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer un feedback spécifique
     */
    public function show(Request $request, $feedbackId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir n'importe quel feedback
            if ($user->role === 'super_admin') {
                $feedback = \App\Models\Feedback::with(['client', 'employee', 'service', 'treatedByUser', 'validationLogs', 'company', 'feedbackAlerts'])
                    ->findOrFail($feedbackId);
            } else {
                // Manager ne voit que les feedbacks de son entreprise
                $company = $user->company;
                $feedback = $company->feedbacks()
                    ->with(['client', 'employee', 'service', 'treatedByUser', 'validationLogs', 'feedbackAlerts'])
                    ->findOrFail($feedbackId);
            }

            return response()->json([
                'success' => true,
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour le statut d'un feedback
     */
    public function updateStatus(Request $request, $feedbackId)
    {
        try {
            $request->validate([
                'status_id' => 'required|uuid|exists:feedback_statuses,id',
                'admin_comments' => 'nullable|string|max:1000',
                'admin_resolution_description' => 'nullable|string|max:2000',
            ]);

            $user = $request->user();
            $company = $user->company;

            $feedback = $company->feedbacks()->findOrFail($feedbackId);
            $previousStatus = $feedback->status;

            // Récupérer le feedback_status par UUID
            $feedbackStatus = \App\Models\FeedbackStatus::findOrFail($request->status_id);

            $feedback->update([
                'status' => $feedbackStatus->name, // Maintenir la compatibilité legacy
                'feedback_status_id' => $request->status_id,
                'admin_comments' => $request->admin_comments,
                'admin_resolution_description' => $request->admin_resolution_description,
                'treated_by_user_id' => $user->id,
                'treated_at' => now(),
            ]);

            // Si le statut passe à "treated", envoyer email de validation avec boutons
            if ($feedbackStatus->name === 'treated' && $previousStatus !== 'treated') {
                if (($feedback->type === 'negatif' || $feedback->type === 'incident') && $feedback->client && $feedback->client->email) {
                    // Générer le token de validation
                    $validationToken = $feedback->generateValidationToken();

                    try {
                        // Envoyer l'email de validation avec les boutons
                        \Mail::to($feedback->client->email)->send(new \App\Mail\FeedbackValidationMail($feedback));

                        // Programmer une relance dans 24h si pas de réponse
                        \App\Jobs\SendFollowUpEmailJob::dispatch($feedback)->delay(now()->addDay());

                        \Log::info('Email de validation envoyé et relance programmée', [
                            'feedback_id' => $feedback->id,
                            'client_email' => $feedback->client->email,
                            'validation_token' => $validationToken,
                            'follow_up_scheduled_at' => now()->addDay()
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Erreur envoi email de validation: ' . $e->getMessage());
                    }
                }
            }

            $response = [
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => $feedback->fresh()
            ];

            // Ajouter le token de validation dans la réponse si généré
            if (isset($validationToken)) {
                $response['validation_token'] = $validationToken;
                $response['validation_url'] = config('app.frontend_url') . '/validate/' . $validationToken;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer manuellement un email de suivi (pour les managers)
     */
    public function sendFollowUpEmail(Request $request, $feedbackId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $feedback = $company->feedbacks()->findOrFail($feedbackId);

            // Vérifier que le feedback est éligible pour un suivi
            if ($feedback->status !== 'treated') {
                return response()->json([
                    'success' => false,
                    'message' => 'Le feedback doit être en statut "traité" pour envoyer un suivi'
                ], 400);
            }

            if ($feedback->client_validated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le client a déjà validé ce feedback'
                ], 400);
            }

            if ($feedback->status === 'not_resolved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Le client a indiqué que le problème n\'est pas résolu. Les relances automatiques sont arrêtées.'
                ], 400);
            }

            // Vérifier la limite d'une semaine
            $treatmentDate = $feedback->updated_at;
            $oneWeekAfterTreatment = $treatmentDate->addWeek();

            if (now() > $oneWeekAfterTreatment) {
                return response()->json([
                    'success' => false,
                    'message' => 'La période de suivi d\'une semaine est expirée'
                ], 400);
            }

            // Envoyer l'email de suivi
            try {
                \Mail::to($feedback->client->email)->send(
                    new \App\Mail\FeedbackValidationMail($feedback, true) // true = isReminder
                );

                \Log::info('Email de suivi manuel envoyé', [
                    'feedback_id' => $feedback->id,
                    'client_email' => $feedback->client->email,
                    'sent_by_manager_id' => $user->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Email de suivi envoyé avec succès'
                ]);

            } catch (\Exception $e) {
                \Log::error('Erreur envoi email de suivi manuel: ' . $e->getMessage(), [
                    'feedback_id' => $feedback->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi de l\'email de suivi'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du suivi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider un feedback via token (pour les clients)
     */
    public function validateByToken(Request $request, $token)
    {
        try {
            // Accepter status_id via GET (liens directs) ou POST (formulaire)
            $statusId = $request->input('status_id') ?? $request->input('feedback_status_id');
            
            $request->merge(['feedback_status_id' => $statusId]);
            
            $request->validate([
                'feedback_status_id' => 'required|string|exists:feedback_statuses,id',
                'rating' => 'nullable|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $feedback = Feedback::where('validation_token', $token)
                ->where('client_validated', false)
                ->where('validation_expires_at', '>', now())
                ->firstOrFail();

            // Récupérer le statut sélectionné par le client
            $feedbackStatus = \App\Models\FeedbackStatus::findOrFail($request->feedback_status_id);
            
            // Calculer les points bonus selon le statut
            $bonusPoints = 0;
            if ($feedbackStatus->name === 'resolved') {
                $bonusPoints = 5; // Points bonus si résolu
            } elseif ($feedbackStatus->name === 'partially_resolved') {
                $bonusPoints = 2; // Points bonus partiels
            }

            $feedback->validateByClient(
                $feedbackStatus->name,
                $request->rating,
                $request->comment,
                $bonusPoints,
                $feedbackStatus->id
            );

            // Si le statut est "not_resolved", programmer une nouvelle relance dans 24h
            if ($feedbackStatus->name === 'not_resolved') {
                try {
                    \App\Jobs\SendFollowUpEmailJob::dispatch($feedback)->delay(now()->addDay());

                    \Log::info('Nouvelle relance programmée car problème non résolu', [
                        'feedback_id' => $feedback->id,
                        'next_followup_at' => now()->addDay()
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Erreur programmation relance: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Merci pour votre validation !',
                'data' => [
                    'feedback_id' => $feedback->id,
                    'reference' => $feedback->reference,
                    'validation_status' => $feedbackStatus->name,
                    'status_label' => $feedbackStatus->label,
                    'bonus_points_earned' => $bonusPoints,
                    'final_status' => $feedback->status,
                    'follow_up_scheduled' => $feedbackStatus->name === 'not_resolved'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token de validation invalide ou expiré',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Afficher le formulaire de validation (pour les clients)
     */
    public function showValidationForm($token)
    {
        try {
            $feedback = Feedback::with(['company', 'client'])
                ->where('validation_token', $token)
                ->where('client_validated', false)
                ->where('validation_expires_at', '>', now())
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'feedback' => [
                        'id' => $feedback->id,
                        'reference' => $feedback->reference,
                        'title' => $feedback->title,
                        'description' => $feedback->description,
                        'type' => $feedback->type,
                        'status' => $feedback->status,
                        'admin_resolution_description' => $feedback->admin_resolution_description,
                        'treated_at' => $feedback->treated_at,
                    ],
                    'company' => [
                        'name' => $feedback->company->name,
                        'logo_url' => $feedback->company->logo_url,
                    ],
                    'client' => [
                        'name' => $feedback->client->full_name,
                        'email' => $feedback->client->email,
                    ],
                    'validation' => [
                        'expires_at' => $feedback->validation_expires_at,
                        'can_validate' => !$feedback->is_validation_expired,
                    ],
                    'available_statuses' => \App\Models\FeedbackStatus::whereIn('name', [
                        'resolved',
                        'not_resolved'
                    ])->where('is_active', true)->get(['id', 'name', 'label', 'description', 'color'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token de validation invalide ou expiré',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Générer un token de validation pour un feedback
     */
    public function generateValidationToken(Request $request, $feedbackId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $feedback = $company->feedbacks()->findOrFail($feedbackId);

            // Vérifier que le feedback est traité et nécessite validation
            if ($feedback->status !== 'treated') {
                return response()->json([
                    'success' => false,
                    'message' => 'Le feedback doit être marqué comme traité avant génération du token',
                ], 400);
            }

            if ($feedback->type !== 'incident' && $feedback->type !== 'suggestion' && $feedback->type !== 'negatif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les incidents, suggestions et feedbacks négatifs peuvent être validés',
                ], 400);
            }

            // Générer le token seulement (PAS d'envoi email automatique)
            $token = $feedback->generateValidationToken();
            $validationUrl = config('app.frontend_url') . '/validate/' . $token;

            return response()->json([
                'success' => true,
                'message' => 'Token de validation généré avec succès',
                'data' => [
                    'validation_token' => $token,
                    'validation_url' => $validationUrl,
                    'expires_at' => $feedback->validation_expires_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un feedback
     */
    public function destroy(Request $request, $feedbackId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $feedback = $company->feedbacks()->findOrFail($feedbackId);

            // Supprimer le fichier joint si présent
            if ($feedback->attachment_url) {
                Storage::disk('public')->delete('attachments/' . $feedback->attachment_url);
            }

            $feedback->delete();

            return response()->json([
                'success' => true,
                'message' => 'Feedback supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer les KaliPoints selon le type de feedback
     */
    private function calculateKaliPoints($type, $providedPoints = null)
    {
        switch ($type) {
            case 'appreciation':
                // Pour les appréciations, utiliser les points fournis (1-5)
                return max(1, min(5, $providedPoints ?? 3));
                
            case 'incident':
                // Pour les incidents, pas de points initiaux
                return 0;
                
            case 'suggestion':
                // Pour les suggestions, 2 points fixes
                return 2;
                
            default:
                return 0;
        }
    }

    /**
     * Calculer les KaliPoints selon le label du type de feedback
     */
    private function calculateKaliPointsByType($typeLabel)
    {
        switch ($typeLabel) {
            case 'positif':
                return 5; // Feedback positif = 5 points
                
            case 'negatif':
                return 0; // Feedback négatif = 0 points initialement
                
            case 'suggestion':
                return 3; // Suggestion = 3 points
                
            default:
                return 0;
        }
    }

    /**
     * Calculer les KaliPoints selon le type et le rating
     */
    private function calculateKaliPointsByRating($typeLabel, $rating)
    {
        switch ($typeLabel) {
            case 'positif':
                // Pour les feedbacks positifs, KaliPoints = rating
                return max(1, min(5, $rating));
                
            case 'negatif':
                // Pour les feedbacks négatifs, pas de points initiaux
                return 0;
                
            case 'suggestion':
                // Pour les suggestions, points fixes basés sur le rating
                return max(1, min(3, intval($rating / 2) + 1));
                
            default:
                return max(0, min(3, intval($rating / 2)));
        }
    }
}