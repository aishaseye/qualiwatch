<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter 'validation_request' à l'ENUM type de la table notification_templates
        DB::statement("ALTER TABLE notification_templates MODIFY COLUMN type ENUM('feedback', 'reward', 'escalation', 'system', 'promotion', 'milestone', 'validation_request')");
    }

    public function down(): void
    {
        // Restaurer l'ENUM original sans 'validation_request'
        DB::statement("ALTER TABLE notification_templates MODIFY COLUMN type ENUM('feedback', 'reward', 'escalation', 'system', 'promotion', 'milestone')");
    }
};