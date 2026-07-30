<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-course brochure PDF, uploaded in the admin panel and served by the
 * "Brochure" button on the course details page.
 *
 * Nullable: a course without one simply does not render the button.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Path relative to /public, same convention as `image`.
            $table->string('brochure')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('brochure');
        });
    }
};
