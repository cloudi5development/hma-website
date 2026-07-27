<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blog posts — the blog listing page, the single blog-details page, and the
 * "Latest Blog" section on the home page.
 *
 *   show_home  → include the post in the home "Latest Blog" grid
 *   is_latest  → include the post in the blog-details "The Latest" sidebar
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt');
            $table->longText('content');
            $table->string('image');                                      // thumbnail / hero, path relative to /public
            $table->string('author')->default('Hireminds Academy Admin');
            $table->string('category')->nullable();
            $table->date('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_home')->default(false);                 // home "Latest Blog" grid
            $table->boolean('is_latest')->default(false);                 // blog-details "The Latest" sidebar
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
