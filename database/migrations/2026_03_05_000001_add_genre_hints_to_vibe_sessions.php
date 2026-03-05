<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add genre_hints JSON column to vibe_sessions.
     * Stores the AI-detected cultural/artistic music genre hints (e.g. ['arabic', 'world music']).
     */
    public function up(): void
    {
        Schema::table('vibe_sessions', function (Blueprint $table) {
            $table->json('genre_hints')->nullable()->after('keywords');
        });
    }

    public function down(): void
    {
        Schema::table('vibe_sessions', function (Blueprint $table) {
            $table->dropColumn('genre_hints');
        });
    }
};
