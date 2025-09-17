<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter les champs KaliPoints par type à company_statistics
        Schema::table('company_statistics', function (Blueprint $table) {
            $table->integer('positive_kalipoints')->default(0)->after('average_kalipoints_per_feedback');
            $table->integer('negative_kalipoints')->default(0)->after('positive_kalipoints');
            $table->integer('suggestion_kalipoints')->default(0)->after('negative_kalipoints');
            $table->integer('positive_bonus_kalipoints')->default(0)->after('suggestion_kalipoints');
            $table->integer('negative_bonus_kalipoints')->default(0)->after('positive_bonus_kalipoints');
            $table->integer('suggestion_bonus_kalipoints')->default(0)->after('negative_bonus_kalipoints');
            $table->decimal('avg_positive_kalipoints', 5, 2)->default(0)->after('suggestion_bonus_kalipoints');
            $table->decimal('avg_negative_kalipoints', 5, 2)->default(0)->after('avg_positive_kalipoints');
            $table->decimal('avg_suggestion_kalipoints', 5, 2)->default(0)->after('avg_negative_kalipoints');
        });

        // Ajouter les champs KaliPoints par type à service_statistics
        Schema::table('service_statistics', function (Blueprint $table) {
            $table->integer('positive_kalipoints')->default(0)->after('average_kalipoints');
            $table->integer('negative_kalipoints')->default(0)->after('positive_kalipoints');
            $table->integer('suggestion_kalipoints')->default(0)->after('negative_kalipoints');
            $table->integer('positive_bonus_kalipoints')->default(0)->after('suggestion_kalipoints');
            $table->integer('negative_bonus_kalipoints')->default(0)->after('positive_bonus_kalipoints');
            $table->integer('suggestion_bonus_kalipoints')->default(0)->after('negative_bonus_kalipoints');
            $table->decimal('avg_positive_kalipoints', 5, 2)->default(0)->after('suggestion_bonus_kalipoints');
            $table->decimal('avg_negative_kalipoints', 5, 2)->default(0)->after('avg_positive_kalipoints');
            $table->decimal('avg_suggestion_kalipoints', 5, 2)->default(0)->after('avg_negative_kalipoints');
        });

        // Ajouter les champs KaliPoints par type à employee_statistics
        Schema::table('employee_statistics', function (Blueprint $table) {
            $table->integer('positive_kalipoints')->default(0)->after('average_kalipoints_per_feedback');
            $table->integer('negative_kalipoints')->default(0)->after('positive_kalipoints');
            $table->integer('suggestion_kalipoints')->default(0)->after('negative_kalipoints');
            $table->integer('positive_bonus_kalipoints')->default(0)->after('suggestion_kalipoints');
            $table->integer('negative_bonus_kalipoints')->default(0)->after('positive_bonus_kalipoints');
            $table->integer('suggestion_bonus_kalipoints')->default(0)->after('negative_bonus_kalipoints');
            $table->decimal('avg_positive_kalipoints', 5, 2)->default(0)->after('suggestion_bonus_kalipoints');
            $table->decimal('avg_negative_kalipoints', 5, 2)->default(0)->after('avg_positive_kalipoints');
            $table->decimal('avg_suggestion_kalipoints', 5, 2)->default(0)->after('avg_negative_kalipoints');
        });
    }

    public function down(): void
    {
        Schema::table('company_statistics', function (Blueprint $table) {
            $table->dropColumn([
                'positive_kalipoints',
                'negative_kalipoints',
                'suggestion_kalipoints',
                'positive_bonus_kalipoints',
                'negative_bonus_kalipoints',
                'suggestion_bonus_kalipoints',
                'avg_positive_kalipoints',
                'avg_negative_kalipoints',
                'avg_suggestion_kalipoints'
            ]);
        });

        Schema::table('service_statistics', function (Blueprint $table) {
            $table->dropColumn([
                'positive_kalipoints',
                'negative_kalipoints',
                'suggestion_kalipoints',
                'positive_bonus_kalipoints',
                'negative_bonus_kalipoints',
                'suggestion_bonus_kalipoints',
                'avg_positive_kalipoints',
                'avg_negative_kalipoints',
                'avg_suggestion_kalipoints'
            ]);
        });

        Schema::table('employee_statistics', function (Blueprint $table) {
            $table->dropColumn([
                'positive_kalipoints',
                'negative_kalipoints',
                'suggestion_kalipoints',
                'positive_bonus_kalipoints',
                'negative_bonus_kalipoints',
                'suggestion_bonus_kalipoints',
                'avg_positive_kalipoints',
                'avg_negative_kalipoints',
                'avg_suggestion_kalipoints'
            ]);
        });
    }
};