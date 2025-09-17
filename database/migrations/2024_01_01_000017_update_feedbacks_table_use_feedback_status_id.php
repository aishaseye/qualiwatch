<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter la colonne feedback_status_id
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->uuid('feedback_status_id')->nullable()->after('feedback_type_id');
            $table->foreign('feedback_status_id')->references('id')->on('feedback_statuses')->onDelete('restrict');
        });

        // Migrer les données existantes si il y en a
        $statusMappings = [
            'new' => DB::table('feedback_statuses')->where('name', 'new')->value('id'),
            'in_progress' => DB::table('feedback_statuses')->where('name', 'in_progress')->value('id'),
            'treated' => DB::table('feedback_statuses')->where('name', 'treated')->value('id'),
            'resolved' => DB::table('feedback_statuses')->where('name', 'resolved')->value('id'),
            'partially_resolved' => DB::table('feedback_statuses')->where('name', 'partially_resolved')->value('id'),
            'not_resolved' => DB::table('feedback_statuses')->where('name', 'not_resolved')->value('id'),
            'implemented' => DB::table('feedback_statuses')->where('name', 'implemented')->value('id'),
            'partially_implemented' => DB::table('feedback_statuses')->where('name', 'partially_implemented')->value('id'),
            'rejected' => DB::table('feedback_statuses')->where('name', 'rejected')->value('id'),
            'archived' => DB::table('feedback_statuses')->where('name', 'archived')->value('id'),
        ];

        // Mapper les anciens status enum vers les nouveaux IDs
        foreach ($statusMappings as $oldStatus => $newId) {
            if ($newId) {
                DB::table('feedbacks')
                    ->where('status', $oldStatus)
                    ->update(['feedback_status_id' => $newId]);
            }
        }

        // Rendre la colonne obligatoire après migration des données
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->uuid('feedback_status_id')->nullable(false)->change();
        });

        // Supprimer l'ancienne colonne enum status (optionnel - à faire plus tard pour sécurité)
        // Schema::table('feedbacks', function (Blueprint $table) {
        //     $table->dropColumn('status');
        // });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropForeign(['feedback_status_id']);
            $table->dropColumn('feedback_status_id');
        });
    }
};