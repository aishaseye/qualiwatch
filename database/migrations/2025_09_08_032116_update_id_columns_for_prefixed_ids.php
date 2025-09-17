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
        // Extend ID columns to accommodate prefixed IDs (CMP-, SRV-, EMP-, FBK- + UUID)
        Schema::table('companies', function (Blueprint $table) {
            $table->string('id', 50)->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('id', 50)->change();
            $table->string('company_id', 50)->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('id', 50)->change();
            $table->string('company_id', 50)->change();
            $table->string('service_id', 50)->nullable()->change();
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->string('id', 50)->change();
            $table->string('company_id', 50)->change();
            $table->string('service_id', 50)->nullable()->change();
            $table->string('employee_id', 50)->nullable()->change();
        });

        // Update foreign key columns as well
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_id', 50)->nullable()->change();
        });

        // Make manager_id nullable in companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->string('manager_id', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the changes (back to standard UUID length)
        Schema::table('companies', function (Blueprint $table) {
            $table->uuid('id')->change();
            $table->uuid('manager_id')->nullable()->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->uuid('id')->change();
            $table->uuid('company_id')->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->uuid('id')->change();
            $table->uuid('company_id')->change();
            $table->uuid('service_id')->nullable()->change();
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->uuid('id')->change();
            $table->uuid('company_id')->change();
            $table->uuid('service_id')->nullable()->change();
            $table->uuid('employee_id')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('company_id')->nullable()->change();
        });
    }
};
