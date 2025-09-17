<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CalculateStatistics;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Calcul automatique des statistiques quotidiennes à minuit
        $schedule->command('qualywatch:calculate-stats --period=daily')->dailyAt('00:30');
        
        // Calcul automatique des statistiques hebdomadaires le lundi à 1h
        $schedule->command('qualywatch:calculate-stats --period=weekly')->weeklyOn(1, '01:00');
        
        // Calcul automatique des statistiques mensuelles le 1er de chaque mois à 2h
        $schedule->command('qualywatch:calculate-stats --period=monthly')->monthlyOn(1, '02:00');
        
        // Calcul automatique des statistiques annuelles le 1er janvier à 3h
        $schedule->command('qualywatch:calculate-stats --period=yearly')->yearlyOn(1, 1, '03:00');

        // Vérification gamification quotidienne à 2h du matin
        $schedule->command('gamification:check')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Calcul automatique des classements hebdomadaires le dimanche à 1h
        $schedule->command('gamification:check --force-weekly')
                 ->weekly()
                 ->sundays()
                 ->at('01:00');

        // Calcul automatique des classements mensuels le 1er de chaque mois à 1h
        $schedule->command('gamification:check --force-monthly')
                 ->monthlyOn(1, '01:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}