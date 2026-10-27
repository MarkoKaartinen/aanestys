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
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 16)->unique();
            $table->string('admin_token_hash', 64);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 20);
            $table->string('voting_method', 20);
            $table->unsignedSmallInteger('max_choices')->nullable();
            $table->unsignedSmallInteger('ranking_limit')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->boolean('includes_time')->default(false);
            $table->timestamp('suggestions_starts_at')->nullable();
            $table->timestamp('suggestions_ends_at')->nullable();
            $table->timestamp('voting_starts_at');
            $table->timestamp('voting_ends_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};
