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
        Schema::create('employee_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // '1-5', '5-15', etc.
            $table->string('name'); // '1 à 5 employés', '5 à 15 employés', etc.
            $table->string('display_label'); // 'Très petite entreprise (1-5)', etc.
            $table->integer('min_count'); // 1, 5, 15, etc.
            $table->integer('max_count')->nullable(); // 5, 15, 25, null pour 100+
            $table->string('color')->default('#3B82F6'); // Couleur pour l'interface
            $table->string('icon')->default('users'); // Icône pour l'interface
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_counts');
    }
};