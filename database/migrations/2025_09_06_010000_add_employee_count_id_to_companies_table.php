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
            // Vérifier si la colonne n'existe pas déjà
            if (!Schema::hasColumn('companies', 'employee_count_id')) {
                // Ajouter la clé étrangère vers employee_counts
                $table->uuid('employee_count_id')->nullable()->after('business_sector_id');
                $table->foreign('employee_count_id')->references('id')->on('employee_counts')->onDelete('set null');
            }
            
            // Supprimer l'ancien champ employees_count si il existe
            if (Schema::hasColumn('companies', 'employees_count')) {
                $table->dropColumn('employees_count');
            }
            if (Schema::hasColumn('companies', 'employee_count')) {
                $table->dropColumn('employee_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'employee_count_id')) {
                $table->dropForeign(['employee_count_id']);
                $table->dropColumn('employee_count_id');
            }
            
            // Remettre l'ancien champ
            $table->integer('employees_count')->nullable();
        });
    }
};