<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Http\Requests\CreateServiceRequest;
use App\Http\Requests\UpdateServiceRequest;

class ServiceController extends Controller
{
    /**
     * Lister tous les services de l'entreprise
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir les services d'une entreprise spécifique
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

            $services = $company->services()
                ->withCount(['feedbacks', 'employees'])
                ->withCount(['feedbacks as positive_feedbacks' => function ($query) {
                    $query->where('type', 'appreciation');
                }])
                ->withCount(['feedbacks as negative_feedbacks' => function ($query) {
                    $query->where('type', 'incident');
                }])
                ->withCount(['feedbacks as suggestions_count' => function ($query) {
                    $query->where('type', 'suggestion');
                }])
                ->get()
                ->map(function ($service) {
                    $service->satisfaction_score = $service->feedbacks_count > 0 
                        ? round(($service->positive_feedbacks / $service->feedbacks_count) * 100, 1) 
                        : 0;
                    
                    // Pourcentages de qualité par type de feedback
                    $service->quality_percentages = [
                        'positif' => [
                            'count' => $service->positive_feedbacks,
                            'percentage' => $service->feedbacks_count > 0 ? round(($service->positive_feedbacks / $service->feedbacks_count) * 100, 1) : 0,
                        ],
                        'negatif' => [
                            'count' => $service->negative_feedbacks,
                            'percentage' => $service->feedbacks_count > 0 ? round(($service->negative_feedbacks / $service->feedbacks_count) * 100, 1) : 0,
                        ],
                        'suggestion' => [
                            'count' => $service->suggestions_count,
                            'percentage' => $service->feedbacks_count > 0 ? round(($service->suggestions_count / $service->feedbacks_count) * 100, 1) : 0,
                        ],
                    ];
                    
                    $service->average_kalipoints = $service->feedbacks()->avg('kalipoints') ?? 0;
                    $service->average_kalipoints_positif = $service->feedbacks()->where('type', 'appreciation')->avg('kalipoints') ?? 0;
                    $service->average_kalipoints_negatif = $service->feedbacks()->where('type', 'incident')->avg('kalipoints') ?? 0;
                    $service->average_kalipoints_suggestion = $service->feedbacks()->where('type', 'suggestion')->avg('kalipoints') ?? 0;
                    
                    // Métriques par statut pour feedbacks négatifs
                    $service->negative_new = $service->feedbacks()->where('type', 'incident')->where('status_id', 1)->count();
                    $service->negative_seen = $service->feedbacks()->where('type', 'incident')->where('status_id', 2)->count();
                    $service->negative_in_progress = $service->feedbacks()->where('type', 'incident')->where('status_id', 3)->count();
                    $service->negative_treated = $service->feedbacks()->where('type', 'incident')->where('status_id', 4)->count();
                    $service->negative_resolved = $service->feedbacks()->where('type', 'incident')->where('status_id', 5)->count();
                    $service->negative_partially_resolved = $service->feedbacks()->where('type', 'incident')->where('status_id', 6)->count();
                    $service->negative_not_resolved = $service->feedbacks()->where('type', 'incident')->where('status_id', 7)->count();
                    
                    // Moyennes KaliPoints par statut - Négatifs
                    $service->avg_kalipoints_negative_resolved = $service->feedbacks()->where('type', 'incident')->where('status_id', 5)->avg('kalipoints') ?? 0;
                    $service->avg_kalipoints_negative_partial = $service->feedbacks()->where('type', 'incident')->where('status_id', 6)->avg('kalipoints') ?? 0;
                    $service->avg_kalipoints_negative_not_resolved = $service->feedbacks()->where('type', 'incident')->where('status_id', 7)->avg('kalipoints') ?? 0;
                    
                    // Métriques par statut pour suggestions
                    $service->suggestion_new = $service->feedbacks()->where('type', 'suggestion')->where('status_id', 1)->count();
                    $service->suggestion_seen = $service->feedbacks()->where('type', 'suggestion')->where('status_id', 2)->count();
                    $service->suggestion_in_progress = $service->feedbacks()->where('type', 'suggestion')->where('status_id', 3)->count();
                    $service->suggestion_treated = $service->feedbacks()->where('type', 'suggestion')->where('status_id', 4)->count();
                    $service->suggestion_implemented = $service->feedbacks()->where('type', 'suggestion')->where('status_id', 8)->count();
                    $service->suggestion_partially_implemented = $service->feedbacks()->where('type', 'suggestion')->where('status_id', 9)->count();
                    $service->suggestion_rejected = $service->feedbacks()->where('type', 'suggestion')->where('status_id', 10)->count();
                    
                    // Moyennes KaliPoints par statut - Suggestions
                    $service->avg_kalipoints_suggestion_implemented = $service->feedbacks()->where('type', 'suggestion')->where('status_id', 8)->avg('kalipoints') ?? 0;
                    $service->avg_kalipoints_suggestion_partial = $service->feedbacks()->where('type', 'suggestion')->where('status_id', 9)->avg('kalipoints') ?? 0;
                    $service->avg_kalipoints_suggestion_rejected = $service->feedbacks()->where('type', 'suggestion')->where('status_id', 10)->avg('kalipoints') ?? 0;
                    
                    return $service;
                });

            return response()->json([
                'success' => true,
                'data' => $services
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau service
     */
    public function store(CreateServiceRequest $request)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $service = $company->services()->create([
                'name' => $request->name,
                'description' => $request->description,
                'icon' => $request->icon ?? 'briefcase',
                'color' => $request->color ?? '#3B82F6',
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service créé avec succès',
                'data' => $service
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un service spécifique
     */
    public function show(Request $request, $serviceId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir n'importe quel service
            if ($user->role === 'super_admin') {
                $service = Service::with(['company', 'employees' => function ($query) {
                    $query->withCount('feedbacks');
                }])
                ->withCount(['feedbacks', 'employees'])
                ->findOrFail($serviceId);
                $company = $service->company;
            } else {
                $company = $user->company;
                $service = $company->services()
                    ->withCount(['feedbacks', 'employees'])
                    ->with(['employees' => function ($query) {
                        $query->withCount('feedbacks');
                    }])
                    ->findOrFail($serviceId);
            }

            // Statistiques du service
            $service->positive_feedbacks = $service->feedbacks()->where('type', 'appreciation')->count();
            $service->negative_feedbacks = $service->feedbacks()->where('type', 'incident')->count();
            $service->suggestions_count = $service->feedbacks()->where('type', 'suggestion')->count();
            $service->satisfaction_score = $service->feedbacks_count > 0 
                ? round(($service->positive_feedbacks / $service->feedbacks_count) * 100, 1) 
                : 0;
            
            // Pourcentages de qualité par type de feedback
            $service->quality_percentages = [
                'positif' => [
                    'count' => $service->positive_feedbacks,
                    'percentage' => $service->feedbacks_count > 0 ? round(($service->positive_feedbacks / $service->feedbacks_count) * 100, 1) : 0,
                ],
                'negatif' => [
                    'count' => $service->negative_feedbacks,
                    'percentage' => $service->feedbacks_count > 0 ? round(($service->negative_feedbacks / $service->feedbacks_count) * 100, 1) : 0,
                ],
                'suggestion' => [
                    'count' => $service->suggestions_count,
                    'percentage' => $service->feedbacks_count > 0 ? round(($service->suggestions_count / $service->feedbacks_count) * 100, 1) : 0,
                ],
            ];
            
            $service->average_kalipoints = $service->feedbacks()->avg('kalipoints') ?? 0;
            $service->average_kalipoints_positif = $service->feedbacks()->where('type', 'appreciation')->avg('kalipoints') ?? 0;
            $service->average_kalipoints_negatif = $service->feedbacks()->where('type', 'incident')->avg('kalipoints') ?? 0;
            $service->average_kalipoints_suggestion = $service->feedbacks()->where('type', 'suggestion')->avg('kalipoints') ?? 0;

            return response()->json([
                'success' => true,
                'data' => $service
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour un service
     */
    public function update(UpdateServiceRequest $request, $serviceId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $service = $company->services()->findOrFail($serviceId);

            $service->update([
                'name' => $request->name,
                'description' => $request->description,
                'icon' => $request->icon,
                'color' => $request->color,
                'is_active' => $request->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service mis à jour avec succès',
                'data' => $service
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un service
     */
    public function destroy(Request $request, $serviceId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $service = $company->services()->findOrFail($serviceId);

            // Vérifier s'il y a des feedbacks liés
            if ($service->feedbacks()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un service qui a des feedbacks associés'
                ], 400);
            }

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un service
     */
    public function toggleStatus(Request $request, $serviceId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $service = $company->services()->findOrFail($serviceId);
            $service->update(['is_active' => !$service->is_active]);

            return response()->json([
                'success' => true,
                'message' => $service->is_active ? 'Service activé' : 'Service désactivé',
                'data' => $service
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}