<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Forms module — an admin-built form engine.
 *
 * Every other lead table in this app (contact_enquiries, course_enquiries,
 * event_registrations) has a column per question, because the questions were
 * decided when the table was written. These five tables exist so the questions
 * can be decided in the panel instead: the admin builds the fields, and a
 * response is a bag of values keyed to them.
 *
 * The shape:
 *
 *   forms                 one row per form the admin builds
 *   form_fields             its questions, in display order
 *   form_field_options        the choices under a dropdown / radio / checkbox
 *   form_responses        one row per submission
 *   form_response_values      one row per answered field
 *
 * Two decisions here are what keep old submissions readable, and both are worth
 * understanding before changing anything:
 *
 * 1. `form_fields` is SOFT-deleted. Removing a question from a form that already
 *    has responses must not take the answers with it, and a hard delete would —
 *    the response values point at the field.
 *
 * 2. `form_response_values` keeps a SNAPSHOT of the field's key, label and type
 *    alongside the foreign key. Renaming "Course" to "Select Your Preferred
 *    Course" a year later must not rewrite what a person was actually asked, and
 *    the snapshot is what a response detail falls back to when the field itself
 *    is gone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();

            // `name` is the admin's own label for the form ("2026 Course
            // Enquiry"); `title` is what a visitor reads at the top of the page.
            // They are different jobs and routinely different words.
            $table->string('name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();

            // draft | published | disabled — see Form::STATUSES. Only a published
            // form accepts a submission.
            $table->string('status', 20)->default('draft');

            // Submit-button text, success message, redirect, submission caps and
            // the notification settings. One JSON column rather than a dozen
            // mostly-null ones; the defaults live in Form::settings().
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->index('status');
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();

            // One of App\Support\FormFieldType::TYPES. Stored as a string rather
            // than an enum column so a new type is a code change, not a
            // migration — which is the whole point of the registry.
            $table->string('field_type', 40);

            $table->string('label');

            // The key a response value is filed under. Unique per form, enforced
            // in the builder service rather than by a unique index: soft-deleted
            // rows keep their keys, and a unique index would then refuse to let
            // an admin re-add a question they had removed.
            $table->string('field_key', 120);

            $table->string('placeholder')->nullable();
            $table->string('help_text', 500)->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();

            // {min_length, max_length, min_value, max_value, file_types[],
            //  max_file_size_kb} — only the keys the field's type actually uses.
            $table->json('validation_rules')->nullable();

            // {multiple, rows, condition:{field_key, operator, value}} — the
            // per-type extras, and the slot conditional logic lives in.
            $table->json('settings')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['form_id', 'sort_order']);
            $table->index(['form_id', 'field_key']);
        });

        Schema::create('form_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_field_id')->constrained()->cascadeOnDelete();

            // What the visitor reads, and what gets stored. They are separate so
            // the wording can change without rewriting every stored answer.
            $table->string('label');
            $table->string('value');

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['form_field_id', 'sort_order']);
        });

        Schema::create('form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();

            // New | Contacted | In Progress | Converted | Closed | Rejected —
            // the same workflow the enquiry tables use, extended.
            $table->string('status', 30)->default('New');

            $table->string('ip_address', 45)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'status']);
            $table->index(['form_id', 'submitted_at']);
        });

        Schema::create('form_response_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('form_responses')->cascadeOnDelete();

            // Nulled rather than cascaded when a field is finally purged, so the
            // answer survives on its snapshot alone.
            $table->foreignId('field_id')->nullable()->constrained('form_fields')->nullOnDelete();

            // The snapshot — what this person was actually asked, at the moment
            // they were asked it.
            $table->string('field_key', 120);
            $table->string('field_label');
            $table->string('field_type', 40);

            // A single answer as text; a multi-answer field (checkbox, multi
            // file) as a JSON array. FormResponseValue::decoded() knows which.
            $table->longText('value')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['response_id', 'sort_order']);
            $table->index('field_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_response_values');
        Schema::dropIfExists('form_responses');
        Schema::dropIfExists('form_field_options');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
    }
};
