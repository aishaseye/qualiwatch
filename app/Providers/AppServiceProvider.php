<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\Company::observe(\App\Observers\CompanyObserver::class);
        \App\Models\Feedback::observe(\App\Observers\FeedbackObserver::class);
        \App\Models\RewardClaim::observe(\App\Observers\RewardClaimObserver::class);
    }
}