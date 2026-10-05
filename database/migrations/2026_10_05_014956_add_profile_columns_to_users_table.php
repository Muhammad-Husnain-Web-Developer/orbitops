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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_workspace_id')->nullable()->after('id')->constrained('workspaces')->nullOnDelete();
            $table->string('title')->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('title');
            $table->string('timezone')->default('UTC')->after('avatar_path');
            $table->string('theme', 10)->default('dark')->after('timezone');
            $table->json('notification_preferences')->nullable()->after('theme');
            $table->timestamp('last_active_at')->nullable()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_workspace_id');
            $table->dropColumn(['title', 'avatar_path', 'timezone', 'theme', 'notification_preferences', 'last_active_at']);
        });
    }
};
