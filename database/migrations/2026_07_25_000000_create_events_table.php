<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upcoming Events — the cover-flow carousel in the "Learn, Connect & Grow"
 * section on the home page. Three cards show at a time; extras rotate in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('speaker');                    // presenter name
            $table->string('title');
            $table->string('type')->default('Live Event');
            $table->string('price')->nullable();          // display string, e.g. "₹499/-"
            $table->string('link')->nullable();           // "Event Details" URL
            $table->string('image');                      // person cut-out, path relative to /public
            $table->string('tone')->default('purple');    // purple | teal | green
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_home')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
