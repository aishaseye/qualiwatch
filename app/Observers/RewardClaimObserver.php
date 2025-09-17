<?php

namespace App\Observers;

use App\Models\RewardClaim;
use App\Services\NotificationService;

class RewardClaimObserver
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function created(RewardClaim $rewardClaim)
    {
        $this->notificationService->sendRewardClaimNotification($rewardClaim);
    }

    public function updated(RewardClaim $rewardClaim)
    {
        // Si le statut change, envoyer une notification
        if ($rewardClaim->isDirty('status')) {
            $this->handleStatusChange($rewardClaim);
        }
    }

    private function handleStatusChange(RewardClaim $rewardClaim)
    {
        $oldStatus = $rewardClaim->getOriginal('status');
        $newStatus = $rewardClaim->status;

        // Envoyer notification sur changement de statut
        if (in_array($newStatus, ['approved', 'delivered', 'cancelled'])) {
            $this->notificationService->sendRewardStatusNotification($rewardClaim, $newStatus);
        }
    }
}