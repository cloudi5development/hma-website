<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The batch dates and the training mode belong to the batch, not the course.
 *
 * A course ran once as far as the `courses` row was concerned — one
 * `batch_start_date`, one `training_mode` — while Courses → Schedule already
 * held the real thing: a row per intake, each with its own start and end date.
 * The two disagreed constantly, and the admin had to type the start date twice.
 *
 * So the course loses both fields and the schedule gains the mode. Everything
 * the website printed from the course row — the card's mode line, the details
 * hero date, the "Mode" stat — now reads off that course's soonest upcoming
 * batch instead.
 *
 * Existing batches inherit their course's mode before the column goes, so no
 * page loses a value it was already showing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->string('training_mode', 30)->nullable()->after('duration');
        });

        if (Schema::hasColumn('courses', 'training_mode')) {
            DB::table('courses')
                ->whereNotNull('training_mode')
                ->orderBy('id')
                ->select('id', 'training_mode')
                ->chunk(200, function ($courses) {
                    foreach ($courses as $course) {
                        DB::table('course_schedules')
                            ->where('course_id', $course->id)
                            ->whereNull('training_mode')
                            ->update(['training_mode' => $course->training_mode]);
                    }
                });
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['batch_start_date', 'training_mode']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->date('batch_start_date')->nullable()->after('image');
            $table->string('training_mode')->nullable()->after('duration');
        });

        // Put back what can be put back: the soonest batch's start date and mode
        // become the course's again. A course with no batches keeps both null,
        // which is what the column allowed anyway.
        DB::table('course_schedules')
            ->orderBy('course_id')->orderBy('start_date')->orderBy('id')
            ->select('course_id', 'start_date', 'training_mode')
            ->chunk(200, function ($schedules) {
                foreach ($schedules as $schedule) {
                    DB::table('courses')
                        ->where('id', $schedule->course_id)
                        ->whereNull('batch_start_date')
                        ->update([
                            'batch_start_date' => $schedule->start_date,
                            'training_mode'    => $schedule->training_mode,
                        ]);
                }
            });

        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropColumn('training_mode');
        });
    }
};
