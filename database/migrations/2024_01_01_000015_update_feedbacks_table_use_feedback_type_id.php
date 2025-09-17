<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter la colonne feedback_type_id
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->uuid('feedback_type_id')->nullable()->after('service_id');
            $table->foreign('feedback_type_id')->references('id')->on('feedback_types')->onDelete('restrict');
        });

        // Migrer les données existantes si il y en a
        $positifTypeId = DB::table('feedback_types')->where('name', 'positif')->value('id');
        $negatifTypeId = DB::table('feedback_types')->where('name', 'negatif')->value('id');
        $incidentTypeId = DB::table('feedback_types')->where('name', 'incident')->value('id');

        // Mapper les anciennes valeurs enum vers les nouveaux IDs
        if ($positifTypeId) {
            DB::table('feedbacks')->where('type', 'appreciation')->update(['feedback_type_id' => $positifTypeId]);
        }
        if ($negatifTypeId) {
            DB::table('feedbacks')->where('type', 'incident')->update(['feedback_type_id' => $negatifTypeId]);
        }
        if ($incidentTypeId) {
            // Pour l'instant, mapper 'suggestion' vers 'incident' ou créer un nouveau type si nécessaire
            DB::table('feedbacks')->where('type', 'suggestion')->update(['feedback_type_id' => $incidentTypeId]);
        }

        // Rendre la colonne obligatoire après migration des données
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->uuid('feedback_type_id')->nullable(false)->change();
        });

        // Supprimer l'ancienne colonne enum type (optionnel - à faire plus tard pour sécurité)
        // Schema::table('feedbacks', function (Blueprint $table) {
        //     $table->dropColumn('type');
        // });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropForeign(['feedback_type_id']);
            $table->dropColumn('feedback_type_id');
        });
    }
};