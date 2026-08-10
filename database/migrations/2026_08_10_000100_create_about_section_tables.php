<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The four editable blocks on the About Us page: Our Story, Our Purpose,
 * Our Features and Our Approach.
 *
 * One row per block in `about_sections` holds the heading copy (and the one
 * photo Purpose and Approach each carry); the repeating pieces inside a block —
 * story chapters, vision/mission cards, feature cards, approach pills — all live
 * in `about_section_items`.
 *
 * The four blocks are seeded and keyed ('story', 'purpose', 'features',
 * 'approach') rather than created from the panel: each has its own markup and
 * its own place on the page, so there is nothing to add or delete, only edit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();          // see AboutSection::SECTIONS
            $table->string('label')->nullable();          // the small eyebrow ("Our Story")
            $table->string('title')->nullable();          // the big heading
            $table->text('lead')->nullable();             // the paragraph under it
            $table->string('image')->nullable();          // path relative to /public
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('about_section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('about_section_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('text')->nullable();
            $table->string('image')->nullable();          // story chapters only

            // Which colour set about.css paints the card / chip with, and — for an
            // approach pill — where it sits around the circle. Both are picked from
            // the fixed lists in AboutSection, so the panel can never ask for a
            // modifier class the stylesheet has no rule for.
            $table->string('tone', 30)->nullable();
            $table->string('position', 10)->nullable();

            // Two per-section touches that would otherwise be lost the moment the
            // copy became editable: `zoom` crops a story photo past a baked-in
            // frame, `eyes` puts the animated eyes on a feature card.
            $table->boolean('zoom')->default(false);
            $table->boolean('eyes')->default(false);

            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['about_section_id', 'is_active', 'display_order'], 'about_section_items_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_section_items');
        Schema::dropIfExists('about_sections');
    }
};
