<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\ValidationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\FeedbackValidationMail;
use App\Http\Requests\ValidateFeedbackRequest;
use App\Http\Requests\GenerateValidationRequest;

class ValidationController extends Controller
{
    /**
     * Générer un lien de validation pour un feedback traité
     * (Appelé par l'admin après avoir traité un incident/suggestion)
     */
    public function generateValidationLink(GenerateValidationRequest $request, $feedbackId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $feedback = $company->feedbacks()
                ->whereIn('type', ['incident', 'suggestion'])
                ->where('status', 'treated')
                ->findOrFail($feedbackId);

            // Générer le token de validation
            $token = $feedback->generateValidationToken();

            // Envoyer l'email de validation au client
            if ($feedback->client && $feedback->client->email) {
                Mail::to($feedback->client->email)->send(new FeedbackValidationMail($feedback));
            }

            return response()->json([
                'success' => true,
                'message' => 'Lien de validation généré et envoyé au client',
                'data' => [
                    'validation_token' => $token,
                    'validation_url' => $feedback->getValidationUrl(),
                    'expires_at' => $feedback->validation_expires_at,
                    'client_email' => $feedback->client?->email,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du lien de validation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les détails d'un feedback pour validation par le client
     * (Page web accessible via le token)
     */
    public function getValidationDetails($token)
    {
        try {
            $feedback = Feedback::where('validation_token', $token)
                ->where('validation_expires_at', '>', now())
                ->where('client_validated', false)
                ->with(['company', 'client', 'service', 'employee'])
                ->first();

            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lien de validation invalide ou expiré',
                    'error_type' => 'invalid_token'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'feedback' => [
                        'id' => $feedback->id,
                        'reference' => $feedback->reference,
                        'type' => $feedback->type,
                        'type_label' => $feedback->type === 'incident' ? 'Incident' : 'Suggestion',
                        'title' => $feedback->title,
                        'description' => $feedback->description,
                        'created_at' => $feedback->created_at->format('d/m/Y à H:i'),
                        'treated_at' => $feedback->treated_at->format('d/m/Y à H:i'),
                    ],
                    'company' => [
                        'name' => $feedback->company->name,
                        'logo_url' => $feedback->company->logo_url,
                    ],
                    'client' => [
                        'full_name' => $feedback->client->full_name,
                        'contact_info' => $feedback->client->contact_info,
                    ],
                    'service' => $feedback->service ? [
                        'name' => $feedback->service->name,
                    ] : null,
                    'employee' => $feedback->employee ? [
                        'full_name' => $feedback->employee->full_name,
                    ] : null,
                    'resolution' => [
                        'description' => $feedback->admin_resolution_description,
                        'comments' => $feedback->admin_comments,
                        'treated_by' => $feedback->treatedByUser?->full_name,
                    ],
                    'validation' => [
                        'expires_at' => $feedback->validation_expires_at->format('d/m/Y à H:i'),
                        'time_remaining' => $feedback->validation_expires_at->diffInHours(now()),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider un feedback par le client
     * (Soumission du formulaire de validation)
     */
    public function validateFeedback(ValidateFeedbackRequest $request, $token)
    {
        try {
            $feedback = Feedback::where('validation_token', $token)
                ->where('validation_expires_at', '>', now())
                ->where('client_validated', false)
                ->with(['client'])
                ->first();

            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lien de validation invalide ou expiré'
                ], 404);
            }

            // Calculer les points bonus selon le type et la satisfaction
            $bonusPoints = $this->calculateBonusPoints(
                $feedback->type, 
                $request->validation_status, 
                $request->satisfaction_rating
            );

            // Valider le feedback
            $feedback->validateByClient(
                $request->validation_status,
                $request->satisfaction_rating,
                $request->comment,
                $bonusPoints
            );

            // Créer un log de validation
            ValidationLog::createFromValidation($feedback, [
                'status' => $request->validation_status,
                'rating' => $request->satisfaction_rating,
                'comment' => $request->comment,
                'bonus_points' => $bonusPoints,
            ], $request);

            return response()->json([
                'success' => true,
                'message' => 'Merci pour votre validation !',
                'data' => [
                    'feedback_id' => $feedback->id,
                    'reference' => $feedback->reference,
                    'final_status' => $feedback->fresh()->status,
                    'bonus_points_earned' => $bonusPoints,
                    'client_total_points' => $feedback->client->fresh()->total_kalipoints + $feedback->client->fresh()->bonus_kalipoints,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister les validations en attente pour l'admin
     */
    public function getPendingValidations(Request $request)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $pendingValidations = $company->feedbacks()
                ->whereNotNull('validation_token')
                ->where('client_validated', false)
                ->where('validation_expires_at', '>', now())
                ->with(['client', 'service', 'employee'])
                ->orderBy('validation_expires_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_validations' => $pendingValidations->map(function ($feedback) {
                        return [
                            'id' => $feedback->id,
                            'reference' => $feedback->reference,
                            'type' => $feedback->type,
                            'title' => $feedback->title,
                            'client' => $feedback->client->full_name,
                            'client_email' => $feedback->client->email,
                            'service' => $feedback->service?->name,
                            'employee' => $feedback->employee?->full_name,
                            'expires_at' => $feedback->validation_expires_at,
                            'hours_remaining' => $feedback->validation_expires_at->diffInHours(now()),
                            'validation_url' => $feedback->getValidationUrl(),
                        ];
                    }),
                    'total_pending' => $pendingValidations->count(),
                    'expiring_soon' => $pendingValidations->filter(function ($feedback) {
                        return $feedback->validation_expires_at->diffInHours(now()) <= 6;
                    })->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des validations en attente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer un rappel de validation au client
     */
    public function sendValidationReminder(Request $request, $feedbackId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $feedback = $company->feedbacks()
                ->whereNotNull('validation_token')
                ->where('client_validated', false)
                ->where('validation_expires_at', '>', now())
                ->findOrFail($feedbackId);

            // Vérifier qu'un rappel n'a pas déjà été envoyé dans les dernières 6 heures
            if ($feedback->validation_reminded_at && 
                $feedback->validation_reminded_at->diffInHours(now()) < 6) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un rappel a déjà été envoyé récemment'
                ], 400);
            }

            // Envoyer l'email de rappel
            if ($feedback->client && $feedback->client->email) {
                Mail::to($feedback->client->email)->send(new FeedbackValidationMail($feedback, true));
                
                $feedback->update(['validation_reminded_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rappel envoyé au client'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du rappel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer les points bonus selon le type de feedback et la satisfaction
     */
    private function calculateBonusPoints($type, $validationStatus, $satisfactionRating = null)
    {
        $basePoints = 0;

        if ($type === 'incident') {
            switch ($validationStatus) {
                case 'satisfied':
                    $basePoints = 3;
                    break;
                case 'partially_satisfied':
                    $basePoints = 1;
                    break;
                case 'not_satisfied':
                    $basePoints = 0;
                    break;
            }
        } elseif ($type === 'suggestion') {
            switch ($validationStatus) {
                case 'satisfied':
                    $basePoints = 5;
                    break;
                case 'partially_satisfied':
                    $basePoints = 2;
                    break;
                case 'not_satisfied':
                    $basePoints = 0;
                    break;
            }
        }

        // Bonus supplémentaire basé sur la note de satisfaction (1-5 étoiles)
        if ($satisfactionRating && $satisfactionRating >= 4) {
            $basePoints += 1;
        }

        return $basePoints;
    }
}