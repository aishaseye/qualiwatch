<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('business_sector', [
                'restauration',
                'hotellerie', 
                'commerce_retail',
                'services_sante',
                'services_financiers',
                'education',
                'transport_logistique',
                'immobilier',
                'technologie',
                'industrie_manufacturing',
                'services_publics',
                'tourisme_loisirs',
                'sport_fitness',
                'beaute_bien_etre',
                'services_domicile',
                'conseils_consulting',
                'artisanat',
                'agriculture',
                'energie',
                'telecommunications',
                'media_communication',
                'autres'
            ])->nullable()->after('phone');
            
            $table->string('business_description')->nullable()->after('business_sector');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'business_sector',
                'business_description'
            ]);
        });
    }
};