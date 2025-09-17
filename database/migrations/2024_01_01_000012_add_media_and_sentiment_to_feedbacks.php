<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            // Support audio/vidéo
            $table->string('audio_url')->nullable()->after('attachment_url');
            $table->string('video_url')->nullable()->after('audio_url');
            $table->enum('media_type', ['text', 'audio', 'video', 'mixed'])->default('text')->after('video_url');
            
            // Sentiment par type de feedback
            $table->enum('sentiment', [
                // Positif - appreciation
                'content', 'heureux', 'extremement_satisfait',
                // Négatif - incident  
                'mecontent', 'en_colere', 'laisse_a_desirer',
                // Suggestion - neutre/constructif
                'constructif', 'amelioration', 'proposition'
            ])->nullable()->after('media_type');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn([
                'audio_url',
                'video_url', 
                'media_type',
                'sentiment'
            ]);
        });
    }
};