<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batches move out of the course form and into their own module.
 *
 * Two changes, both consequences of that move:
 *
 * 1. `start_time` / `end_time` — a batch now records when in the day it runs,
 *    not only which days. Optional: plenty of batches are advertised by date
 *    alone, and a blank time must stay blank rather than defaulting to midnight,
 *    which the site would then print.
 *
 * 2. `courses.schedule_enabled` goes. It was the master switch at the top of the
 *    old in-course repeater — with the repeater gone there is nowhere to flip it,
 *    and a hidden column silently suppressing rows an admin can see in the new
 *    listing is worse than no switch at all. Each schedule row's own `is_active`
 *    is now the single thing that decides whether it shows.
 *
 *    Dropping it makes batches on courses that were switched off visible. That is
 *    the intended outcome — they were entered to be shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('duration');
            $table->time('end_time')->nullable()->after('start_time');
        });

        if (Schema::hasColumn('courses', 'schedule_enabled')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('schedule_enabled');
            });
        }
    }

    public function down(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });

        if (! Schema::hasColumn('courses', 'schedule_enabled')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->boolean('schedule_enabled')->default(false)->after('is_featured');
            });
        }
    }
};
