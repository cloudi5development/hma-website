<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories — belong to a department, contain courses. Drive the home "Top
 * Categories" grid (show_home), the navbar mega-menu lists, and the courses
 * listing filter. `tone` keeps the pastel card colour from the original design;
 * `is_featured` keeps the single highlighted item in the mega-menu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();          // glyph image, path relative to /public
            $table->string('tone')->nullable();          // pastel card tone (red|purple|teal|…)
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_home')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('show_home');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
