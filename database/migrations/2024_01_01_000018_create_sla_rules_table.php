<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('feedback_type_id');
            $table->string('name'); // "Incident Critical", "Feedback Standard"
            $table->text('description')->nullable();
            
            // Conditions d'application
            $table->json('conditions')->nullable(); // {"sentiment": "en_colere", "rating": ">=4"} - POUR NÉGATIFS: plus haute note = plus grave
            $table->integer('priority_level')->default(1); // 1=Low, 2=Normal, 3=High, 4=Critical, 5=Emergency
            
            // Temps SLA en minutes
            $table->integer('first_response_sla')->default(240); // 4h par défaut
            $table->integer('resolution_sla')->default(1440); // 24h par défaut
            
            // Escalades automatiques
            $table->integer('escalation_level_1')->default(120); // 2h → Manager
            $table->integer('escalation_level_2')->default(480); // 8h → Direction
            $table->integer('escalation_level_3')->default(1440); // 24h → PDG
            
            // Qui notifier à chaque niveau
            $table->json('level_1_recipients')->nullable(); // ["manager", "service_head"]
            $table->json('level_2_recipients')->nullable(); // ["director", "quality_manager"]
            $table->json('level_3_recipients')->nullable(); // ["ceo", "board"]
            
            // Canaux de notification
            $table->json('notification_channels')->default('["email", "app"]'); // ["email", "sms", "app", "slack"]
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('feedback_type_id')->references('id')->on('feedback_types')->onDelete('cascade');
            $table->index(['company_id', 'feedback_type_id', 'priority_level']);
        });

        // Insérer des règles SLA par défaut
        // $this->insertDefaultSlaRules(); // Commenté temporairement pour les seeders
    }

    private function insertDefaultSlaRules()
    {
        // Récupérer les types de feedback
        $positifType = DB::table('feedback_types')->where('name', 'positif')->first();
        $negatifType = DB::table('feedback_types')->where('name', 'negatif')->first();
        $incidentType = DB::table('feedback_types')->where('name', 'incident')->first();

        $defaultRules = [];

        if ($positifType) {
            $defaultRules[] = [
                'id' => \Illuminate\Support\Str::uuid(),
                'company_id' => '00000000-0000-0000-0000-000000000000', // Template pour toutes les entreprises
                'feedback_type_id' => $positifType->id,
                'name' => 'Feedback Positif Standard',
                'description' => 'SLA standard pour les feedbacks positifs',
                'conditions' => json_encode(['rating' => '>=4']),
                'priority_level' => 1,
                'first_response_sla' => 480, // 8h
                'resolution_sla' => 1440, // 24h
                'escalation_level_1' => 720, // 12h
                'escalation_level_2' => 1440, // 24h
                'escalation_level_3' => 2880, // 48h
                'level_1_recipients' => json_encode(['manager']),
                'level_2_recipients' => json_encode(['director']),
                'level_3_recipients' => json_encode(['ceo']),
                'notification_channels' => json_encode(['email', 'app']),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        if ($negatifType) {
            $defaultRules[] = [
                'id' => \Illuminate\Support\Str::uuid(),
                'company_id' => '00000000-0000-0000-0000-000000000000',
                'feedback_type_id' => $negatifType->id,
                'name' => 'Feedback Négatif Critique',
                'description' => 'SLA pour feedbacks négatifs critiques',
                'conditions' => json_encode(['rating' => '>=4', 'sentiment' => 'en_colere']),
                'priority_level' => 4,
                'first_response_sla' => 60, // 1h
                'resolution_sla' => 480, // 8h
                'escalation_level_1' => 120, // 2h
                'escalation_level_2' => 240, // 4h
                'escalation_level_3' => 480, // 8h
                'level_1_recipients' => json_encode(['manager', 'service_head']),
                'level_2_recipients' => json_encode(['director', 'quality_manager']),
                'level_3_recipients' => json_encode(['ceo']),
                'notification_channels' => json_encode(['email', 'sms', 'app']),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        if ($incidentType) {
            $defaultRules[] = [
                'id' => \Illuminate\Support\Str::uuid(),
                'company_id' => '00000000-0000-0000-0000-000000000000',
                'feedback_type_id' => $incidentType->id,
                'name' => 'Incident Majeur',
                'description' => 'SLA pour incidents majeurs nécessitant intervention immédiate',
                'conditions' => json_encode(['rating' => '<=1', 'sentiment' => 'en_colere']),
                'priority_level' => 5,
                'first_response_sla' => 30, // 30min
                'resolution_sla' => 240, // 4h
                'escalation_level_1' => 60, // 1h
                'escalation_level_2' => 120, // 2h
                'escalation_level_3' => 240, // 4h
                'level_1_recipients' => json_encode(['manager', 'service_head', 'quality_manager']),
                'level_2_recipients' => json_encode(['director', 'ceo']),
                'level_3_recipients' => json_encode(['ceo', 'board']),
                'notification_channels' => json_encode(['email', 'sms', 'app', 'phone']),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        if (!empty($defaultRules)) {
            DB::table('sla_rules')->insert($defaultRules);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_rules');
    }
};