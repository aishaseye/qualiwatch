<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Gamification Events
        \App\Events\BadgeEarned::class => [
            \App\Listeners\SendBadgeEarnedNotification::class,
        ],
        
        \App\Events\LeaderboardUpdated::class => [
            \App\Listeners\SendLeaderboardNotification::class,
        ],
        
        \App\Events\LeaderboardPublished::class => [
            \App\Listeners\SendLeaderboardNotification::class . '@handlePublished',
        ],
        
        \App\Events\ChallengeCompleted::class => [
            \App\Listeners\SendChallengeNotification::class . '@handleCompleted',
        ],
        
        \App\Events\ChallengeProgressUpdated::class => [
            \App\Listeners\SendChallengeNotification::class . '@handleProgress',
        ],
        
        // Feedback Events
        \App\Events\FeedbackCreated::class => [
            \App\Listeners\SendFeedbackNotification::class,
            \App\Listeners\CheckFeedbackSLA::class,
            \App\Listeners\UpdateGamificationProgress::class,
        ],
        
        \App\Events\FeedbackStatusChanged::class => [
            \App\Listeners\SendFeedbackStatusNotification::class,
            \App\Listeners\UpdateGamificationProgress::class,
        ],
        
        \App\Events\SLAViolated::class => [
            \App\Listeners\SendSLAViolationNotification::class,
            \App\Listeners\EscalateFeedback::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}