<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reels — the Instagram reel cards in the shared "Our Journey" (home) /
 * "Career Success" (testimonials) slider. One library feeds both sections.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reels', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('video');                          // uploaded clip, path relative to /public — autoplays in the card
            $table->string('instagram_url')->nullable();      // optional: click the card to open the full reel
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reels');
    }
};
