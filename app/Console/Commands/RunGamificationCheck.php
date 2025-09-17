<?php

namespace App\Console\Commands;

use App\Services\GamificationService;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunGamificationCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gamification:check 
                          {--company= : Run for specific company ID}
                          {--force-monthly : Force monthly leaderboard calculation}
                          {--force-weekly : Force weekly leaderboard calculation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run daily gamification checks for badges and leaderboards';

    protected $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        parent::__construct();
        $this->gamificationService = $gamificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎮 Starting gamification checks...');
        $startTime = microtime(true);

        try {
            $companyId = $this->option('company');
            
            if ($companyId) {
                $company = Company::find($companyId);
                if (!$company) {
                    $this->error("Company with ID {$companyId} not found.");
                    return 1;
                }
                $companies = collect([$company]);
                $this->info("Running for company: {$company->name}");
            } else {
                $companies = Company::active()->get();
                $this->info("Running for all " . $companies->count() . " active companies");
            }

            $totalResults = [
                'companies_processed' => 0,
                'badges_awarded' => 0,
                'leaderboards_calculated' => 0,
                'errors' => 0,
            ];

            $progressBar = $this->output->createProgressBar($companies->count());
            $progressBar->start();

            foreach ($companies as $company) {
                try {
                    $results = $this->processCompany($company);
                    
                    $totalResults['companies_processed']++;
                    $totalResults['badges_awarded'] += $results['badges_awarded'];
                    $totalResults['leaderboards_calculated'] += $results['leaderboards_calculated'] ?? 0;

                    $progressBar->advance();
                    
                } catch (\Exception $e) {
                    $totalResults['errors']++;
                    Log::error("Gamification check failed for company {$company->id}", [
                        'company_id' => $company->id,
                        'company_name' => $company->name,
                        'error' => $e->getMessage(),
                    ]);
                    
                    if ($companyId) {
                        // Si c'est pour une seule entreprise, afficher l'erreur
                        $this->error("Error processing company {$company->name}: " . $e->getMessage());
                    }
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            // Afficher les résultats
            $this->displayResults($totalResults, microtime(true) - $startTime);

            return $totalResults['errors'] > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("Critical error in gamification check: " . $e->getMessage());
            Log::critical("Gamification check command failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Traiter une entreprise
     */
    private function processCompany(Company $company): array
    {
        $results = [
            'badges_awarded' => 0,
            'leaderboards_calculated' => 0,
        ];

        // 1. Vérification des badges quotidienne
        $badgeResults = $this->gamificationService->runDailyGamificationCheck($company->id);
        $results['badges_awarded'] = $badgeResults['badges_awarded'] ?? 0;

        // 2. Calcul des classements si nécessaire
        $leaderboardResults = $this->calculateLeaderboards($company);
        $results['leaderboards_calculated'] = count($leaderboardResults);

        // 3. Nettoyer les défis expirés
        $this->cleanupExpiredChallenges($company);

        return $results;
    }

    /**
     * Calculer les classements
     */
    private function calculateLeaderboards(Company $company): array
    {
        $calculated = [];

        // Classements hebdomadaires le dimanche
        if (now()->isSunday() || $this->option('force-weekly')) {
            $this->gamificationService->calculateLeaderboards($company->id, 'weekly');
            $calculated[] = 'weekly';
            
            if ($this->option('company')) {
                $this->info("  ✅ Weekly leaderboards calculated");
            }
        }

        // Classements mensuels le dernier jour du mois
        if (now()->isLastOfMonth() || $this->option('force-monthly')) {
            $this->gamificationService->calculateLeaderboards($company->id, 'monthly');
            $calculated[] = 'monthly';
            
            if ($this->option('company')) {
                $this->info("  ✅ Monthly leaderboards calculated");
            }
        }

        return $calculated;
    }

    /**
     * Nettoyer les défis expirés
     */
    private function cleanupExpiredChallenges(Company $company): void
    {
        $expiredChallenges = \App\Models\Challenge::where('company_id', $company->id)
                                                 ->where('status', 'active')
                                                 ->where('end_date', '<', now())
                                                 ->get();

        foreach ($expiredChallenges as $challenge) {
            $challenge->complete();
            
            if ($this->option('company')) {
                $this->info("  🏁 Challenge '{$challenge->title}' marked as completed");
            }
        }
    }

    /**
     * Afficher les résultats
     */
    private function displayResults(array $results, float $executionTime): void
    {
        $this->info('🎯 Gamification Check Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Companies Processed', $results['companies_processed']],
                ['Badges Awarded', $results['badges_awarded']],
                ['Leaderboards Calculated', $results['leaderboards_calculated']],
                ['Errors', $results['errors']],
                ['Execution Time', round($executionTime, 2) . 's'],
            ]
        );

        if ($results['errors'] > 0) {
            $this->warn("⚠️  Some companies had errors. Check the logs for details.");
        } else {
            $this->info("✅ All companies processed successfully!");
        }

        // Afficher quelques statistiques supplémentaires
        if ($results['badges_awarded'] > 0) {
            $this->info("🏅 {$results['badges_awarded']} badges have been awarded today!");
        }

        if ($results['leaderboards_calculated'] > 0) {
            $this->info("📊 Leaderboards have been updated and published!");
        }
    }
}