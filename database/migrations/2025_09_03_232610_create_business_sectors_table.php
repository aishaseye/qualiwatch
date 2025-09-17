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
        Schema::create('business_sectors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // 'restauration', 'hotellerie', etc.
            $table->string('name'); // 'Restauration', 'Hôtellerie', etc.
            $table->text('description')->nullable();
            $table->string('color')->default('#3B82F6'); // Couleur pour l'interface
            $table->string('icon')->default('briefcase'); // Icône pour l'interface
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
        Schema::dropIfExists('business_sectors');
    }
};
