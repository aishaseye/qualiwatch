<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaderboardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $companyId;
    public $periodType;
    public $periodDate;
    public $metrics;

    public function __construct($companyId, $periodType, $periodDate, $metrics = null)
    {
        $this->companyId = $companyId;
        $this->periodType = $periodType;
        $this->periodDate = $periodDate;
        $this->metrics = $metrics ?? $this->getLeaderboardMetrics();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->companyId}.leaderboard"),
            new Channel("public.rankings.{$this->companyId}")
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'leaderboard.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->companyId,
            'period_type' => $this->periodType,
            'period_date' => $this->periodDate,
            'period_label' => $this->getPeriodLabel(),
            'metrics' => $this->metrics,
            'top_performers' => $this->getTopPerformers(),
            'updated_at' => now()->toISOString(),
            'message' => "Les classements {$this->getPeriodLabel()} ont été mis à jour !",
        ];
    }

    /**
     * Get leaderboard metrics summary
     */
    private function getLeaderboardMetrics(): array
    {
        $leaderboards = \App\Models\Leaderboard::byCompany($this->companyId)
                                              ->byPeriod($this->periodType, $this->periodDate)
                                              ->published()
                                              ->get();

        $metricsSummary = [];
        
        foreach ($leaderboards->groupBy('metric_type') as $metricType => $entries) {
            $metricsSummary[$metricType] = [
                'participants' => $entries->count(),
                'winner' => $entries->where('rank_overall', 1)->first()?->user?->only(['id', 'name']),
                'avg_score' => round($entries->avg('score'), 2),
                'top_3' => $entries->where('podium_position', '<=', 3)
                                  ->sortBy('podium_position')
                                  ->map(function ($entry) {
                                      return [
                                          'rank' => $entry->podium_position,
                                          'user' => $entry->user->only(['id', 'name']),
                                          'score' => $entry->score_formatted,
                                      ];
                                  })->values()->toArray(),
            ];
        }

        return $metricsSummary;
    }

    /**
     * Get top performers across all metrics
     */
    private function getTopPerformers(): array
    {
        return \App\Models\Leaderboard::byCompany($this->companyId)
                                     ->byPeriod($this->periodType, $this->periodDate)
                                     ->where('podium_position', '<=', 3)
                                     ->with(['user'])
                                     ->orderBy('podium_position')
                                     ->orderBy('score', 'desc')
                                     ->take(5)
                                     ->get()
                                     ->map(function ($entry) {
                                         return [
                                             'user' => [
                                                 'id' => $entry->user->id,
                                                 'name' => $entry->user->name,
                                                 'avatar' => $entry->user->avatar_url,
                                             ],
                                             'metric' => $entry->metric_label,
                                             'rank' => $entry->rank_display,
                                             'score' => $entry->score_formatted,
                                             'points' => $entry->points_earned,
                                         ];
                                     })->toArray();
    }

    /**
     * Get period label
     */
    private function getPeriodLabel(): string
    {
        return match($this->periodType) {
            'daily' => 'quotidiens',
            'weekly' => 'hebdomadaires',
            'monthly' => 'mensuels',
            'yearly' => 'annuels',
            default => 'périodiques'
        };
    }

    /**
     * Determine if this event should queue
     */
    public function shouldQueue(): bool
    {
        return true;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'gamification',
            'leaderboard-updated',
            "company:{$this->companyId}",
            "period:{$this->periodType}"
        ];
    }
}