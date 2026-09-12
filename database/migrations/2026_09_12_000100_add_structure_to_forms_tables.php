<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pages and sections for the form builder.
 *
 * A form was a flat list of questions. It can now also be a list grouped into
 * sections, a sequence of pages, or pages that are themselves grouped — without
 * three parallel systems, and without disturbing a single existing form.
 *
 * The shape that makes that possible is ONE question table with two nullable
 * parents:
 *
 *   forms.structure_type   which of the four shapes this form is
 *   form_pages             its steps, when it has any
 *   form_sections          its groups, hung off a page or off the form
 *   form_fields.form_page_id / .form_section_id   where a question sits
 *
 * Both columns nullable is the whole trick. A plain form leaves them null and
 * behaves exactly as it did; a section form fills in the section; a multi-page
 * form fills in the page; a multi-page form with sections fills in both. There
 * is no second questions table and no branch in the response pipeline — a
 * response is still a bag of values keyed to fields, whatever shape the form
 * that collected it happened to be.
 *
 * Two deliberate choices:
 *
 * 1. Every existing form becomes `plain` by the column default, with both
 *    foreign keys null. Nothing to backfill, and nothing about an already
 *    published form changes.
 *
 * 2. form_fields' new keys are nullOnDelete, NOT cascade. Deleting a page must
 *    never delete questions — questions are what responses point at, and losing
 *    one would take a year of answers with it. A question whose page is deleted
 *    comes loose and is rendered on the first page; the builder is what decides
 *    whether it should really go, and it already knows how to retire a question
 *    without stranding its answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            // plain | sections | pages | pages_sections — see Form::STRUCTURES.
            $table->string('structure_type', 20)->default('plain')->after('slug');
        });

        Schema::create('form_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();

            // Both optional. A page that is only there to break a long form in
            // two does not need a name, and forcing one would mean inventing
            // "Page 2" on the admin's behalf.
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['form_id', 'sort_order']);
        });

        Schema::create('form_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();

            // Null on a single-page form: the section hangs off the form itself.
            // Set on a multi-page form: the section lives on that page, and goes
            // when the page does.
            $table->foreignId('form_page_id')->nullable()->constrained('form_pages')->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['form_id', 'sort_order']);
            $table->index(['form_page_id', 'sort_order']);
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->foreignId('form_page_id')->nullable()->after('form_id')
                ->constrained('form_pages')->nullOnDelete();

            $table->foreignId('form_section_id')->nullable()->after('form_page_id')
                ->constrained('form_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropConstrainedForeignId('form_section_id');
            $table->dropConstrainedForeignId('form_page_id');
        });

        Schema::dropIfExists('form_sections');
        Schema::dropIfExists('form_pages');

        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('structure_type');
        });
    }
};
