<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\RewardClaim;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RewardController extends Controller
{
    /**
     * Liste des récompenses disponibles pour une entreprise
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir les récompenses d'une entreprise spécifique
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if ($companyId) {
                    $company = \App\Models\Company::findOrFail($companyId);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Super admin doit spécifier company_id en paramètre'
                    ], 400);
                }
            } else {
                $company = $user->company;
            }

            $rewards = $company->rewards()
                ->with(['claims' => function ($query) {
                    $query->whereIn('status', ['pending', 'approved', 'delivered']);
                }])
                ->when($request->type, function ($query, $type) {
                    $query->where('type', $type);
                })
                ->when($request->status === 'available', function ($query) {
                    $query->available();
                })
                ->when($request->status === 'active', function ($query) {
                    $query->active();
                })
                ->orderBy('kalipoints_cost')
                ->paginate(20);

            // Ajouter les informations calculées
            $rewards->getCollection()->transform(function ($reward) {
                $reward->append(['available_stock', 'claims_count', 'is_available']);
                return $reward;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'rewards' => $rewards->items(),
                    'pagination' => [
                        'total' => $rewards->total(),
                        'per_page' => $rewards->perPage(),
                        'current_page' => $rewards->currentPage(),
                        'last_page' => $rewards->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des récompenses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle récompense
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'type' => 'required|in:discount,gift,service,experience,digital',
                'kalipoints_cost' => 'required|integer|min:1',
                'details' => 'nullable|array',
                'stock' => 'nullable|integer|min:0',
                'valid_from' => 'nullable|date',
                'valid_until' => 'nullable|date|after:valid_from',
                'is_active' => 'boolean'
            ]);

            $user = $request->user();
            
            // Super admin peut créer pour une entreprise spécifique
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if (!$companyId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Super admin doit spécifier company_id'
                    ], 400);
                }
            } else {
                $companyId = $user->company->id;
            }

            $reward = Reward::create([
                'company_id' => $companyId,
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'kalipoints_cost' => $request->kalipoints_cost,
                'details' => $request->details ?? [],
                'stock' => $request->stock,
                'is_active' => $request->is_active ?? true,
                'valid_from' => $request->valid_from,
                'valid_until' => $request->valid_until,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Récompense créée avec succès',
                'data' => $reward->load('company')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la récompense',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une récompense spécifique
     */
    public function show(Request $request, $rewardId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir n'importe quelle récompense
            if ($user->role === 'super_admin') {
                $reward = Reward::with(['company', 'claims.client'])->findOrFail($rewardId);
            } else {
                $company = $user->company;
                $reward = $company->rewards()
                    ->with(['claims.client'])
                    ->findOrFail($rewardId);
            }

            // Statistiques des réclamations
            $claimsStats = $reward->claims()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $reward->claims_stats = [
                'pending' => $claimsStats->get('pending')?->count ?? 0,
                'approved' => $claimsStats->get('approved')?->count ?? 0,
                'delivered' => $claimsStats->get('delivered')?->count ?? 0,
                'cancelled' => $claimsStats->get('cancelled')?->count ?? 0,
                'total' => $reward->claims()->count(),
            ];

            $reward->append(['available_stock', 'is_available']);

            return response()->json([
                'success' => true,
                'data' => $reward
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Récompense non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour une récompense
     */
    public function update(Request $request, $rewardId)
    {
        try {
            $request->validate([
                'name' => 'string|max:255',
                'description' => 'string|max:2000',
                'type' => 'in:discount,gift,service,experience,digital',
                'kalipoints_cost' => 'integer|min:1',
                'details' => 'nullable|array',
                'stock' => 'nullable|integer|min:0',
                'valid_from' => 'nullable|date',
                'valid_until' => 'nullable|date|after:valid_from',
                'is_active' => 'boolean'
            ]);

            $user = $request->user();
            
            // Super admin peut modifier n'importe quelle récompense
            if ($user->role === 'super_admin') {
                $reward = Reward::findOrFail($rewardId);
            } else {
                $company = $user->company;
                $reward = $company->rewards()->findOrFail($rewardId);
            }

            $reward->update($request->only([
                'name', 'description', 'type', 'kalipoints_cost',
                'details', 'stock', 'is_active', 'valid_from', 'valid_until'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Récompense mise à jour avec succès',
                'data' => $reward->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la récompense',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une récompense
     */
    public function destroy(Request $request, $rewardId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut supprimer n'importe quelle récompense
            if ($user->role === 'super_admin') {
                $reward = Reward::findOrFail($rewardId);
            } else {
                $company = $user->company;
                $reward = $company->rewards()->findOrFail($rewardId);
            }

            // Vérifier s'il y a des réclamations en attente
            if ($reward->claims()->whereIn('status', ['pending', 'approved'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une récompense avec des réclamations en cours'
                ], 400);
            }

            $reward->delete();

            return response()->json([
                'success' => true,
                'message' => 'Récompense supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la récompense',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réclamer une récompense (pour les clients)
     */
    public function claim(Request $request, $rewardId)
    {
        try {
            $request->validate([
                'client_email' => 'required|email',
                'claim_details' => 'nullable|array'
            ]);

            // Trouver le client
            $client = Client::where('email', $request->client_email)->first();
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé'
                ], 404);
            }

            // Trouver la récompense
            $reward = Reward::findOrFail($rewardId);

            // Vérifier si la récompense est disponible
            if (!$reward->canBeClaimed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette récompense n\'est pas disponible'
                ], 400);
            }

            // Vérifier si le client a assez de KaliPoints
            if ($client->total_kalipoints < $reward->kalipoints_cost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Points KaliPoints insuffisants',
                    'data' => [
                        'required' => $reward->kalipoints_cost,
                        'available' => $client->total_kalipoints,
                        'missing' => $reward->kalipoints_cost - $client->total_kalipoints
                    ]
                ], 400);
            }

            // Créer la réclamation
            $claim = RewardClaim::create([
                'client_id' => $client->id,
                'reward_id' => $reward->id,
                'company_id' => $reward->company_id,
                'kalipoints_spent' => $reward->kalipoints_cost,
                'claim_details' => $request->claim_details ?? []
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Récompense réclamée avec succès',
                'data' => [
                    'claim_id' => $claim->id,
                    'claim_code' => $claim->claim_code,
                    'status' => $claim->status,
                    'kalipoints_spent' => $claim->kalipoints_spent,
                    'remaining_kalipoints' => $client->fresh()->total_kalipoints
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réclamation de la récompense',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des réclamations de récompenses
     */
    public function claims(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir toutes les réclamations
            if ($user->role === 'super_admin') {
                $query = RewardClaim::with(['client', 'reward', 'company']);
                
                if ($request->company_id) {
                    $query->where('company_id', $request->company_id);
                }
            } else {
                $company = $user->company;
                $query = $company->rewardClaims()->with(['client', 'reward']);
            }

            // Filtres
            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->client_email) {
                $query->whereHas('client', function ($q) use ($request) {
                    $q->where('email', 'like', '%' . $request->client_email . '%');
                });
            }

            $claims = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'claims' => $claims->items(),
                    'pagination' => [
                        'total' => $claims->total(),
                        'per_page' => $claims->perPage(),
                        'current_page' => $claims->currentPage(),
                        'last_page' => $claims->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des réclamations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approuver une réclamation
     */
    public function approveClaim(Request $request, $claimId)
    {
        try {
            $request->validate([
                'notes' => 'nullable|string|max:1000'
            ]);

            $user = $request->user();
            
            // Super admin peut approuver n'importe quelle réclamation
            if ($user->role === 'super_admin') {
                $claim = RewardClaim::findOrFail($claimId);
            } else {
                $company = $user->company;
                $claim = $company->rewardClaims()->findOrFail($claimId);
            }

            if ($claim->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les réclamations en attente peuvent être approuvées'
                ], 400);
            }

            $claim->approve($request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Réclamation approuvée avec succès',
                'data' => $claim->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation de la réclamation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer une réclamation comme livrée
     */
    public function deliverClaim(Request $request, $claimId)
    {
        try {
            $request->validate([
                'notes' => 'nullable|string|max:1000'
            ]);

            $user = $request->user();
            
            // Super admin peut livrer n'importe quelle réclamation
            if ($user->role === 'super_admin') {
                $claim = RewardClaim::findOrFail($claimId);
            } else {
                $company = $user->company;
                $claim = $company->rewardClaims()->findOrFail($claimId);
            }

            if (!in_array($claim->status, ['pending', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les réclamations en attente ou approuvées peuvent être livrées'
                ], 400);
            }

            $claim->deliver($request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Réclamation marquée comme livrée',
                'data' => $claim->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la livraison de la réclamation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler une réclamation
     */
    public function cancelClaim(Request $request, $claimId)
    {
        try {
            $request->validate([
                'notes' => 'required|string|max:1000'
            ]);

            $user = $request->user();
            
            // Super admin peut annuler n'importe quelle réclamation
            if ($user->role === 'super_admin') {
                $claim = RewardClaim::findOrFail($claimId);
            } else {
                $company = $user->company;
                $claim = $company->rewardClaims()->findOrFail($claimId);
            }

            if (in_array($claim->status, ['delivered', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réclamation ne peut pas être annulée'
                ], 400);
            }

            $claim->cancel($request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Réclamation annulée avec succès',
                'data' => $claim->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la réclamation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}