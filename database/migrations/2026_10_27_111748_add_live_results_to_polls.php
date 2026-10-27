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
        Schema::table('polls', function (Blueprint $table) {
            $table->boolean('live_results_visible')->default(true)->after('max_suggestions');
            $table->string('live_token', 32)->nullable()->unique()->after('admin_token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polls', function (Blueprint $table) {
            $table->dropUnique(['live_token']);
            $table->dropColumn(['live_results_visible', 'live_token']);
        });
    }
};
