<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standard form or quiz.
 *
 * A column rather than a key in `settings`, for the same reason structure_type
 * is one: it is chosen on the builder screen and it changes what the builder
 * offers. The settings JSON is written only from the form's own page, and a
 * builder save that tried to set one key in it would have to carry every other
 * setting along or wipe them.
 *
 * What a quiz adds today is small on purpose: a Multiple choice question may
 * carry a correct answer, kept in that field's own settings JSON beside its
 * other per-type extras. Nothing is scored. Every existing form is `standard`
 * by the default, and a standard form behaves exactly as it did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            // standard | quiz — see Form::FORM_TYPES.
            $table->string('form_type', 20)->default('standard')->after('structure_type');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('form_type');
        });
    }
};
