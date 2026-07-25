<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student Success Stories — the "Real Career Stories" card grid on the home
 * page. Each card shows a learner's portrait, their package (LPA) and role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('success_stories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('salary');                     // LPA amount, e.g. "9.0" (rendered as ₹9.0 LPA)
            $table->string('image');                      // portrait, path relative to /public
            $table->string('tone')->default('olive');     // olive | teal | green | violet
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_home')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('success_stories');
    }
};
