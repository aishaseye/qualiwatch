<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\CreateEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Imports\EmployeesImport;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    /**
     * Lister tous les employés de l'entreprise
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir les employés d'une entreprise spécifique
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

            $query = $company->employees()->with('service');

            // Filtres
            if ($request->service_id) {
                $query->where('service_id', $request->service_id);
            }

            if ($request->is_active !== null) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->search . '%')
                      ->orWhere('last_name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%')
                      ->orWhere('position', 'like', '%' . $request->search . '%');
                });
            }

            $employees = $query
                ->withCount(['feedbacks', 'feedbacks as positive_feedbacks' => function ($q) {
                    $q->where('type', 'appreciation');
                }, 'feedbacks as negative_feedbacks' => function ($q) {
                    $q->where('type', 'incident');
                }, 'feedbacks as suggestions_count' => function ($q) {
                    $q->where('type', 'suggestion');
                }])
                ->orderBy('created_at', 'desc')
                ->paginate(50);

            // Calculer le score de performance pour chaque employé
            $employees->getCollection()->transform(function ($employee) {
                $employee->performance_score = $employee->feedbacks_count > 0 
                    ? round((($employee->positive_feedbacks - $employee->negative_feedbacks) / $employee->feedbacks_count + 1) * 50, 1)
                    : 0;
                
                // Pourcentages de qualité par type de feedback
                $employee->quality_percentages = [
                    'positif' => [
                        'count' => $employee->positive_feedbacks,
                        'percentage' => $employee->feedbacks_count > 0 ? round(($employee->positive_feedbacks / $employee->feedbacks_count) * 100, 1) : 0,
                    ],
                    'negatif' => [
                        'count' => $employee->negative_feedbacks,
                        'percentage' => $employee->feedbacks_count > 0 ? round(($employee->negative_feedbacks / $employee->feedbacks_count) * 100, 1) : 0,
                    ],
                    'suggestion' => [
                        'count' => $employee->suggestions_count,
                        'percentage' => $employee->feedbacks_count > 0 ? round(($employee->suggestions_count / $employee->feedbacks_count) * 100, 1) : 0,
                    ],
                ];
                
                $employee->total_kalipoints = $employee->feedbacks()->sum('kalipoints');
                $employee->average_kalipoints = $employee->feedbacks()->avg('kalipoints') ?? 0;
                $employee->average_kalipoints_positif = $employee->feedbacks()->where('type', 'appreciation')->avg('kalipoints') ?? 0;
                $employee->average_kalipoints_negatif = $employee->feedbacks()->where('type', 'incident')->avg('kalipoints') ?? 0;
                $employee->average_kalipoints_suggestion = $employee->feedbacks()->where('type', 'suggestion')->avg('kalipoints') ?? 0;
                
                // Métriques par statut pour feedbacks négatifs
                $employee->negative_new = $employee->feedbacks()->where('type', 'incident')->where('status_id', 1)->count();
                $employee->negative_seen = $employee->feedbacks()->where('type', 'incident')->where('status_id', 2)->count();
                $employee->negative_in_progress = $employee->feedbacks()->where('type', 'incident')->where('status_id', 3)->count();
                $employee->negative_treated = $employee->feedbacks()->where('type', 'incident')->where('status_id', 4)->count();
                $employee->negative_resolved = $employee->feedbacks()->where('type', 'incident')->where('status_id', 5)->count();
                $employee->negative_partially_resolved = $employee->feedbacks()->where('type', 'incident')->where('status_id', 6)->count();
                $employee->negative_not_resolved = $employee->feedbacks()->where('type', 'incident')->where('status_id', 7)->count();
                
                // Moyennes KaliPoints par statut - Négatifs
                $employee->avg_kalipoints_negative_resolved = $employee->feedbacks()->where('type', 'incident')->where('status_id', 5)->avg('kalipoints') ?? 0;
                $employee->avg_kalipoints_negative_partial = $employee->feedbacks()->where('type', 'incident')->where('status_id', 6)->avg('kalipoints') ?? 0;
                $employee->avg_kalipoints_negative_not_resolved = $employee->feedbacks()->where('type', 'incident')->where('status_id', 7)->avg('kalipoints') ?? 0;
                
                // Métriques par statut pour suggestions
                $employee->suggestion_new = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 1)->count();
                $employee->suggestion_seen = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 2)->count();
                $employee->suggestion_in_progress = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 3)->count();
                $employee->suggestion_treated = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 4)->count();
                $employee->suggestion_implemented = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 8)->count();
                $employee->suggestion_partially_implemented = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 9)->count();
                $employee->suggestion_rejected = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 10)->count();
                
                // Moyennes KaliPoints par statut - Suggestions
                $employee->avg_kalipoints_suggestion_implemented = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 8)->avg('kalipoints') ?? 0;
                $employee->avg_kalipoints_suggestion_partial = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 9)->avg('kalipoints') ?? 0;
                $employee->avg_kalipoints_suggestion_rejected = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 10)->avg('kalipoints') ?? 0;
                
                return $employee;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'employees' => $employees->items(),
                    'pagination' => [
                        'total' => $employees->total(),
                        'per_page' => $employees->perPage(),
                        'current_page' => $employees->currentPage(),
                        'last_page' => $employees->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des employés',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouvel employé
     */
    public function store(CreateEmployeeRequest $request)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            // Gérer l'upload de la photo
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('employees', 'public');
                $photoPath = basename($photoPath);
            }

            $employee = $company->employees()->create([
                'service_id' => $request->service_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'position' => $request->position,
                'photo' => $photoPath,
                'hire_date' => $request->hire_date,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Employé créé avec succès',
                'data' => $employee->load('service')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'employé',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un employé spécifique
     */
    public function show(Request $request, $employeeId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir n'importe quel employé
            if ($user->role === 'super_admin') {
                $employee = Employee::with(['service', 'feedbacks.client', 'company'])
                    ->withCount(['feedbacks', 'feedbacks as positive_feedbacks' => function ($q) {
                        $q->where('type', 'appreciation');
                    }, 'feedbacks as negative_feedbacks' => function ($q) {
                        $q->where('type', 'incident');
                    }, 'feedbacks as suggestions_count' => function ($q) {
                        $q->where('type', 'suggestion');
                    }])
                    ->findOrFail($employeeId);
            } else {
                $company = $user->company;
                $employee = $company->employees()
                    ->with(['service', 'feedbacks.client'])
                    ->withCount(['feedbacks', 'feedbacks as positive_feedbacks' => function ($q) {
                        $q->where('type', 'appreciation');
                    }, 'feedbacks as negative_feedbacks' => function ($q) {
                        $q->where('type', 'incident');
                    }, 'feedbacks as suggestions_count' => function ($q) {
                        $q->where('type', 'suggestion');
                    }])
                    ->findOrFail($employeeId);
            }

            // Statistiques détaillées
            $employee->performance_score = $employee->feedbacks_count > 0 
                ? round((($employee->positive_feedbacks - $employee->negative_feedbacks) / $employee->feedbacks_count + 1) * 50, 1)
                : 0;
            
            // Pourcentages de qualité par type de feedback
            $employee->quality_percentages = [
                'positif' => [
                    'count' => $employee->positive_feedbacks,
                    'percentage' => $employee->feedbacks_count > 0 ? round(($employee->positive_feedbacks / $employee->feedbacks_count) * 100, 1) : 0,
                ],
                'negatif' => [
                    'count' => $employee->negative_feedbacks,
                    'percentage' => $employee->feedbacks_count > 0 ? round(($employee->negative_feedbacks / $employee->feedbacks_count) * 100, 1) : 0,
                ],
                'suggestion' => [
                    'count' => $employee->suggestions_count,
                    'percentage' => $employee->feedbacks_count > 0 ? round(($employee->suggestions_count / $employee->feedbacks_count) * 100, 1) : 0,
                ],
            ];
            
            $employee->total_kalipoints = $employee->feedbacks()->sum('kalipoints');
            $employee->average_kalipoints = $employee->feedbacks()->avg('kalipoints') ?? 0;
            $employee->average_kalipoints_positif = $employee->feedbacks()->where('type', 'appreciation')->avg('kalipoints') ?? 0;
            $employee->average_kalipoints_negatif = $employee->feedbacks()->where('type', 'incident')->avg('kalipoints') ?? 0;
            $employee->average_kalipoints_suggestion = $employee->feedbacks()->where('type', 'suggestion')->avg('kalipoints') ?? 0;

            // Métriques par statut pour feedbacks négatifs
            $employee->negative_new = $employee->feedbacks()->where('type', 'incident')->where('status_id', 1)->count();
            $employee->negative_seen = $employee->feedbacks()->where('type', 'incident')->where('status_id', 2)->count();
            $employee->negative_in_progress = $employee->feedbacks()->where('type', 'incident')->where('status_id', 3)->count();
            $employee->negative_treated = $employee->feedbacks()->where('type', 'incident')->where('status_id', 4)->count();
            $employee->negative_resolved = $employee->feedbacks()->where('type', 'incident')->where('status_id', 5)->count();
            $employee->negative_partially_resolved = $employee->feedbacks()->where('type', 'incident')->where('status_id', 6)->count();
            $employee->negative_not_resolved = $employee->feedbacks()->where('type', 'incident')->where('status_id', 7)->count();
            
            // Moyennes KaliPoints par statut - Négatifs
            $employee->avg_kalipoints_negative_resolved = $employee->feedbacks()->where('type', 'incident')->where('status_id', 5)->avg('kalipoints') ?? 0;
            $employee->avg_kalipoints_negative_partial = $employee->feedbacks()->where('type', 'incident')->where('status_id', 6)->avg('kalipoints') ?? 0;
            $employee->avg_kalipoints_negative_not_resolved = $employee->feedbacks()->where('type', 'incident')->where('status_id', 7)->avg('kalipoints') ?? 0;
            
            // Métriques par statut pour suggestions
            $employee->suggestion_new = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 1)->count();
            $employee->suggestion_seen = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 2)->count();
            $employee->suggestion_in_progress = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 3)->count();
            $employee->suggestion_treated = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 4)->count();
            $employee->suggestion_implemented = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 8)->count();
            $employee->suggestion_partially_implemented = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 9)->count();
            $employee->suggestion_rejected = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 10)->count();
            
            // Moyennes KaliPoints par statut - Suggestions
            $employee->avg_kalipoints_suggestion_implemented = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 8)->avg('kalipoints') ?? 0;
            $employee->avg_kalipoints_suggestion_partial = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 9)->avg('kalipoints') ?? 0;
            $employee->avg_kalipoints_suggestion_rejected = $employee->feedbacks()->where('type', 'suggestion')->where('status_id', 10)->avg('kalipoints') ?? 0;

            // Derniers feedbacks
            $employee->recent_feedbacks = $employee->feedbacks()
                ->with('client')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $employee
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour un employé
     */
    public function update(UpdateEmployeeRequest $request, $employeeId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $employee = $company->employees()->findOrFail($employeeId);

            // Gérer l'upload de la nouvelle photo
            $photoPath = $employee->photo;
            if ($request->hasFile('photo')) {
                // Supprimer l'ancienne photo
                if ($employee->photo) {
                    Storage::disk('public')->delete('employees/' . $employee->photo);
                }
                
                $photoPath = $request->file('photo')->store('employees', 'public');
                $photoPath = basename($photoPath);
            }

            $employee->update([
                'service_id' => $request->service_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'position' => $request->position,
                'photo' => $photoPath,
                'hire_date' => $request->hire_date,
                'is_active' => $request->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Employé mis à jour avec succès',
                'data' => $employee->load('service')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'employé',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un employé
     */
    public function destroy(Request $request, $employeeId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $employee = $company->employees()->findOrFail($employeeId);

            // Supprimer la photo
            if ($employee->photo) {
                Storage::disk('public')->delete('employees/' . $employee->photo);
            }

            $employee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Employé supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'employé',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import Excel d'employés
     */
    public function importExcel(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // 5MB max
            ]);

            $user = $request->user();
            $company = $user->company;

            $import = new EmployeesImport($company);
            Excel::import($import, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Import terminé avec succès',
                'data' => [
                    'imported_count' => $import->getImportedCount(),
                    'errors' => $import->getErrors(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger un modèle Excel pour l'import
     */
    public function downloadTemplate()
    {
        $headers = [
            'first_name' => 'Prénom',
            'last_name' => 'Nom',
            'email' => 'Email',
            'phone' => 'Téléphone',
            'position' => 'Poste',
            'service_name' => 'Service',
            'hire_date' => 'Date d\'embauche (YYYY-MM-DD)',
        ];

        $filename = 'modele_import_employes.csv';
        
        return response()->streamDownload(function () use ($headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_values($headers), ';');
            fputcsv($handle, [
                'Jean',
                'Dupont',
                'jean.dupont@exemple.com',
                '0123456789',
                'Responsable Accueil',
                'Réception',
                '2023-01-15'
            ], ';');
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Activer/Désactiver un employé
     */
    public function toggleStatus(Request $request, $employeeId)
    {
        try {
            $user = $request->user();
            $company = $user->company;

            $employee = $company->employees()->findOrFail($employeeId);
            $employee->update(['is_active' => !$employee->is_active]);

            return response()->json([
                'success' => true,
                'message' => $employee->is_active ? 'Employé activé' : 'Employé désactivé',
                'data' => $employee
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