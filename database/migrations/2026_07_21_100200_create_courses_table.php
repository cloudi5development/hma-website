<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Courses — belong to a category (and through it, a department). Feed the home
 * "Popular Courses" grid (is_popular, max 4), the courses listing page, the
 * course-details page, and the "Continue Learning" rail (is_continue_learning).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();

            // Card / at-a-glance meta
            $table->string('image')->nullable();
            $table->date('batch_start_date')->nullable();
            $table->string('duration')->nullable();       // "30 Days", "3 Months", …
            $table->string('training_mode')->nullable();  // Online | Offline | Hybrid
            $table->string('skill_level')->nullable();    // Beginner | Intermediate | Advanced
            $table->decimal('rating', 2, 1)->default(4.5);

            // Content
            $table->text('short_description')->nullable();
            $table->longText('full_description')->nullable();
            $table->longText('overview')->nullable();
            $table->longText('learning_outcomes')->nullable();
            $table->longText('prerequisites')->nullable();
            $table->longText('certification')->nullable();

            // Flags / ordering
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);          // home Popular Courses (max 4)
            $table->boolean('is_continue_learning')->default(false);// details "Continue Learning"
            $table->boolean('is_featured')->default(false);         // listing "Top Courses"

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('is_popular');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
