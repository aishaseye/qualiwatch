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
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->unsignedBigInteger('sentiment_id')->nullable()->after('sentiment');
            $table->foreign('sentiment_id')->references('id')->on('sentiments')->onDelete('set null');
            $table->index('sentiment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropForeign(['sentiment_id']);
            $table->dropColumn('sentiment_id');
        });
    }
};
