<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

// Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ValidationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SlaController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\FeedbackAlertController;
use App\Http\Controllers\ReferenceDataController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WhatsAppTestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group. Make something great!
|
*/

// Routes publiques (sans authentification)
Route::prefix('auth')->group(function () {
    Route::post('register/step-1', [AuthController::class, 'registerStepOne']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('login', [AuthController::class, 'login']);
});

// Routes publiques pour les données de référence
Route::prefix('reference')->group(function () {
    Route::get('business-sectors', [ReferenceDataController::class, 'businessSectors']);
    Route::get('employee-counts', [ReferenceDataController::class, 'employeeCounts']);
    Route::get('all', [ReferenceDataController::class, 'all']);
});

// Routes avec authentification pour l'inscription (token temporaire)
Route::middleware(['auth:sanctum'])->prefix('auth')->group(function () {
    Route::post('register/step-2', [AuthController::class, 'registerStepTwo']);
    Route::post('register/step-3', [AuthController::class, 'registerStepThree']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
});

// Routes publiques pour les feedbacks QR (sans auth)
Route::prefix('feedback')->group(function () {
    Route::get('company/{companyId}', [FeedbackController::class, 'getCompanyInfo']);
    Route::get('sentiments/{feedbackTypeId}', [FeedbackController::class, 'getSentiments']);
    Route::post('company/{companyId}', [FeedbackController::class, 'store']);
});

// Routes publiques pour les récompenses (clients)
Route::prefix('rewards')->group(function () {
    Route::get('company/{companyId}', [RewardController::class, 'index']);
    Route::post('{rewardId}/claim', [RewardController::class, 'claim']);
});

// Routes publiques pour le chatbot
Route::prefix('chatbot')->group(function () {
    Route::post('company/{companyId}/start', [ChatbotController::class, 'startConversation']);
    Route::post('conversation/{conversationId}/message', [ChatbotController::class, 'sendMessage']);
    Route::get('conversation/{conversationId}', [ChatbotController::class, 'getConversation']);
    Route::put('conversation/{conversationId}/close', [ChatbotController::class, 'closeConversation']);
});

// Route publique pour récupérer les infos d'une entreprise
Route::get('company/{companyId}', function ($companyId) {
    $company = \App\Models\Company::with(['businessSector', 'services' => function($query) {
        $query->where('is_active', true);
    }, 'employees' => function($query) {
        $query->where('is_active', true);
    }])->find($companyId);
    
    if (!$company) {
        return response()->json([
            'success' => false,
            'message' => 'Entreprise non trouvée'
        ], 404);
    }
    
    return response()->json([
        'success' => true,
        'data' => [
            'id' => $company->id,
            'name' => $company->name,
            'logo_url' => $company->logo_url,
            'qr_code_url' => $company->qr_code_url,
            'business_sector' => $company->businessSector ? [
                'id' => $company->businessSector->id,
                'name' => $company->businessSector->name,
                'color' => $company->businessSector->color,
                'icon' => $company->businessSector->icon
            ] : null,
            'satisfaction_score' => $company->satisfaction_score,
            'total_feedbacks' => $company->total_feedbacks,
            'services' => $company->services->map(function($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'color' => $service->color,
                    'icon' => $service->icon,
                    'description' => $service->description
                ];
            }),
            'employees' => $company->employees->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'position' => $employee->position
                ];
            })
        ]
    ]);
});

// Routes publiques pour la validation client (sans auth)
Route::prefix('validate')->group(function () {
    Route::get('{token}', function(Request $request, $token) {
        // Si c'est un lien direct avec status_id, valider directement
        if ($request->has('status_id')) {
            return app(FeedbackController::class)->validateByToken($request, $token);
        }
        // Sinon, afficher le formulaire
        return app(FeedbackController::class)->showValidationForm($token);
    });
    Route::post('{token}', [FeedbackController::class, 'validateByToken']);
});

// Routes protégées pour l'admin
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Dashboard et statistiques
    Route::prefix('dashboard')->group(function () {
        Route::get('overview', [DashboardController::class, 'overview']);
        Route::get('service/{serviceId}', [DashboardController::class, 'serviceStats']);
        Route::get('employee/{employeeId}', [DashboardController::class, 'employeeProfile']);
        Route::get('clients', [DashboardController::class, 'clientsStats']);
    });

    // Gestion des feedbacks (admin)
    Route::prefix('feedbacks')->group(function () {
        Route::get('/', [FeedbackController::class, 'index']);
        Route::get('{feedbackId}', [FeedbackController::class, 'show']);
        Route::put('{feedbackId}/status', [FeedbackController::class, 'updateStatus']);
        Route::post('{feedbackId}/generate-validation-token', [FeedbackController::class, 'generateValidationToken']);
        Route::post('{feedbackId}/send-follow-up', [FeedbackController::class, 'sendFollowUpEmail']);
        Route::delete('{feedbackId}', [FeedbackController::class, 'destroy']);
    });

    // Système de validation (admin)
    Route::prefix('validation')->group(function () {
        Route::post('feedback/{feedbackId}/generate', [ValidationController::class, 'generateValidationLink']);
        Route::get('pending', [ValidationController::class, 'getPendingValidations']);
        Route::post('feedback/{feedbackId}/remind', [ValidationController::class, 'sendValidationReminder']);
    });

    // Gestion de l'équipe (Manager uniquement)
    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'getTeam']);
        Route::post('add-director', [TeamController::class, 'addDirector']);
        Route::post('add-ceo', [TeamController::class, 'addCEO']);
        Route::post('add-service-head', [TeamController::class, 'addServiceHead']);
        Route::delete('member/{userId}', [TeamController::class, 'removeMember']);
    });

    // Gestion des services
    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::get('{serviceId}', [ServiceController::class, 'show']);
        Route::put('{serviceId}', [ServiceController::class, 'update']);
        Route::delete('{serviceId}', [ServiceController::class, 'destroy']);
        Route::put('{serviceId}/toggle-status', [ServiceController::class, 'toggleStatus']);
    });

    // Gestion des employés
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('{employeeId}', [EmployeeController::class, 'show']);
        Route::put('{employeeId}', [EmployeeController::class, 'update']);
        Route::delete('{employeeId}', [EmployeeController::class, 'destroy']);
        Route::put('{employeeId}/toggle-status', [EmployeeController::class, 'toggleStatus']);
        
        // Import Excel
        Route::post('import', [EmployeeController::class, 'importExcel']);
        Route::get('import/template', [EmployeeController::class, 'downloadTemplate']);
    });

    // Gestion du profil utilisateur
    Route::prefix('profile')->group(function () {
        Route::get('/', [AuthController::class, 'me']);
        Route::put('/', function (Request $request) {
            $request->validate([
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'email' => 'required|email|unique:users,email,' . $request->user()->id,
                'phone' => 'required|string|max:20|unique:users,phone,' . $request->user()->id,
                'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user = $request->user();
            
            $data = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
            ];

            if ($request->hasFile('profile_photo')) {
                // Supprimer l'ancienne photo
                if ($user->profile_photo) {
                    \Storage::disk('public')->delete('profiles/' . $user->profile_photo);
                }
                
                $photoPath = $request->file('profile_photo')->store('profiles', 'public');
                $data['profile_photo'] = basename($photoPath);
            }

            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => $user->fresh()
            ]);
        });
    });

    // Statistiques avancées (nouvelles routes)
    Route::prefix('statistics')->group(function () {
        Route::get('company/historical', [StatisticsController::class, 'companyHistoricalStats']);
        Route::get('services/comparison', [StatisticsController::class, 'servicesComparison']);
        Route::get('employees/ranking', [StatisticsController::class, 'employeesRanking']);
        Route::get('employee/{employeeId}/profile', [StatisticsController::class, 'employeeDetailedProfile']);
        Route::get('global/summary', [StatisticsController::class, 'globalSummary']);
        Route::post('recalculate', [StatisticsController::class, 'recalculateStatistics']);
    });

    // 🏆 Système de Récompenses et Badges
    Route::prefix('rewards')->group(function () {
        Route::get('/', [RewardController::class, 'index']);
        Route::post('/', [RewardController::class, 'store']);
        Route::get('{rewardId}', [RewardController::class, 'show']);
        Route::put('{rewardId}', [RewardController::class, 'update']);
        Route::delete('{rewardId}', [RewardController::class, 'destroy']);
        
        // Réclamations de récompenses
        Route::get('claims/all', [RewardController::class, 'claims']);
        Route::post('{rewardId}/claim', [RewardController::class, 'claim']);
        Route::put('claims/{claimId}/approve', [RewardController::class, 'approveClaim']);
        Route::put('claims/{claimId}/deliver', [RewardController::class, 'deliverClaim']);
        Route::put('claims/{claimId}/cancel', [RewardController::class, 'cancelClaim']);
    });

    // 🏅 Système de Badges
    Route::prefix('badges')->group(function () {
        Route::get('/', [BadgeController::class, 'index']);
        Route::post('/', [BadgeController::class, 'store']);
        Route::get('{badgeId}', [BadgeController::class, 'show']);
        Route::put('{badgeId}', [BadgeController::class, 'update']);
        Route::delete('{badgeId}', [BadgeController::class, 'destroy']);
        
        // Attribution de badges
        Route::post('{badgeId}/award', [BadgeController::class, 'awardBadge']);
        Route::get('client/{clientEmail}', [BadgeController::class, 'clientBadges']);
    });

    // 🔔 Système de Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('{notificationId}', [NotificationController::class, 'show']);
        Route::post('/', [NotificationController::class, 'send']);
        Route::put('{notificationId}/read', [NotificationController::class, 'markAsRead']);
        Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
        Route::put('{notificationId}/retry', [NotificationController::class, 'retry']);
        Route::delete('{notificationId}', [NotificationController::class, 'destroy']);
        
        // Templates de notifications
        Route::get('templates/list', [NotificationController::class, 'templates']);
    });

    // ⚡ Système SLA & Escalations
    Route::prefix('sla')->group(function () {
        Route::get('/', [SlaController::class, 'index']);
        Route::post('/', [SlaController::class, 'store']);
        Route::get('{slaRuleId}', [SlaController::class, 'show']);
        Route::put('{slaRuleId}', [SlaController::class, 'update']);
        Route::delete('{slaRuleId}', [SlaController::class, 'destroy']);
        
        // Escalations
        Route::get('escalations/list', [SlaController::class, 'escalations']);
        Route::put('escalations/{escalationId}/resolve', [SlaController::class, 'resolveEscalation']);
        
        // Statistiques
        Route::get('stats/overview', [SlaController::class, 'stats']);
    });

    // 🤖 Système de Chatbot IA (Admin)
    Route::prefix('chatbot')->group(function () {
        Route::get('conversations', [ChatbotController::class, 'adminConversations']);
        Route::put('conversations/{conversationId}/transfer', [ChatbotController::class, 'transferToAgent']);
    });

    // 🚨 Système d'Alertes Feedback
    Route::prefix('feedback-alerts')->group(function () {
        Route::get('/', [FeedbackAlertController::class, 'index']);
        Route::get('{alertId}', [FeedbackAlertController::class, 'show']);
        Route::put('{alertId}/acknowledge', [FeedbackAlertController::class, 'acknowledge']);
        Route::put('{alertId}/start-progress', [FeedbackAlertController::class, 'startProgress']);
        Route::put('{alertId}/resolve', [FeedbackAlertController::class, 'resolve']);
        Route::put('{alertId}/dismiss', [FeedbackAlertController::class, 'dismiss']);
        Route::put('{alertId}/escalate', [FeedbackAlertController::class, 'escalate']);
        Route::post('bulk-update', [FeedbackAlertController::class, 'bulkUpdate']);
        Route::get('stats/overview', [FeedbackAlertController::class, 'stats']);
        Route::get('dashboard/summary', [FeedbackAlertController::class, 'dashboard']);
    });

    // Gestion de l'entreprise
    Route::prefix('company')->group(function () {
        Route::get('/', function (Request $request) {
            $company = $request->user()->company()->with(['services', 'employees'])->first();
            return response()->json([
                'success' => true,
                'data' => $company
            ]);
        });
        
        Route::post('generate-qr', function (Request $request) {
            $company = $request->user()->company;
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entreprise non trouvée'
                ], 404);
            }
            
            // Générer le QR Code
            try {
                $baseUrl = config('app.frontend_url', config('app.url'));
                $feedbackUrl = $baseUrl . '/feedback/' . $company->id;
                
                $qrCode = \Endroid\QrCode\QrCode::create($feedbackUrl)
                    ->setSize(300)
                    ->setMargin(10);

                $writer = new \Endroid\QrCode\Writer\SvgWriter();
                $result = $writer->write($qrCode);

                $filename = 'qr_' . $company->id . '_' . time() . '.svg';
                $path = 'qr-codes/' . $filename;

                // Supprimer l'ancien QR code s'il existe
                if ($company->qr_code) {
                    \Storage::disk('public')->delete('qr-codes/' . $company->qr_code);
                }

                \Storage::disk('public')->put($path, $result->getString());
                
                $company->qr_code = $filename;
                $company->save();
                
                return response()->json([
                    'success' => true,
                    'message' => 'QR Code généré avec succès',
                    'data' => [
                        'qr_code_url' => $company->qr_code_url
                    ]
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la génération du QR Code',
                    'error' => $e->getMessage()
                ], 500);
            }
        });
        
        Route::put('/', function (Request $request) {
            $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|unique:companies,email,' . $request->user()->company->id,
                'location' => 'required|string|max:200',
                'category' => 'required|string',
                'employees_count' => 'required|integer|min:1',
                'phone' => 'required|string|max:20',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $company = $request->user()->company;
            
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'location' => $request->location,
                'category' => $request->category,
                'employees_count' => $request->employees_count,
                'phone' => $request->phone,
            ];

            if ($request->hasFile('logo')) {
                // Supprimer l'ancien logo
                if ($company->logo) {
                    \Storage::disk('public')->delete('companies/' . $company->logo);
                }
                
                $logoPath = $request->file('logo')->store('companies', 'public');
                $data['logo'] = basename($logoPath);
            }

            $company->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Informations de l\'entreprise mises à jour avec succès',
                'data' => $company->fresh()
            ]);
        });
    });
});

// Routes utilitaires
Route::prefix('utils')->group(function () {
    // Secteurs d'activité disponibles
    Route::get('business-sectors', function () {
        return response()->json([
            'success' => true,
            'data' => \App\Models\BusinessSector::getActiveOptions()
        ]);
    });

    // Icônes disponibles pour les services
    Route::get('icons', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'phone' => 'Téléphone',
                'user-tie' => 'Direction',
                'calculator' => 'Finance',
                'headphones' => 'Service Client',
                'tools' => 'Technique',
                'briefcase' => 'Général',
                'home' => 'Accueil',
                'car' => 'Transport',
                'shopping-cart' => 'Vente',
                'cog' => 'Administration',
                'heart' => 'Bien-être',
                'graduation-cap' => 'Formation',
                'shield-alt' => 'Sécurité',
                'utensils' => 'Restauration',
                'bed' => 'Hébergement',
                'wrench' => 'Maintenance',
            ]
        ]);
    });

    // Couleurs prédéfinies
    Route::get('colors', function () {
        return response()->json([
            'success' => true,
            'data' => [
                '#3B82F6' => 'Bleu',
                '#EF4444' => 'Rouge',
                '#10B981' => 'Vert',
                '#F59E0B' => 'Orange',
                '#8B5CF6' => 'Violet',
                '#06B6D4' => 'Cyan',
                '#84CC16' => 'Lime',
                '#F97316' => 'Orange vif',
                '#EC4899' => 'Rose',
                '#6B7280' => 'Gris',
            ]
        ]);
    });

    // Types de feedback disponibles
    Route::get('feedback-types', function () {
        return response()->json([
            'success' => true,
            'data' => \App\Models\FeedbackType::where('is_active', true)
                                             ->orderBy('sort_order')
                                             ->get(['id', 'name', 'label', 'description', 'color', 'icon'])
        ]);
    });
    
    // Sentiments disponibles par type de feedback
    Route::get('sentiments/{feedbackType}', function ($feedbackType) {
        return response()->json([
            'success' => true,
            'data' => \App\Models\Sentiment::getByFeedbackType($feedbackType)
        ]);
    });
    
    // Tous les sentiments
    Route::get('sentiments', function () {
        return response()->json([
            'success' => true,
            'data' => \App\Models\Sentiment::orderBy('name')->get(['id', 'name', 'description'])
        ]);
    });
});

// Route de test pour vérifier que l'API fonctionne
Route::get('health', function () {
    return response()->json([
        'success' => true,
        'message' => 'QualyWatch API is running',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
});

// Route de test pour prévisualiser les emails
Route::get('test/email-preview', function () {
    if (config('app.env') !== 'local') {
        return response()->json(['error' => 'Only available in development'], 403);
    }
    
    // Données de test pour le template
    $testData = [
        'company_name' => 'Aisha SARL',
        'client_name' => 'Aisha Seye',
        'feedback_type' => 'incident',
        'feedback_reference' => 'QW-2025-TEST-001',
        'feedback_title' => 'Probleme urgent avec le service',
        'admin_resolution' => 'Nous avons identifie le probleme et avons mis en place des corrections immediates. Notre equipe technique a optimise le processus pour eviter ce type de situation a l avenir.',
        'expires_at' => now()->addDays(7)->format('d/m/Y à H:i'),
        'resolved_url' => 'http://127.0.0.1:8000/api/validate/test-token?status_id=1',
        'not_resolved_url' => 'http://127.0.0.1:8000/api/validate/test-token?status_id=2'
    ];
    
    return view('emails.feedback-validation', $testData);
});

// Route de test pour le template v2 avec layout
Route::get('test/email-preview-v2', function () {
    if (config('app.env') !== 'local') {
        return response()->json(['error' => 'Only available in development'], 403);
    }
    
    // Données de test pour le template avec layout
    $testData = [
        'title' => 'Validation de votre feedback',
        'company_name' => 'Aisha SARL',
        'header_subtitle' => 'Validation requise',
        'reference' => 'QW-2025-TEST-001',
        'client_name' => 'Aisha Seye',
        'feedback_type' => 'incident',
        'feedback_reference' => 'QW-2025-TEST-001',
        'feedback_title' => 'Probleme urgent avec le service',
        'admin_resolution' => 'Nous avons identifie le probleme et avons mis en place des corrections immediates. Notre equipe technique a optimise le processus pour eviter ce type de situation a l avenir.',
        'expires_at' => now()->addDays(7)->format('d/m/Y à H:i'),
        'resolved_url' => 'http://127.0.0.1:8000/api/validate/test-token?status_id=1',
        'not_resolved_url' => 'http://127.0.0.1:8000/api/validate/test-token?status_id=2'
    ];
    
    return view('emails.feedback-validation-v2', $testData);
});

// Route pour envoyer un vrai email de test
Route::get('test/send-email', function () {
    if (config('app.env') !== 'local') {
        return response()->json(['error' => 'Only available in development'], 403);
    }
    
    try {
        // Données de test
        $testData = [
            'title' => 'Test Email QualyWatch',
            'company_name' => 'Aisha SARL',
            'header_subtitle' => 'Email de test',
            'reference' => 'QW-TEST-' . date('YmdHis'),
            'client_name' => 'Manager Test',
            'feedback_type' => 'incident',
            'feedback_reference' => 'QW-TEST-' . date('YmdHis'),
            'feedback_title' => 'Test du gradient orange dans l\'email',
            'admin_resolution' => 'Ceci est un test pour vérifier que le gradient orange s\'affiche correctement dans votre boîte email. Le header devrait avoir un beau dégradé orange.',
            'expires_at' => now()->addDays(7)->format('d/m/Y à H:i'),
            'resolved_url' => 'http://127.0.0.1:8000/api/test/validation?status=resolved',
            'not_resolved_url' => 'http://127.0.0.1:8000/api/test/validation?status=not_resolved'
        ];
        
        // Envoyer l'email
        Mail::to('aishaseye074@gmail.com')->send(new \App\Mail\TestEmail($testData));
        
        return response()->json([
            'success' => true,
            'message' => 'Email de test envoyé à aishaseye074@gmail.com',
            'data' => [
                'recipient' => 'aishaseye074@gmail.com',
                'template' => 'emails.feedback-validation-v2 (avec gradient orange)',
                'reference' => $testData['reference']
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'envoi de l\'email',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Route pour envoyer email de validation avec feedback réel
Route::get('test/send-validation-email/{feedbackId}', function ($feedbackId) {
    if (config('app.env') !== 'local') {
        return response()->json(['error' => 'Only available in development'], 403);
    }
    
    try {
        $feedback = \App\Models\Feedback::with(['client', 'company', 'feedbackType'])->find($feedbackId);
        
        if (!$feedback) {
            return response()->json(['error' => 'Feedback not found'], 404);
        }
        
        // Données réelles du feedback pour l'email
        $emailData = [
            'title' => 'Validation de votre ' . $feedback->feedbackType->label,
            'company_name' => $feedback->company->name,
            'header_subtitle' => 'Validation requise',
            'reference' => 'QW-2025-' . substr($feedback->id, 0, 8),
            'client_name' => $feedback->client->full_name,
            'feedback_type' => $feedback->feedbackType->label,
            'feedback_reference' => 'QW-2025-' . substr($feedback->id, 0, 8),
            'feedback_title' => $feedback->title,
            'admin_resolution' => $feedback->admin_response ?? 'Votre feedback a été traité avec succès. Notre équipe a pris les mesures nécessaires.',
            'expires_at' => now()->addDays(7)->format('d/m/Y à H:i'),
            'resolved_url' => 'http://127.0.0.1:8000/api/validate/' . $feedback->id . '?status=resolved',
            'not_resolved_url' => 'http://127.0.0.1:8000/api/validate/' . $feedback->id . '?status=not_resolved'
        ];
        
        // Envoyer l'email de validation
        Mail::to($feedback->client->email)->send(new \App\Mail\TestEmail($emailData));
        
        return response()->json([
            'success' => true,
            'message' => 'Email de validation envoyé avec CARD ORANGE GRADIENT 100px !',
            'data' => [
                'recipient' => $feedback->client->email,
                'feedback_id' => $feedback->id,
                'client_name' => $feedback->client->full_name,
                'company_name' => $feedback->company->name,
                'template' => 'emails.feedback-validation-v2 (CARD GRADIENT 100px)',
                'reference' => $emailData['reference']
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'envoi',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Route de test OTP (développement uniquement)
Route::get('test-otp', function () {
    if (config('app.env') !== 'local') {
        return response()->json(['error' => 'Only available in development'], 403);
    }
    
    try {
        // Créer un OTP de test
        $testOtp = \App\Models\UserOtp::generateOtp();
        
        return response()->json([
            'success' => true,
            'message' => 'Test OTP system',
            'data' => [
                'sample_otp' => $testOtp,
                'email_config' => [
                    'mailer' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                ],
                'endpoints' => [
                    'register_step_1' => url('/api/auth/register/step-1'),
                    'verify_otp' => url('/api/auth/verify-otp'),
                    'resend_otp' => url('/api/auth/resend-otp'),
                ]
            ]
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Routes Super Admin (pour les développeurs et admin système)
Route::middleware(['auth:sanctum', 'super_admin'])->prefix('super-admin')->group(function () {
    // Liste toutes les entreprises
    Route::get('companies', [SuperAdminController::class, 'getAllCompanies']);
    
    // Détails complets d'une entreprise
    Route::get('companies/{companyId}', [SuperAdminController::class, 'getCompanyDetails']);
    
    // Statistiques globales de la plateforme
    Route::get('statistics', [SuperAdminController::class, 'getGlobalStatistics']);
    
    // Dashboard global Super Admin
    Route::get('dashboard', [SuperAdminController::class, 'getSuperAdminDashboard']);
});

// Import
use App\Mail\TestEmail;

// Routes de test WhatsApp (développement uniquement)
Route::middleware(['auth:sanctum'])->prefix('whatsapp-test')->group(function () {
    Route::get('status', [WhatsAppTestController::class, 'checkStatus']);
    Route::get('connection', [WhatsAppTestController::class, 'testConnection']);
    Route::post('feedback/{feedback_id}', [WhatsAppTestController::class, 'testFeedbackMessage']);
    Route::post('sample', [WhatsAppTestController::class, 'testWithSampleData']);
    Route::get('debug', [WhatsAppTestController::class, 'debugInfo']);
});

// Route de test pour prévisualiser le nouveau template d'escalation
Route::get('test/escalation-preview', function () {
    if (config('app.env') !== 'local') {
        return response()->json(['error' => 'Only available in development'], 403);
    }

    // Données de test pour le template d'escalation
    $testData = [
        'escalation' => (object) [
            'escalation_level' => 2,
            'trigger_reason' => 'sla_breach',
            'escalated_at' => now()
        ],
        'user' => (object) [
            'full_name' => 'Jean Directeur'
        ],
        'feedback' => (object) [
            'reference' => 'ESC-2025-001',
            'rating' => 2,
            'content' => 'Service très décevant, temps d\'attente inacceptable et personnel peu aimable.',
            'created_at' => now()->subHours(2),
            'feedbackType' => (object) ['name' => 'Incident']
        ],
        'company' => (object) [
            'name' => 'Aisha SARL'
        ],
        'client' => (object) [
            'name' => 'Client Mécontent',
            'email' => 'client@example.com',
            'phone' => '+33123456789'
        ],
        'urgencyColor' => '#EF4444',
        'urgencyLabel' => 'ESCALADE DIRECTION',
        'actionUrl' => 'http://localhost:3000/dashboard/feedback/123'
    ];

    return view('emails.escalation-notification-v2', $testData);
});

// Route de test pour email de remerciement suggestion
Route::get('test/send-suggestion-email', function (Request $request) {
    try {
        $email = $request->get('email', 'test@example.com');
        $companyName = $request->get('company_name', 'Test Company');
        
        $testData = [
            'client_name' => 'Test Client Suggestion',
            'company_name' => $companyName,
            'rating' => 4,
            'message' => 'Votre service est bien mais il manque peut-être un système de réservation en ligne',
            'created_at' => '07/09/2025 à 23:50'
        ];
        
        // Envoyer l'email directement avec Mail::send
        Mail::send('emails.suggestion-thank-you-simple', $testData, function($message) use ($email) {
            $message->to($email)
                    ->subject('💡 Merci pour votre suggestion - QualyAisha SARL');
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Email de remerciement suggestion envoyé à ' . $email,
            'data' => [
                'recipient' => $email,
                'template' => 'emails.suggestion-thank-you-simple',
                'reference' => 'QW-SUGGESTION-' . date('YmdHis')
            ]
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});