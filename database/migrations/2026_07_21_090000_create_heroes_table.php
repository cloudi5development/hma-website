<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hero — the home page banner. A singleton (one row): the editable left-column
 * copy + the two call-to-action buttons + the right-column image. Everything
 * else in the hero (glow, floating cards, scroll badge, tech marquee) stays
 * static in the Blade markup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heroes', function (Blueprint $table) {
            $table->id();
            $table->string('badge_text');
            $table->string('title');
            $table->text('description');
            $table->string('btn1_text');
            $table->string('btn1_url')->nullable();
            $table->string('btn2_text');
            $table->string('btn2_url')->nullable();
            $table->string('image');            // right-column image, path relative to /public
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heroes');
    }
};
