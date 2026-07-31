<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The three repeatable blocks on the event details page: "What You Will Learn"
 * (highlights), "Meet Our Speakers" and the event's own FAQ accordion.
 *
 * All three share the same shape — belong to one event, ordered, individually
 * switchable — so the admin repeaters and the frontend queries stay uniform.
 * Rows cascade with the event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('icon', 60)->nullable();      // icon key, see Event::HIGHLIGHT_ICONS
            $table->string('title');
            $table->string('description', 400)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['event_id', 'is_active', 'display_order'], 'event_highlights_lookup');
        });

        Schema::create('event_speakers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('company')->nullable();
            $table->string('photo')->nullable();          // path relative to /public
            $table->string('linkedin')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['event_id', 'is_active', 'display_order'], 'event_speakers_lookup');
        });

        Schema::create('event_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['event_id', 'is_active', 'display_order'], 'event_faqs_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_faqs');
        Schema::dropIfExists('event_speakers');
        Schema::dropIfExists('event_highlights');
    }
};
