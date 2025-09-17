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
        Schema::table('user_otps', function (Blueprint $table) {
            // Rendre user_id nullable car on ne crée plus l'user avant vérification
            $table->uuid('user_id')->nullable()->change();
            
            // Ajouter les champs temporaires pour stocker les données d'inscription
            $table->string('temp_first_name')->nullable();
            $table->string('temp_last_name')->nullable();
            $table->string('temp_phone')->nullable();
            $table->string('temp_password_hash')->nullable();
            $table->string('temp_role')->default('manager');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_otps', function (Blueprint $table) {
            $table->dropColumn([
                'temp_first_name',
                'temp_last_name', 
                'temp_phone',
                'temp_password_hash',
                'temp_role'
            ]);
            
            $table->uuid('user_id')->nullable(false)->change();
        });
    }
};
