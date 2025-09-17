<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter les nouveaux rôles pour l'escalation
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('manager', 'super_admin', 'director', 'ceo', 'service_head') DEFAULT 'manager'");
        
        // Ajouter une colonne pour lier les utilisateurs aux entreprises
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('company_id')->nullable()->after('role');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->index(['company_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
        
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('manager', 'super_admin') DEFAULT 'manager'");
    }
};