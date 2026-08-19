<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upcoming batches for a course.
 *
 * A course runs more than once — "Ethical Hacking" may start in August and
 * again in December, at different fees — so the dates cannot live on the
 * courses row (there is already a single `batch_start_date` there, which the
 * course card prints; it stays untouched and is not what this feeds). One row
 * per batch, cascading with the course.
 *
 * `schedule_enabled` on the course is the master switch the admin flips at the
 * top of the Course Schedule block: off, the course keeps its batches but shows
 * none of them on the site.
 *
 * Both of those notes have since been overtaken: `schedule_enabled` was dropped
 * when batches became their own module (2026_08_13_000200), and the course's
 * `batch_start_date` was dropped along with its `training_mode` when both moved
 * onto the batch (2026_08_19_000200). This file is left as it ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('duration', 60)->nullable();

            // Nullable rather than 0: "no fee entered yet" and "this batch is
            // free" are different things, and the section must never print ₹0.
            $table->decimal('fee', 10, 2)->nullable();
            $table->boolean('show_fee')->default(true);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // The home page asks for active, not-yet-finished batches in date
            // order; this covers that lookup.
            $table->index(['course_id', 'is_active', 'start_date'], 'course_schedules_lookup');
            $table->index('start_date');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('schedule_enabled')->default(false)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('schedule_enabled');
        });

        Schema::dropIfExists('course_schedules');
    }
};
