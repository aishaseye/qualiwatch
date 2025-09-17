<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeedbackAlert;
use App\Services\AlertDetectionService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeedbackAlertController extends Controller
{
    protected AlertDetectionService $alertService;
    protected NotificationService $notificationService;

    public function __construct(AlertDetectionService $alertService, NotificationService $notificationService)
    {
        $this->alertService = $alertService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->role === 'super_admin' ? $request->get('company_id') : $user->company_id;

        if (!$companyId && $user->role !== 'super_admin') {
            return response()->json(['error' => 'Company ID required'], 400);
        }

        $query = FeedbackAlert::with(['feedback', 'company', 'acknowledgedBy'])
            ->when($companyId, fn($q) => $q->byCompany($companyId))
            ->when($request->severity, fn($q) => $q->bySeverity($request->severity))
            ->when($request->status, fn($q) => $q->byStatus($request->status))
            ->when($request->unacknowledged === 'true', fn($q) => $q->unacknowledged())
            ->when($request->critical === 'true', fn($q) => $q->critical())
            ->when($request->escalated === 'true', fn($q) => $q->escalated())
            ->orderBy('created_at', 'desc');

        $alerts = $request->has('per_page') 
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $alert = FeedbackAlert::with(['feedback', 'company', 'acknowledgedBy'])
            ->when($user->role !== 'super_admin', fn($q) => $q->byCompany($user->company_id))
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $alert,
        ]);
    }

    public function acknowledge(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $alert = FeedbackAlert::when($user->role !== 'super_admin', fn($q) => $q->byCompany($user->company_id))
            ->findOrFail($id);

        if ($alert->status !== 'new') {
            return response()->json(['error' => 'Alert already acknowledged'], 400);
        }

        $alert->acknowledge($user->id);

        $this->notificationService->sendFeedbackAlertUpdate($alert, 'acknowledged');

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged successfully',
            'data' => $alert->fresh(['acknowledgedBy']),
        ]);
    }

    public function startProgress(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $alert = FeedbackAlert::when($user->role !== 'super_admin', fn($q) => $q->byCompany($user->company_id))
            ->findOrFail($id);

        if (!in_array($alert->status, ['acknowledged'])) {
            return response()->json(['error' => 'Alert must be acknowledged first'], 400);
        }

        $alert->startProgress();

        $this->notificationService->sendFeedbackAlertUpdate($alert, 'in_progress');

        return response()->json([
            'success' => true,
            'message' => 'Alert marked as in progress',
            'data' => $alert->fresh(),
        ]);
    }

    public function resolve(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'resolution_notes' => 'sometimes|string|max:1000',
        ]);

        $alert = FeedbackAlert::when($user->role !== 'super_admin', fn($q) => $q->byCompany($user->company_id))
            ->findOrFail($id);

        if (in_array($alert->status, ['resolved', 'dismissed'])) {
            return response()->json(['error' => 'Alert already resolved or dismissed'], 400);
        }

        $alert->resolve($request->resolution_notes);

        $this->notificationService->sendFeedbackAlertUpdate($alert, 'resolved');

        return response()->json([
            'success' => true,
            'message' => 'Alert resolved successfully',
            'data' => $alert->fresh(),
        ]);
    }

    public function dismiss(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'resolution_notes' => 'sometimes|string|max:1000',
        ]);

        $alert = FeedbackAlert::when($user->role !== 'super_admin', fn($q) => $q->byCompany($user->company_id))
            ->findOrFail($id);

        if (in_array($alert->status, ['resolved', 'dismissed'])) {
            return response()->json(['error' => 'Alert already resolved or dismissed'], 400);
        }

        $alert->dismiss($request->resolution_notes);

        $this->notificationService->sendFeedbackAlertUpdate($alert, 'dismissed');

        return response()->json([
            'success' => true,
            'message' => 'Alert dismissed successfully',
            'data' => $alert->fresh(),
        ]);
    }

    public function escalate(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $alert = FeedbackAlert::when($user->role !== 'super_admin', fn($q) => $q->byCompany($user->company_id))
            ->findOrFail($id);

        if ($alert->is_escalated) {
            return response()->json(['error' => 'Alert already escalated'], 400);
        }

        if (in_array($alert->status, ['resolved', 'dismissed'])) {
            return response()->json(['error' => 'Cannot escalate resolved or dismissed alert'], 400);
        }

        $alert->escalate();

        $this->notificationService->sendFeedbackAlertUpdate($alert, 'escalated');

        return response()->json([
            'success' => true,
            'message' => 'Alert escalated successfully',
            'data' => $alert->fresh(),
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'required|uuid',
            'action' => 'required|in:acknowledge,start_progress,resolve,dismiss,escalate',
            'resolution_notes' => 'sometimes|string|max:1000',
        ]);

        $query = FeedbackAlert::whereIn('id', $request->alert_ids)
            ->when($user->role !== 'super_admin', fn($q) => $q->byCompany($user->company_id));

        $alerts = $query->get();

        if ($alerts->count() !== count($request->alert_ids)) {
            return response()->json(['error' => 'Some alerts not found'], 404);
        }

        $updated = 0;
        foreach ($alerts as $alert) {
            try {
                switch ($request->action) {
                    case 'acknowledge':
                        if ($alert->status === 'new') {
                            $alert->acknowledge($user->id);
                            $updated++;
                        }
                        break;
                    case 'start_progress':
                        if ($alert->status === 'acknowledged') {
                            $alert->startProgress();
                            $updated++;
                        }
                        break;
                    case 'resolve':
                        if (!in_array($alert->status, ['resolved', 'dismissed'])) {
                            $alert->resolve($request->resolution_notes);
                            $updated++;
                        }
                        break;
                    case 'dismiss':
                        if (!in_array($alert->status, ['resolved', 'dismissed'])) {
                            $alert->dismiss($request->resolution_notes);
                            $updated++;
                        }
                        break;
                    case 'escalate':
                        if (!$alert->is_escalated && !in_array($alert->status, ['resolved', 'dismissed'])) {
                            $alert->escalate();
                            $updated++;
                        }
                        break;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$updated} alerts updated successfully",
            'updated_count' => $updated,
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->role === 'super_admin' ? $request->get('company_id') : $user->company_id;

        if (!$companyId && $user->role !== 'super_admin') {
            return response()->json(['error' => 'Company ID required'], 400);
        }

        $stats = $this->alertService->getCompanyAlertStats($companyId);

        $recentAlerts = FeedbackAlert::byCompany($companyId)
            ->with(['feedback'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_alerts' => $recentAlerts,
            ],
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->role === 'super_admin' ? $request->get('company_id') : $user->company_id;

        if (!$companyId && $user->role !== 'super_admin') {
            return response()->json(['error' => 'Company ID required'], 400);
        }

        $query = FeedbackAlert::byCompany($companyId);

        $summary = [
            'critical_unresolved' => $query->clone()->critical()->whereNotIn('status', ['resolved', 'dismissed'])->count(),
            'new_alerts' => $query->clone()->where('status', 'new')->count(),
            'escalated_alerts' => $query->clone()->escalated()->whereNotIn('status', ['resolved', 'dismissed'])->count(),
            'total_today' => $query->clone()->whereDate('created_at', today())->count(),
        ];

        $recentCritical = $query->clone()
            ->critical()
            ->whereNotIn('status', ['resolved', 'dismissed'])
            ->with(['feedback', 'acknowledgedBy'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $alertTrends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $alertTrends[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $query->clone()->whereDate('created_at', $date)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'recent_critical' => $recentCritical,
                'alert_trends' => $alertTrends,
            ],
        ]);
    }
}