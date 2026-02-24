<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vibe_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('image_path')->nullable();
            $table->text('caption')->nullable();
            $table->json('keywords')->nullable();
            $table->float('energy')->nullable();
            $table->float('valence')->nullable();
            $table->float('tempo')->nullable();
            $table->float('acousticness')->nullable();
            $table->string('playlist_id')->nullable();
            $table->string('playlist_url')->nullable();
            $table->string('playlist_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vibe_sessions');
    }
};
