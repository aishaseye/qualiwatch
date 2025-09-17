<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StatisticsCalculatorService;
use Carbon\Carbon;

class CalculateStatistics extends Command
{
    protected $signature = 'qualywatch:calculate-stats 
                            {--period=daily : Type de période (daily, weekly, monthly, yearly)}
                            {--date= : Date pour laquelle calculer les stats (Y-m-d)}
                            {--company= : ID de l\'entreprise spécifique (optionnel)}';

    protected $description = 'Calculer les statistiques QualyWatch pour une période donnée';

    protected StatisticsCalculatorService $statisticsService;

    public function __construct(StatisticsCalculatorService $statisticsService)
    {
        parent::__construct();
        $this->statisticsService = $statisticsService;
    }

    public function handle()
    {
        $periodType = $this->option('period');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $companyId = $this->option('company');

        $this->info("🔄 Calcul des statistiques QualyWatch...");
        $this->info("📅 Période: {$periodType}");
        $this->info("📆 Date: {$date->format('Y-m-d')}");
        
        if ($companyId) {
            $this->info("🏢 Entreprise: {$companyId}");
        }

        $startTime = now();

        try {
            if ($companyId) {
                // Calculer pour une entreprise spécifique
                $company = \App\Models\Company::findOrFail($companyId);
                $this->calculateForCompany($company, $periodType, $date);
            } else {
                // Calculer pour toutes les entreprises
                $this->statisticsService->calculateStatisticsForPeriod($periodType, $date);
            }

            $duration = $startTime->diffInSeconds(now());
            $this->info("✅ Statistiques calculées avec succès en {$duration} secondes");

            // Afficher un résumé
            $this->displaySummary($periodType, $date);

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du calcul des statistiques:");
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function calculateForCompany($company, $periodType, $date)
    {
        $this->info("📊 Calcul pour: {$company->name}");
        
        with($this->output->createProgressBar(3), function ($bar) use ($company, $periodType, $date) {
            $bar->setFormat('verbose');
            
            $bar->setMessage('Statistiques entreprise...');
            $this->statisticsService->calculateCompanyStatistics($company, $periodType, $date);
            $bar->advance();
            
            $bar->setMessage('Statistiques services...');
            $this->statisticsService->calculateServicesStatistics($company, $periodType, $date);
            $bar->advance();
            
            $bar->setMessage('Statistiques employés...');
            $this->statisticsService->calculateEmployeesStatistics($company, $periodType, $date);
            $bar->advance();
            
            $bar->finish();
        });
        
        $this->newLine();
    }

    private function displaySummary($periodType, $date)
    {
        $this->newLine();
        $this->info("📈 Résumé des statistiques calculées:");

        // Statistiques entreprises
        $companyStats = \App\Models\CompanyStatistic::where('period_type', $periodType)
            ->where('period_date', $date->toDateString())
            ->count();

        // Statistiques services
        $serviceStats = \App\Models\ServiceStatistic::where('period_type', $periodType)
            ->where('period_date', $date->toDateString())
            ->count();

        // Statistiques employés
        $employeeStats = \App\Models\EmployeeStatistic::where('period_type', $periodType)
            ->where('period_date', $date->toDateString())
            ->count();

        $this->table(
            ['Type', 'Nombre calculé'],
            [
                ['Entreprises', $companyStats],
                ['Services', $serviceStats],
                ['Employés', $employeeStats],
            ]
        );

        // Top 3 entreprises par satisfaction
        if ($companyStats > 0) {
            $this->info("🏆 Top 3 entreprises par satisfaction:");
            
            $topCompanies = \App\Models\CompanyStatistic::where('period_type', $periodType)
                ->where('period_date', $date->toDateString())
                ->with('company')
                ->orderBy('satisfaction_score', 'desc')
                ->limit(3)
                ->get();

            foreach ($topCompanies as $index => $stat) {
                $rank = $index + 1;
                $medal = match($rank) {
                    1 => '🥇',
                    2 => '🥈',
                    3 => '🥉',
                    default => '  '
                };
                
                $this->line("  {$medal} {$stat->company->name}: {$stat->satisfaction_score}%");
            }
        }

        $this->newLine();
        $this->info("💡 Utilisez les endpoints API pour récupérer les statistiques détaillées");
        $this->info("📊 GET /api/dashboard/overview");
        $this->info("🏢 GET /api/dashboard/service/{id}");
        $this->info("👤 GET /api/dashboard/employee/{id}");
    }
}