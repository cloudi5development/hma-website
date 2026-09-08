<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grid fields need two lists of choices, not one.
 *
 * A multiple-choice grid asks its rows ("Product", "Service", "Support") against
 * its columns ("Excellent", "Good", "Average", "Poor"). Both are lists of
 * label/value/order the admin manages exactly the way dropdown options are
 * managed, so rather than a second table — or a JSON blob that the options
 * manager could not reuse — each row here says which list it belongs to.
 *
 *   option  a plain choice, under a dropdown / radio / checkbox   (the default)
 *   row     a grid's row heading
 *   column  a grid's column heading
 *
 * Existing rows default to 'option', which is what every one of them is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_field_options', function (Blueprint $table) {
            $table->string('group', 10)->default('option')->after('form_field_id');

            // The lookups are always "this field's rows" / "this field's
            // columns", in order.
            $table->index(['form_field_id', 'group', 'sort_order'], 'form_field_options_group_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('form_field_options', function (Blueprint $table) {
            $table->dropIndex('form_field_options_group_lookup');
            $table->dropColumn('group');
        });
    }
};
