<?php

namespace App\Http\Controllers;

use App\Models\SlaRule;
use App\Models\Escalation;
use App\Models\FeedbackType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SlaController extends Controller
{
    /**
     * Liste des règles SLA
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir toutes les règles SLA
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if ($companyId) {
                    $query = SlaRule::where('company_id', $companyId);
                } else {
                    $query = SlaRule::query();
                }
            } else {
                $query = SlaRule::forCompany($user->company->id);
            }

            // Filtres
            if ($request->feedback_type_id) {
                $query->where('feedback_type_id', $request->feedback_type_id);
            }

            if ($request->priority_level) {
                $query->where('priority_level', $request->priority_level);
            }

            if ($request->active_only) {
                $query->active();
            }

            $slaRules = $query->with(['company', 'feedbackType'])
                            ->ordered()
                            ->paginate(20);

            // Ajouter les attributs calculés
            $slaRules->getCollection()->transform(function ($rule) {
                $rule->append(['priority_label', 'priority_color', 'first_response_sla_hours', 'resolution_sla_hours']);
                return $rule;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'sla_rules' => $slaRules->items(),
                    'pagination' => [
                        'total' => $slaRules->total(),
                        'per_page' => $slaRules->perPage(),
                        'current_page' => $slaRules->currentPage(),
                        'last_page' => $slaRules->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des règles SLA',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle règle SLA
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'feedback_type_id' => 'required|exists:feedback_types,id',
                'priority_level' => 'required|integer|min:1|max:5',
                'first_response_sla' => 'required|integer|min:1',
                'resolution_sla' => 'required|integer|min:1',
                'escalation_level_1' => 'required|integer|min:1',
                'escalation_level_2' => 'required|integer|min:1',
                'escalation_level_3' => 'required|integer|min:1',
                'level_1_recipients' => 'required|array',
                'level_2_recipients' => 'required|array',
                'level_3_recipients' => 'required|array',
                'notification_channels' => 'required|array',
                'conditions' => 'nullable|array',
                'is_active' => 'boolean'
            ]);

            $user = $request->user();
            
            // Super admin peut créer pour n'importe quelle entreprise
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

            $slaRule = SlaRule::create([
                'company_id' => $companyId,
                'name' => $request->name,
                'description' => $request->description,
                'feedback_type_id' => $request->feedback_type_id,
                'priority_level' => $request->priority_level,
                'first_response_sla' => $request->first_response_sla,
                'resolution_sla' => $request->resolution_sla,
                'escalation_level_1' => $request->escalation_level_1,
                'escalation_level_2' => $request->escalation_level_2,
                'escalation_level_3' => $request->escalation_level_3,
                'level_1_recipients' => $request->level_1_recipients,
                'level_2_recipients' => $request->level_2_recipients,
                'level_3_recipients' => $request->level_3_recipients,
                'notification_channels' => $request->notification_channels,
                'conditions' => $request->conditions ?? [],
                'is_active' => $request->is_active ?? true,
                'sort_order' => $request->sort_order ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Règle SLA créée avec succès',
                'data' => $slaRule->load(['company', 'feedbackType'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la règle SLA',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une règle SLA
     */
    public function show(Request $request, $slaRuleId)
    {
        try {
            $user = $request->user();
            
            if ($user->role === 'super_admin') {
                $slaRule = SlaRule::with(['company', 'feedbackType', 'escalations.feedback'])
                                 ->findOrFail($slaRuleId);
            } else {
                $slaRule = SlaRule::forCompany($user->company->id)
                                 ->with(['company', 'feedbackType', 'escalations.feedback'])
                                 ->findOrFail($slaRuleId);
            }

            $slaRule->append(['priority_label', 'priority_color', 'first_response_sla_hours', 'resolution_sla_hours']);

            // Statistiques des escalades
            $escalationStats = $slaRule->escalations()
                ->select('escalation_level', DB::raw('count(*) as count'))
                ->groupBy('escalation_level')
                ->get()
                ->keyBy('escalation_level');

            $slaRule->escalation_stats = [
                'level_1' => $escalationStats->get(1)?->count ?? 0,
                'level_2' => $escalationStats->get(2)?->count ?? 0,
                'level_3' => $escalationStats->get(3)?->count ?? 0,
                'total' => $slaRule->escalations()->count(),
                'active' => $slaRule->escalations()->active()->count(),
                'resolved' => $slaRule->escalations()->resolved()->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $slaRule
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Règle SLA non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour une règle SLA
     */
    public function update(Request $request, $slaRuleId)
    {
        try {
            $request->validate([
                'name' => 'string|max:255',
                'description' => 'nullable|string|max:1000',
                'feedback_type_id' => 'exists:feedback_types,id',
                'priority_level' => 'integer|min:1|max:5',
                'first_response_sla' => 'integer|min:1',
                'resolution_sla' => 'integer|min:1',
                'escalation_level_1' => 'integer|min:1',
                'escalation_level_2' => 'integer|min:1',
                'escalation_level_3' => 'integer|min:1',
                'level_1_recipients' => 'array',
                'level_2_recipients' => 'array',
                'level_3_recipients' => 'array',
                'notification_channels' => 'array',
                'conditions' => 'nullable|array',
                'is_active' => 'boolean'
            ]);

            $user = $request->user();
            
            if ($user->role === 'super_admin') {
                $slaRule = SlaRule::findOrFail($slaRuleId);
            } else {
                $slaRule = SlaRule::forCompany($user->company->id)->findOrFail($slaRuleId);
            }

            $slaRule->update($request->only([
                'name', 'description', 'feedback_type_id', 'priority_level',
                'first_response_sla', 'resolution_sla',
                'escalation_level_1', 'escalation_level_2', 'escalation_level_3',
                'level_1_recipients', 'level_2_recipients', 'level_3_recipients',
                'notification_channels', 'conditions', 'is_active', 'sort_order'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Règle SLA mise à jour avec succès',
                'data' => $slaRule->fresh(['company', 'feedbackType'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la règle SLA',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une règle SLA
     */
    public function destroy(Request $request, $slaRuleId)
    {
        try {
            $user = $request->user();
            
            if ($user->role === 'super_admin') {
                $slaRule = SlaRule::findOrFail($slaRuleId);
            } else {
                $slaRule = SlaRule::forCompany($user->company->id)->findOrFail($slaRuleId);
            }

            // Vérifier s'il y a des escalades actives
            if ($slaRule->escalations()->active()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une règle SLA avec des escalades actives'
                ], 400);
            }

            $slaRule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Règle SLA supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la règle SLA',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des escalades
     */
    public function escalations(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir toutes les escalades
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if ($companyId) {
                    $query = Escalation::getEscalationsByCompany($companyId);
                } else {
                    $query = Escalation::query();
                }
            } else {
                $query = Escalation::getEscalationsByCompany($user->company->id);
            }

            // Filtres
            if ($request->escalation_level) {
                $query->where('escalation_level', $request->escalation_level);
            }

            if ($request->status) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'resolved') {
                    $query->resolved();
                }
            }

            if ($request->recent_hours) {
                $query->recent((int) $request->recent_hours);
            }

            $escalations = $query->with(['feedback.client', 'slaRule'])
                                ->orderBy('escalated_at', 'desc')
                                ->paginate(20);

            // Ajouter les attributs calculés
            $escalations->getCollection()->transform(function ($escalation) {
                $escalation->append(['level_label', 'level_color', 'trigger_reason_label', 'escalation_age', 'resolution_time_hours']);
                return $escalation;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'escalations' => $escalations->items(),
                    'pagination' => [
                        'total' => $escalations->total(),
                        'per_page' => $escalations->perPage(),
                        'current_page' => $escalations->currentPage(),
                        'last_page' => $escalations->lastPage(),
                    ],
                    'stats' => [
                        'total_active' => Escalation::getActiveEscalationsCount(),
                        'critical_active' => Escalation::getCriticalEscalationsCount(),
                        'recent_24h' => Escalation::getRecentEscalations(24)->count(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des escalades',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Résoudre une escalade
     */
    public function resolveEscalation(Request $request, $escalationId)
    {
        try {
            $request->validate([
                'resolution_notes' => 'nullable|string|max:2000'
            ]);

            $user = $request->user();
            
            if ($user->role === 'super_admin') {
                $escalation = Escalation::findOrFail($escalationId);
            } else {
                $escalation = Escalation::getEscalationsByCompany($user->company->id)
                                      ->where('id', $escalationId)
                                      ->firstOrFail();
            }

            if ($escalation->is_resolved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette escalade est déjà résolue'
                ], 400);
            }

            $escalation->resolve($request->resolution_notes);

            return response()->json([
                'success' => true,
                'message' => 'Escalade résolue avec succès',
                'data' => $escalation->fresh(['feedback', 'slaRule'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la résolution de l\'escalade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques SLA
     */
    public function stats(Request $request)
    {
        try {
            $user = $request->user();
            
            $companyId = $user->role === 'super_admin' ? 
                $request->get('company_id') : 
                $user->company->id;

            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company ID requis'
                ], 400);
            }

            $stats = [
                'sla_rules_count' => SlaRule::forCompany($companyId)->active()->count(),
                'escalations' => [
                    'total' => Escalation::getEscalationsByCompany($companyId)->count(),
                    'active' => Escalation::getEscalationsByCompany($companyId)->active()->count(),
                    'resolved' => Escalation::getEscalationsByCompany($companyId)->resolved()->count(),
                    'by_level' => Escalation::getEscalationsByCompany($companyId)
                                          ->select('escalation_level', DB::raw('count(*) as count'))
                                          ->groupBy('escalation_level')
                                          ->get()
                                          ->keyBy('escalation_level'),
                    'recent_24h' => Escalation::getEscalationsByCompany($companyId)->recent(24)->count(),
                ],
                'performance' => [
                    'average_resolution_time' => Escalation::getEscalationsByCompany($companyId)
                                                         ->resolved()
                                                         ->whereNotNull('resolved_at')
                                                         ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, escalated_at, resolved_at)) as avg_minutes')
                                                         ->value('avg_minutes'),
                    'sla_breach_rate' => $this->calculateSlaBreachRate($companyId),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateSlaBreachRate($companyId)
    {
        $totalFeedbacks = \App\Models\Feedback::where('company_id', $companyId)
                                             ->where('created_at', '>=', now()->subDays(30))
                                             ->count();

        if ($totalFeedbacks === 0) return 0;

        $breachedFeedbacks = Escalation::getEscalationsByCompany($companyId)
                                     ->where('created_at', '>=', now()->subDays(30))
                                     ->count();

        return round(($breachedFeedbacks / $totalFeedbacks) * 100, 2);
    }
}