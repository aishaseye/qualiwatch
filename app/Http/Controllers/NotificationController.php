<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Liste des notifications
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir toutes les notifications avec company_id
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if ($companyId) {
                    $query = Notification::where('company_id', $companyId);
                } else {
                    $query = Notification::query();
                }
            } else {
                // Users normaux voient leurs propres notifications + celles de leur entreprise
                $companyId = $user->company->id;
                $query = Notification::where('company_id', $companyId);
                
                // Filtrer par destinataire si spécifié
                if ($request->get('recipient_only')) {
                    $query->where('recipient_id', $user->id)
                          ->where('recipient_type', 'App\Models\User');
                }
            }

            // Filtres
            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->type) {
                $query->where('type', $request->type);
            }

            if ($request->channel) {
                $query->where('channel', $request->channel);
            }

            if ($request->unread_only) {
                $query->unread();
            }

            $notifications = $query->with(['recipient', 'company'])
                                   ->orderBy('created_at', 'desc')
                                   ->paginate(20);

            // Ajouter les informations calculées
            $notifications->getCollection()->transform(function ($notification) {
                $notification->append(['is_read', 'is_sent', 'is_failed']);
                return $notification;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications->items(),
                    'pagination' => [
                        'total' => $notifications->total(),
                        'per_page' => $notifications->perPage(),
                        'current_page' => $notifications->currentPage(),
                        'last_page' => $notifications->lastPage(),
                    ],
                    'stats' => $this->getNotificationStats($companyId ?? null, $user)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une notification spécifique
     */
    public function show(Request $request, $notificationId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir n'importe quelle notification
            if ($user->role === 'super_admin') {
                $notification = Notification::with(['recipient', 'company'])->findOrFail($notificationId);
            } else {
                $notification = Notification::where('company_id', $user->company->id)
                                           ->with(['recipient', 'company'])
                                           ->findOrFail($notificationId);
            }

            $notification->append(['is_read', 'is_sent', 'is_failed']);

            return response()->json([
                'success' => true,
                'data' => $notification
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, $notificationId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut marquer n'importe quelle notification
            if ($user->role === 'super_admin') {
                $notification = Notification::findOrFail($notificationId);
            } else {
                // Utilisateur normal peut seulement marquer ses propres notifications
                $notification = Notification::where('recipient_id', $user->id)
                                           ->where('recipient_type', 'App\Models\User')
                                           ->findOrFail($notificationId);
            }

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue',
                'data' => $notification->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = $request->user();
            
            $query = Notification::where('recipient_id', $user->id)
                                ->where('recipient_type', 'App\Models\User')
                                ->unread();

            $count = $query->count();
            $query->update([
                'read_at' => now(),
                'status' => 'read'
            ]);

            return response()->json([
                'success' => true,
                'message' => "Toutes les notifications ({$count}) ont été marquées comme lues"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer une notification manuelle
     */
    public function send(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:feedback,reward,escalation,system,promotion,milestone',
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:2000',
                'channel' => 'required|in:email,sms,push,in_app,webhook',
                'recipient_id' => 'nullable|uuid',
                'recipient_type' => 'nullable|in:App\Models\User,App\Models\Client',
                'scheduled_at' => 'nullable|date|after:now',
                'data' => 'nullable|array'
            ]);

            $user = $request->user();
            
            // Super admin peut envoyer pour n'importe quelle entreprise
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

            // Créer la notification
            $notification = Notification::create([
                'company_id' => $companyId,
                'recipient_id' => $request->recipient_id,
                'recipient_type' => $request->recipient_type,
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'data' => $request->data ?? [],
                'channel' => $request->channel,
                'scheduled_at' => $request->scheduled_at
            ]);

            // Si pas programmée, envoyer immédiatement
            if (!$request->scheduled_at) {
                $template = NotificationTemplate::findTemplate($companyId, $request->type, $request->channel);
                if ($template) {
                    $rendered = [
                        'title' => $request->title,
                        'message' => $request->message,
                        'subject' => $request->title
                    ];
                    
                    // Utiliser la méthode privée via reflection pour les tests
                    $reflection = new \ReflectionClass($this->notificationService);
                    $method = $reflection->getMethod('processNotification');
                    $method->setAccessible(true);
                    $method->invoke($this->notificationService, $notification, $rendered);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification créée avec succès',
                'data' => $notification->fresh()
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réessayer une notification échouée
     */
    public function retry(Request $request, $notificationId)
    {
        try {
            $user = $request->user();
            
            // Super admin peut réessayer n'importe quelle notification
            if ($user->role === 'super_admin') {
                $notification = Notification::findOrFail($notificationId);
            } else {
                $notification = Notification::where('company_id', $user->company->id)
                                           ->findOrFail($notificationId);
            }

            if (!$notification->can_retry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette notification ne peut pas être réessayée'
                ], 400);
            }

            $notification->retry();

            // Réessayer l'envoi
            $template = NotificationTemplate::findTemplate(
                $notification->company_id,
                $notification->type,
                $notification->channel
            );

            if ($template) {
                $rendered = $template->render($notification->data ?? []);
                
                // Utiliser reflection pour accéder à la méthode privée
                $reflection = new \ReflectionClass($this->notificationService);
                $method = $reflection->getMethod('processNotification');
                $method->setAccessible(true);
                $method->invoke($this->notificationService, $notification, $rendered);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification remise en queue',
                'data' => $notification->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renvoi de la notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Request $request, $notificationId)
    {
        try {
            $user = $request->user();
            
            // Seuls les super admins peuvent supprimer des notifications
            if ($user->role !== 'super_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Action non autorisée'
                ], 403);
            }

            $notification = Notification::findOrFail($notificationId);
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des templates de notifications
     */
    public function templates(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin peut voir tous les templates
            if ($user->role === 'super_admin') {
                $companyId = $request->get('company_id');
                if ($companyId) {
                    $query = NotificationTemplate::where('company_id', $companyId)
                                                 ->orWhereNull('company_id');
                } else {
                    $query = NotificationTemplate::query();
                }
            } else {
                $companyId = $user->company->id;
                $query = NotificationTemplate::where('company_id', $companyId)
                                             ->orWhereNull('company_id');
            }

            // Filtres
            if ($request->type) {
                $query->where('type', $request->type);
            }

            if ($request->channel) {
                $query->where('channel', $request->channel);
            }

            if ($request->active_only) {
                $query->active();
            }

            $templates = $query->orderBy('type')
                              ->orderBy('channel')
                              ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'templates' => $templates->items(),
                    'pagination' => [
                        'total' => $templates->total(),
                        'per_page' => $templates->perPage(),
                        'current_page' => $templates->currentPage(),
                        'last_page' => $templates->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getNotificationStats($companyId, $user)
    {
        $baseQuery = $companyId ? 
            Notification::where('company_id', $companyId) : 
            Notification::query();

        // Si pas super admin, filtrer par utilisateur pour les stats personnelles
        if ($user->role !== 'super_admin') {
            $personalQuery = Notification::where('recipient_id', $user->id)
                                        ->where('recipient_type', 'App\Models\User');
            
            return [
                'total' => $baseQuery->count(),
                'unread' => $personalQuery->unread()->count(),
                'failed' => $baseQuery->failed()->count(),
                'by_type' => $baseQuery->select('type', DB::raw('count(*) as count'))
                                     ->groupBy('type')
                                     ->get()
                                     ->keyBy('type'),
                'by_channel' => $baseQuery->select('channel', DB::raw('count(*) as count'))
                                         ->groupBy('channel')
                                         ->get()
                                         ->keyBy('channel'),
            ];
        }

        return [
            'total' => $baseQuery->count(),
            'pending' => $baseQuery->pending()->count(),
            'sent' => $baseQuery->sent()->count(),
            'failed' => $baseQuery->failed()->count(),
            'by_type' => $baseQuery->select('type', DB::raw('count(*) as count'))
                                 ->groupBy('type')
                                 ->get()
                                 ->keyBy('type'),
            'by_channel' => $baseQuery->select('channel', DB::raw('count(*) as count'))
                                     ->groupBy('channel')
                                     ->get()
                                     ->keyBy('channel'),
        ];
    }
}