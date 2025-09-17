<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\RegisterStepOneRequest;
use App\Http\Requests\RegisterStepTwoRequest;
use App\Http\Requests\RegisterStepThreeRequest;
use App\Http\Requests\LoginRequest;
use App\Models\UserOtp;
use App\Mail\OtpVerificationMail;
use Illuminate\Support\Facades\Mail;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Étape 1 : Inscription du gérant
     */
    public function registerStepOne(RegisterStepOneRequest $request)
    {
        try {
            // Vérifier que l'email et le phone ne sont pas déjà utilisés par un utilisateur vérifié
            $existingUser = User::where('email', $request->email)
                                ->whereNotNull('email_verified_at')
                                ->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette adresse email est déjà utilisée par un compte vérifié.'
                ], 422);
            }

            $existingPhone = User::where('phone', $request->phone)
                                 ->whereNotNull('email_verified_at')
                                 ->first();
            if ($existingPhone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de téléphone est déjà utilisé par un compte vérifié.'
                ], 422);
            }

            // Stocker temporairement les données dans la table OTP (sans créer l'utilisateur)
            $tempData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'password_hash' => Hash::make($request->password),
                'role' => 'manager'
            ];

            $otpRecord = UserOtp::createOtpWithTempData($request->email, $tempData, 'registration');
            
            // Envoyer l'email OTP
            try {
                $tempFullName = $request->first_name . ' ' . $request->last_name;
                Mail::to($request->email)->send(new OtpVerificationMail($otpRecord, $tempFullName));
            } catch (\Exception $e) {
                // Log l'erreur mais continue le processus
                \Log::error('Failed to send OTP email: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Un code de vérification a été envoyé à votre adresse email. Veuillez le vérifier pour créer votre compte.',
                'data' => [
                    'email' => $request->email,
                    'next_step' => 'email_verification',
                    'otp_expires_at' => $otpRecord->expires_at->toISOString()
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Étape 2 : Informations de l'entreprise
     */
    public function registerStepTwo(RegisterStepTwoRequest $request)
    {
        try {
            $user = Auth::user();

            // Créer l'entreprise
            $company = Company::create([
                'manager_id' => $user->id,
                'name' => $request->company_name,
                'email' => $request->company_email,
                'location' => $request->location,
                'business_sector_id' => $request->business_sector_id,
                'employee_count_id' => $request->employee_count_id,
                'creation_year' => $request->creation_year,
                'phone' => $request->company_phone,
                'business_description' => $request->business_description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Étape 2 terminée avec succès',
                'data' => [
                    'company_id' => $company->id,
                    'next_step' => 'media_upload'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'entreprise',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Étape 3 : Upload des médias (logo + photo gérant)
     */
    public function registerStepThree(RegisterStepThreeRequest $request)
    {
        try {
            $user = Auth::user();
            $company = $user->company;

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entreprise non trouvée'
                ], 404);
            }

            // Upload du logo de l'entreprise
            if ($request->hasFile('company_logo')) {
                $logoPath = $request->file('company_logo')->store('companies', 'public');
                $company->logo = basename($logoPath);
            }

            // Upload de la photo du gérant
            if ($request->hasFile('manager_photo')) {
                $photoPath = $request->file('manager_photo')->store('profiles', 'public');
                $user->profile_photo = basename($photoPath);
                $user->save();
            }

            // Générer le QR Code pour l'entreprise
            try {
                $qrCode = $this->generateQrCode($company);
                $company->qr_code = $qrCode;
            } catch (\Exception $e) {
                // Log l'erreur pour debug
                \Log::error('QR Code generation failed: ' . $e->getMessage());
                $company->qr_code = null;
            }
            $company->save();

            // Créer les services par défaut
            $this->createDefaultServices($company);

            // Génération du token final pour l'authentification
            $user->tokens()->delete(); // Supprimer le token de registration
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inscription terminée avec succès',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'profile_photo_url' => $user->profile_photo_url,
                    ],
                    'company' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'logo_url' => $company->logo_url,
                        'qr_code_url' => $company->qr_code_url,
                    ],
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload des médias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connexion
     */
    public function login(LoginRequest $request)
    {
        try {
            // Tentative de connexion avec email
            $credentials = [
                'email' => $request->identifier,
                'password' => $request->password
            ];

            // Si pas de réussite avec email, essayer avec phone
            if (!Auth::attempt($credentials)) {
                $credentials = [
                    'phone' => $request->identifier,
                    'password' => $request->password
                ];

                if (!Auth::attempt($credentials)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Identifiants incorrects'
                    ], 401);
                }
            }

            $user = Auth::user();
            $company = $user->company;

            // Vérifier si l'utilisateur a une entreprise (sauf pour les super admins)
            if (!$company && $user->role !== 'super_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscription non terminée - Veuillez compléter l\'étape 2 (informations entreprise)',
                    'data' => [
                        'user_id' => $user->id,
                        'next_step' => 'company_info',
                        'completed_steps' => ['user_created']
                    ]
                ], 400);
            }

            // Générer le token d'authentification
            $token = $user->createToken('auth-token')->plainTextToken;

            $responseData = [
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'profile_photo_url' => $user->profile_photo_url,
                ],
                'token' => $token
            ];

            // Ajouter les informations de l'entreprise seulement si elle existe
            if ($company) {
                $responseData['company'] = [
                    'id' => $company->id,
                    'name' => $company->name,
                    'logo_url' => $company->logo_url,
                    'qr_code_url' => $company->qr_code_url,
                    'satisfaction_score' => $company->satisfaction_score,
                    'total_feedbacks' => $company->total_feedbacks,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Informations de l'utilisateur connecté
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'profile_photo_url' => $user->profile_photo_url,
                ],
                'company' => $company ? [
                    'id' => $company->id,
                    'name' => $company->name,
                    'logo_url' => $company->logo_url,
                    'qr_code_url' => $company->qr_code_url,
                    'satisfaction_score' => $company->satisfaction_score,
                    'total_feedbacks' => $company->total_feedbacks,
                ] : null
            ]
        ]);
    }

    /**
     * Générer le QR Code pour l'entreprise
     */
    private function generateQrCode(Company $company)
    {
        // Si pas de frontend_url configurée, utiliser l'URL de l'API
        $baseUrl = config('app.frontend_url', config('app.url'));
        $feedbackUrl = $baseUrl . '/feedback/' . $company->id;
        
        $qrCode = QrCode::create($feedbackUrl)
            ->setSize(300)
            ->setMargin(10);

        // Utiliser SVG writer qui ne nécessite pas GD
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        $filename = 'qr_' . $company->id . '_' . time() . '.svg';
        $path = 'qr-codes/' . $filename;

        Storage::disk('public')->put($path, $result->getString());

        return $filename;
    }

    /**
     * Créer les services par défaut pour une nouvelle entreprise
     */
    private function createDefaultServices(Company $company)
    {
        $defaultServices = [
            ['name' => 'Réception', 'color' => '#3B82F6', 'icon' => 'phone'],
            ['name' => 'Direction', 'color' => '#8B5CF6', 'icon' => 'user-tie'],
            ['name' => 'Finance', 'color' => '#10B981', 'icon' => 'calculator'],
            ['name' => 'Service Client', 'color' => '#F59E0B', 'icon' => 'headphones'],
            ['name' => 'Technique', 'color' => '#EF4444', 'icon' => 'tools'],
        ];

        foreach ($defaultServices as $service) {
            $company->services()->create([
                'name' => $service['name'],
                'color' => $service['color'],
                'icon' => $service['icon'],
                'description' => 'Service ' . $service['name'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * Vérifier l'OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:4'
        ]);

        try {
            $otpRecord = UserOtp::verifyOtp($request->email, $request->otp, 'registration');
            
            if (!$otpRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code OTP invalide ou expiré'
                ], 400);
            }

            // Créer l'utilisateur maintenant que l'OTP est vérifié
            $user = User::create([
                'first_name' => $otpRecord->temp_first_name,
                'last_name' => $otpRecord->temp_last_name,
                'email' => $otpRecord->email,
                'phone' => $otpRecord->temp_phone,
                'password' => $otpRecord->temp_password_hash, // Déjà hashé
                'role' => $otpRecord->temp_role,
                'email_verified_at' => now(), // Vérifié immédiatement
            ]);

            // Mettre à jour l'enregistrement OTP avec l'user_id
            $otpRecord->update(['user_id' => $user->id]);

            // Générer le token pour les étapes suivantes
            $token = $user->createToken('registration', ['registration'])->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Email vérifié avec succès. Votre compte a été créé.',
                'data' => [
                    'user_id' => $user->id,
                    'token' => $token,
                    'next_step' => 'company_info'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renvoyer l'OTP
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            // Chercher un enregistrement OTP temporaire (pas encore d'utilisateur créé)
            $existingOtp = UserOtp::where('email', $request->email)
                                  ->where('type', 'registration')
                                  ->whereNull('user_id')
                                  ->where('is_used', false)
                                  ->first();

            if (!$existingOtp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune demande d\'inscription en cours pour cet email'
                ], 404);
            }

            // Créer un nouveau OTP avec les mêmes données temporaires
            $tempData = [
                'first_name' => $existingOtp->temp_first_name,
                'last_name' => $existingOtp->temp_last_name,
                'phone' => $existingOtp->temp_phone,
                'password_hash' => $existingOtp->temp_password_hash,
                'role' => $existingOtp->temp_role
            ];

            $otpRecord = UserOtp::createOtpWithTempData($request->email, $tempData, 'registration');
            
            // Envoyer l'email
            try {
                $tempFullName = $existingOtp->temp_first_name . ' ' . $existingOtp->temp_last_name;
                Mail::to($request->email)->send(new OtpVerificationMail($otpRecord, $tempFullName));
            } catch (\Exception $e) {
                \Log::error('Failed to resend OTP email: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Code de vérification renvoyé avec succès',
                'data' => [
                    'otp_expires_at' => $otpRecord->expires_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renvoi du code',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}