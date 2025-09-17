<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Ajouter la clé étrangère vers business_sectors
            $table->uuid('business_sector_id')->nullable()->after('location');
            $table->foreign('business_sector_id')->references('id')->on('business_sectors')->onDelete('set null');
            
            // Supprimer les anciens champs redondants
            $table->dropColumn(['category', 'business_sector']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Remettre les anciens champs
            $table->string('category')->nullable();
            $table->string('business_sector')->nullable();
            
            // Supprimer la clé étrangère et la colonne
            $table->dropForeign(['business_sector_id']);
            $table->dropColumn('business_sector_id');
        });
    }
};
