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
            $table->unsignedSmallInteger('max_suggestions')->default(1)->after('ranking_limit');
        });

        Schema::table('poll_options', function (Blueprint $table) {
            $table->string('suggested_by_hash', 64)->nullable()->index()->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poll_options', function (Blueprint $table) {
            $table->dropColumn('suggested_by_hash');
        });

        Schema::table('polls', function (Blueprint $table) {
            $table->dropColumn('max_suggestions');
        });
    }
};
