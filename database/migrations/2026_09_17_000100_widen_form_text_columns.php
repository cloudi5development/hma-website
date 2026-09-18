<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * No length limits on what an admin writes into a form.
 *
 * The builder used to stop a question at 190 characters, an option at 190, a
 * description at 500 — and the columns underneath were VARCHAR(255), so taking
 * the validation away alone would only have moved the refusal from a friendly
 * message to a database error. A quiz question with a code sample in it, or an
 * option that is a full sentence, is ordinary; these columns become TEXT.
 *
 * `form_response_values.field_label` goes with them: it is the snapshot of the
 * question a response was given to, copied on every submission, so a long
 * question would otherwise fail the moment someone answered it.
 *
 * Left as they are, on purpose: field_key (a storage key the module derives and
 * shortens itself), the type and status codes, and forms.slug, which carries a
 * unique index — the module shortens a long name before making a link from it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('title')->change();
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->text('label')->change();
            $table->text('placeholder')->nullable()->change();
            $table->text('help_text')->nullable()->change();
        });

        Schema::table('form_field_options', function (Blueprint $table) {
            $table->text('label')->change();
            $table->text('value')->change();
        });

        Schema::table('form_pages', function (Blueprint $table) {
            $table->text('title')->nullable()->change();
        });

        Schema::table('form_sections', function (Blueprint $table) {
            $table->text('title')->nullable()->change();
        });

        Schema::table('form_response_values', function (Blueprint $table) {
            $table->text('field_label')->change();
        });
    }

    /** Back to the old widths. Anything longer than them is cut on the way. */
    public function down(): void
    {
        Schema::table('form_response_values', function (Blueprint $table) {
            $table->string('field_label')->change();
        });

        Schema::table('form_sections', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
        });

        Schema::table('form_pages', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
        });

        Schema::table('form_field_options', function (Blueprint $table) {
            $table->string('label')->change();
            $table->string('value')->change();
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('label')->change();
            $table->string('placeholder')->nullable()->change();
            $table->string('help_text', 500)->nullable()->change();
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->string('name')->change();
            $table->string('title')->change();
        });
    }
};
