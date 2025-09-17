<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RealTimeNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(RealTimeNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Obtenir les métriques en temps réel
     */
    public function getRealTimeMetrics(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $this->notificationService->updateDashboardMetrics($companyId, 'api_request');

        return response()->json([
            'message' => 'Métriques temps réel demandées',
            'company_id' => $companyId,
            'timestamp' => now()
        ]);
    }

    /**
     * Forcer la diffusion des statistiques
     */
    public function broadcastStats(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$user->can('view_dashboard')) {
            return response()->json(['error' => 'Permissions insuffisantes'], 403);
        }

        $this->notificationService->broadcastLiveStats($companyId);

        return response()->json([
            'message' => 'Statistiques diffusées en temps réel',
            'company_id' => $companyId,
            'timestamp' => now()
        ]);
    }

    /**
     * Tester la connexion WebSocket
     */
    public function testWebSocket(Request $request)
    {
        $isHealthy = $this->notificationService->checkWebSocketHealth();

        return response()->json([
            'websocket_status' => $isHealthy ? 'healthy' : 'error',
            'timestamp' => now()
        ], $isHealthy ? 200 : 500);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return response()->json(['error' => 'Notification non trouvée'], 404);
        }

        $notification->markAsRead();

        // Diffuser la mise à jour
        broadcast(new \Illuminate\Broadcasting\InteractsWithBroadcasting)->toOthers()
            ->toPrivate('user.' . $user->id)
            ->event('notification.marked_read')
            ->with([
                'notification_id' => $notificationId,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'Notification marquée comme lue',
            'notification_id' => $notificationId
        ]);
    }

    /**
     * Obtenir les notifications non lues
     */
    public function getUnreadNotifications(Request $request)
    {
        $user = Auth::user();
        
        $notifications = $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at,
                    'is_urgent' => $notification->data['urgency'] ?? false,
                ];
            }),
            'total_unread' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Obtenir l'historique des notifications
     */
    public function getNotificationHistory(Request $request)
    {
        $user = Auth::user();
        
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]
        ]);
    }

    /**
     * Configurer les préférences de notification
     */
    public function updateNotificationPreferences(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'escalation_notifications' => 'boolean',
            'feedback_notifications' => 'boolean',
        ]);

        $preferences = $request->only([
            'email_notifications',
            'push_notifications', 
            'sms_notifications',
            'escalation_notifications',
            'feedback_notifications'
        ]);

        // Sauvegarder les préférences (il faudrait une table user_notification_preferences)
        $user->update([
            'notification_preferences' => array_merge(
                $user->notification_preferences ?? [],
                $preferences
            )
        ]);

        return response()->json([
            'message' => 'Préférences de notification mises à jour',
            'preferences' => $user->notification_preferences
        ]);
    }

    /**
     * S'abonner aux notifications push
     */
    public function subscribeToPushNotifications(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $user = Auth::user();

        // Sauvegarder la subscription push
        $user->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'public_key' => $request->keys['p256dh'],
                'auth_token' => $request->keys['auth'],
                'content_encoding' => 'aesgcm',
            ]
        );

        return response()->json([
            'message' => 'Abonnement push notifications enregistré'
        ]);
    }
}