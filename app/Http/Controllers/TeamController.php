<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use App\Models\Escalation;
use App\Mail\EscalationNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TeamController extends Controller
{
    /**
     * Ajouter un directeur
     */
    public function addDirector(Request $request)
    {
        return $this->addTeamMember($request, 'director');
    }

    /**
     * Ajouter un PDG/CEO
     */
    public function addCEO(Request $request)
    {
        return $this->addTeamMember($request, 'ceo');
    }

    /**
     * Ajouter un chef de service
     */
    public function addServiceHead(Request $request)
    {
        return $this->addTeamMember($request, 'service_head');
    }

    /**
     * Méthode générale pour ajouter un membre de l'équipe
     */
    private function addTeamMember(Request $request, string $role)
    {
        try {
            // Vérifier que l'utilisateur est manager
            $manager = $request->user();
            if ($manager->role !== 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seul le manager peut ajouter des membres à l\'équipe'
                ], 403);
            }

            // Récupérer l'entreprise du manager
            $company = Company::where('manager_id', $manager->id)->first();
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entreprise non trouvée'
                ], 404);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|string|max:20',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier qu'il n'y a pas déjà un utilisateur avec ce rôle
            $existingUser = User::where('company_id', $company->id)
                ->where('role', $role)
                ->first();

            if ($existingUser) {
                $roleLabel = $this->getRoleLabel($role);
                return response()->json([
                    'success' => false,
                    'message' => "Un {$roleLabel} existe déjà pour cette entreprise: {$existingUser->full_name}"
                ], 409);
            }

            // Générer OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpiresAt = Carbon::now()->addMinutes(10);

            // Créer l'utilisateur (non vérifié)
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $role,
                'company_id' => $company->id,
                'otp' => $otp,
                'otp_expires_at' => $otpExpiresAt,
                'email_verified_at' => null, // Pas encore vérifié
            ]);

            // Envoyer l'email d'invitation par équipe
            try {
                Mail::send('emails.otp-verification', [
                    'userName' => $user->full_name,
                    'otp' => $otp,
                    'expiresAt' => $otpExpiresAt->format('d/m/Y à H:i'),
                    'companyName' => $company->name,
                    'managerName' => $manager->full_name,
                    'role' => $this->getRoleLabel($role),
                    'isTeamInvitation' => true
                ], function ($message) use ($user, $role) {
                    $message->to($user->email)
                            ->subject('Invitation à rejoindre l\'équipe QualyWatch - ' . $this->getRoleLabel($role));
                });
            } catch (\Exception $e) {
                \Log::error("Erreur envoi email invitation {$role}: " . $e->getMessage());
                // Continuer même si l'email échoue
            }

            // Envoyer les alertes SLA existantes si c'est un directeur ou CEO
            $escalationsSent = $this->sendExistingEscalations($user, $company);

            // Log de l'action
            \Log::info("Nouveau {$role} ajouté par {$manager->full_name}: {$user->full_name} ({$user->email})");

            return response()->json([
                'success' => true,
                'message' => $this->getRoleLabel($role) . ' ajouté avec succès',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $role,
                        'role_label' => $this->getRoleLabel($role),
                        'company_id' => $company->id,
                        'company_name' => $company->name,
                        'is_verified' => false
                    ],
                    'otp_expires_at' => $otpExpiresAt->toISOString(),
                    'next_step' => 'Le ' . $this->getRoleLabel($role) . ' doit vérifier son email avec le code OTP envoyé',
                    'escalations_sent' => $escalationsSent,
                    'sla_alerts_info' => $escalationsSent > 0 ?
                        "✅ {$escalationsSent} alertes SLA existantes envoyées automatiquement" :
                        "ℹ️ Aucune escalation active à envoyer"
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Erreur ajout {$role}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du ' . $this->getRoleLabel($role),
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Lister l'équipe de l'entreprise
     */
    public function getTeam(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer l'entreprise
            $company = null;
            if ($user->role === 'manager') {
                $company = Company::where('manager_id', $user->id)->first();
            } else {
                $company = Company::find($user->company_id);
            }

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entreprise non trouvée'
                ], 404);
            }

            // Récupérer tous les membres de l'équipe
            $teamMembers = User::where('company_id', $company->id)
                ->orWhere('id', $company->manager_id)
                ->get()
                ->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'full_name' => $member->full_name,
                        'email' => $member->email,
                        'phone' => $member->phone,
                        'role' => $member->role,
                        'role_label' => $this->getRoleLabel($member->role),
                        'is_verified' => $member->email_verified_at !== null,
                        'created_at' => $member->created_at
                    ];
                })
                ->sortBy('role');

            return response()->json([
                'success' => true,
                'data' => [
                    'company' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'display_id' => $company->display_id
                    ],
                    'team_members' => $teamMembers->values(),
                    'total_members' => $teamMembers->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'équipe',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Supprimer un membre de l'équipe
     */
    public function removeMember(Request $request, $userId)
    {
        try {
            $manager = $request->user();
            if ($manager->role !== 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seul le manager peut supprimer des membres'
                ], 403);
            }

            $company = Company::where('manager_id', $manager->id)->first();
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entreprise non trouvée'
                ], 404);
            }

            $member = User::where('id', $userId)
                ->where('company_id', $company->id)
                ->first();

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Membre non trouvé'
                ], 404);
            }

            if ($member->id === $manager->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas vous supprimer vous-même'
                ], 400);
            }

            $memberName = $member->full_name;
            $memberRole = $this->getRoleLabel($member->role);

            $member->delete();

            return response()->json([
                'success' => true,
                'message' => "{$memberRole} {$memberName} supprimé avec succès"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Envoyer les escalations SLA existantes au nouveau membre
     */
    private function sendExistingEscalations(User $user, Company $company): int
    {
        // Ne pas envoyer d'alertes si ce n'est pas directeur ou CEO
        if (!in_array($user->role, ['director', 'ceo'])) {
            return 0;
        }

        try {
            // Récupérer les escalations actives pour cette entreprise
            $escalations = Escalation::whereHas('feedback', function($q) use ($company) {
                                        $q->where('company_id', $company->id);
                                    })
                                    ->where('is_resolved', false)
                                    ->with(['feedback', 'slaRule'])
                                    ->get();

            if ($escalations->isEmpty()) {
                \Log::info("Aucune escalation à envoyer pour {$user->full_name}");
                return 0;
            }

            $sentCount = 0;
            $relevantEscalations = collect();

            // Filtrer les escalations selon le rôle
            foreach ($escalations as $escalation) {
                $shouldReceive = false;

                // Directeur reçoit les escalations niveau 2 et 3
                if ($user->role === 'director' && in_array($escalation->escalation_level, [2, 3])) {
                    $shouldReceive = true;
                }

                // CEO reçoit les escalations niveau 3
                if ($user->role === 'ceo' && $escalation->escalation_level === 3) {
                    $shouldReceive = true;
                }

                if ($shouldReceive) {
                    $relevantEscalations->push($escalation);
                }
            }

            \Log::info("Envoi de {$relevantEscalations->count()} escalations à {$user->full_name} ({$user->role})");

            // Envoyer les emails d'escalation
            foreach ($relevantEscalations->take(10) as $escalation) { // Limiter à 10 pour éviter le spam
                try {
                    Mail::to($user->email)->send(new EscalationNotification($escalation, $user));
                    $sentCount++;

                    \Log::info("Escalation {$escalation->id} envoyée à {$user->email}");

                } catch (\Exception $e) {
                    \Log::error("Erreur envoi escalation {$escalation->id} à {$user->email}: " . $e->getMessage());
                }
            }

            if ($sentCount > 0) {
                \Log::info("✅ {$sentCount} alertes SLA envoyées à {$user->full_name} ({$user->email})");
            }

            return $sentCount;

        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi des escalations existantes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtenir le libellé du rôle
     */
    private function getRoleLabel(string $role): string
    {
        return match($role) {
            'manager' => 'Manager',
            'director' => 'Directeur',
            'ceo' => 'PDG',
            'service_head' => 'Chef de Service',
            'super_admin' => 'Super Admin',
            default => ucfirst($role)
        };
    }
}