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
        Schema::table('escalations', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('escalated_at');
            $table->json('notification_channels')->nullable()->after('notified_at');
            $table->integer('notified_users_count')->default(0)->after('notification_channels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('escalations', function (Blueprint $table) {
            $table->dropColumn(['notified_at', 'notification_channels', 'notified_users_count']);
        });
    }
};
