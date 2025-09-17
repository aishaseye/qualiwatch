<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserOtp;
use App\Models\User;
use Carbon\Carbon;

class CleanExpiredOtps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:clean {--hours=24 : Number of hours after which expired OTPs are deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired OTP records and unverified users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $cutoffTime = Carbon::now()->subHours($hours);

        // 1. Supprimer les OTP expirés depuis plus de X heures
        $expiredOtps = UserOtp::where('expires_at', '<', $cutoffTime)
                              ->where('is_used', false)
                              ->get();

        $this->info("Trouvé " . $expiredOtps->count() . " OTP expirés à nettoyer");

        foreach ($expiredOtps as $otp) {
            // Si l'OTP avait un user_id, supprimer aussi l'utilisateur non vérifié
            if ($otp->user_id) {
                $user = User::find($otp->user_id);
                if ($user && is_null($user->email_verified_at)) {
                    $this->line("Suppression utilisateur non vérifié: {$user->email}");
                    $user->delete();
                }
            }
            
            $this->line("Suppression OTP expiré: {$otp->email}");
            $otp->delete();
        }

        // 2. Nettoyer les utilisateurs orphelins (créés mais sans company depuis plus de X heures)
        $orphanUsers = User::whereNull('email_verified_at')
                          ->where('created_at', '<', $cutoffTime)
                          ->get();

        $this->info("Trouvé " . $orphanUsers->count() . " utilisateurs orphelins à nettoyer");

        foreach ($orphanUsers as $user) {
            $this->line("Suppression utilisateur orphelin: {$user->email}");
            $user->delete();
        }

        $this->info("Nettoyage terminé !");
        
        return Command::SUCCESS;
    }
}