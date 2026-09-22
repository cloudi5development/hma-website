<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Placement Readiness page, section by section.
 *
 * Same shape as the About Us blocks (about_sections / about_section_items), for
 * the same reason: every section has its own markup and its own place on the
 * page, so the rows are seeded and keyed — the panel edits them, it never
 * creates or deletes one — while everything that REPEATS inside a section (the
 * challenge cards, the four framework stages, the score bands, the training
 * modules, the mock-recruitment steps, the engagement formats, the reasons) is
 * a row in one items table.
 *
 * One items table rather than a table per list: they are the same thing on the
 * page — a heading, sometimes a short second line, sometimes a paragraph — and
 * a `group` says which list a row belongs to. A section can hold more than one
 * list (the diagnostic has its score bands AND its three audience cards), which
 * is exactly what `group` is for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();            // see PlacementSection::SECTIONS
            $table->string('label')->nullable();            // what the panel calls it

            // The copy at the top of a section. Which of these a section shows
            // is declared per key on the model, so the edit form only ever asks
            // for the fields that section actually draws.
            $table->string('eyebrow')->nullable();          // the small line above the heading
            $table->string('title')->nullable();
            $table->text('lead')->nullable();
            $table->text('note')->nullable();               // one extra line: the hero card's heading, the promise under the mock flow

            $table->string('image')->nullable();            // path relative to /public

            // The buttons a section carries (hero, CTA).
            $table->string('primary_label')->nullable();
            $table->string('primary_url')->nullable();
            $table->string('secondary_label')->nullable();
            $table->string('secondary_url')->nullable();

            // How to reach us, on the closing section.
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();

            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('placement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_section_id')->constrained()->cascadeOnDelete();

            // Which list inside the section this row belongs to — 'band' or
            // 'audience' on the diagnostic, 'step' or 'outcome' on the mock
            // recruitment. See PlacementSection::GROUPS.
            $table->string('group', 30)->default('item');

            $table->string('title')->nullable();            // the card heading / classification / format name
            $table->string('subtitle')->nullable();         // the short second line: a score range, a duration
            $table->text('text')->nullable();               // the paragraph under it

            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['placement_section_id', 'group', 'is_active', 'display_order'], 'placement_items_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_items');
        Schema::dropIfExists('placement_sections');
    }
};
