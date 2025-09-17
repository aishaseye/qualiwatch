<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGamificationUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $triggerEvent;
    protected $contextData;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $triggerEvent, array $contextData = [])
    {
        $this->userId = $userId;
        $this->triggerEvent = $triggerEvent;
        $this->contextData = $contextData;
    }

    /**
     * Execute the job.
     */
    public function handle(GamificationService $gamificationService): void
    {
        try {
            $user = User::find($this->userId);
            
            if (!$user) {
                Log::warning("User not found for gamification update", [
                    'user_id' => $this->userId,
                    'trigger_event' => $this->triggerEvent,
                ]);
                return;
            }

            // Vérifier et attribuer les badges
            $badges = $gamificationService->checkAndAwardBadges($user, $this->triggerEvent);
            
            // Mettre à jour les défis en cours
            $this->updateActiveChallenges($user);
            
            // Logger les résultats
            if (count($badges) > 0) {
                Log::info("Gamification update processed with badges", [
                    'user_id' => $this->userId,
                    'trigger_event' => $this->triggerEvent,
                    'badges_awarded' => count($badges),
                    'badge_titles' => $badges->pluck('badge.title')->toArray(),
                ]);
            } else {
                Log::debug("Gamification update processed without badges", [
                    'user_id' => $this->userId,
                    'trigger_event' => $this->triggerEvent,
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Gamification update job failed", [
                'user_id' => $this->userId,
                'trigger_event' => $this->triggerEvent,
                'error' => $e->getMessage(),
                'context' => $this->contextData,
            ]);
            
            throw $e;
        }
    }

    /**
     * Mettre à jour les défis actifs de l'utilisateur
     */
    private function updateActiveChallenges(User $user): void
    {
        $activeChallenges = \App\Models\UserChallenge::where('user_id', $user->id)
                                                   ->where('is_active', true)
                                                   ->where('is_completed', false)
                                                   ->with('challenge')
                                                   ->get();

        foreach ($activeChallenges as $userChallenge) {
            $challenge = $userChallenge->challenge;
            
            // Skip si le défi est expiré
            if ($challenge->is_expired) {
                $userChallenge->deactivate();
                continue;
            }
            
            // Calculer la nouvelle valeur selon l'événement
            $newValue = $this->calculateChallengeValue($challenge, $user);
            
            if ($newValue !== null && $newValue > $userChallenge->current_value) {
                $userChallenge->updateProgress($newValue, [
                    'trigger_event' => $this->triggerEvent,
                    'context' => $this->contextData,
                    'updated_by_job' => true,
                ]);
            }
        }
    }

    /**
     * Calculer la nouvelle valeur pour un défi
     */
    private function calculateChallengeValue(\App\Models\Challenge $challenge, User $user): ?int
    {
        $objectives = $challenge->objectives;
        
        if (!$objectives || !isset($objectives['type'])) {
            return null;
        }

        $periodStart = $challenge->start_date;
        $periodEnd = min($challenge->end_date, now());

        switch ($objectives['type']) {
            case 'total_feedbacks':
                return \App\Models\Feedback::where('employee_id', $user->id)
                                         ->whereBetween('created_at', [$periodStart, $periodEnd])
                                         ->count();
                
            case 'positive_feedbacks':
                return \App\Models\Feedback::where('employee_id', $user->id)
                                         ->where('rating', '>=', 4)
                                         ->whereBetween('created_at', [$periodStart, $periodEnd])
                                         ->count();
                
            case 'satisfaction_score':
                $feedbacks = \App\Models\Feedback::where('employee_id', $user->id)
                                               ->whereNotNull('rating')
                                               ->whereBetween('created_at', [$periodStart, $periodEnd])
                                               ->get();
                
                return $feedbacks->isEmpty() ? 0 : (int)(($feedbacks->avg('rating') / 5) * 100);
                
            case 'resolution_count':
                return \App\Models\Feedback::where('employee_id', $user->id)
                                         ->whereNotNull('resolved_at')
                                         ->whereBetween('created_at', [$periodStart, $periodEnd])
                                         ->count();
                
            case 'kalipoints_earned':
                return \App\Models\Feedback::where('employee_id', $user->id)
                                         ->whereBetween('created_at', [$periodStart, $periodEnd])
                                         ->sum('kalipoints');
                
            default:
                return null;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Gamification update job permanently failed", [
            'user_id' => $this->userId,
            'trigger_event' => $this->triggerEvent,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'gamification',
            'user:' . $this->userId,
            'event:' . $this->triggerEvent,
        ];
    }
}