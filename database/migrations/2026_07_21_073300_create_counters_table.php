<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Counters — the animated stat figures shown on home / about / testimonials.
 * `number` stores the full display string (e.g. "2.5K+"); the model derives the
 * count-up target, suffix and decimal places from it so the animation is
 * preserved without extra columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('number');                    // "2.5K+", "150+", "95%" …
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_home')->default(true);
            $table->boolean('show_about')->default(false);
            $table->boolean('show_testimonials')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
