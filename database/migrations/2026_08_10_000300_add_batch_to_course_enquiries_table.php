<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which batch the enquiry is about.
 *
 * The Apply buttons in "Upcoming Course Schedules" open the same enquiry modal
 * the course-details page uses, but for one specific batch — so the team needs
 * to see which one they are being asked about. A readable snapshot ("20 Aug
 * 2026 – 20 Nov 2026") rather than a schedule_id: the enquiry has to keep
 * meaning after that batch is edited or removed, exactly like course_name.
 *
 * Nullable, because an enquiry sent from the course-details modal names no
 * batch at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enquiries', function (Blueprint $table) {
            $table->string('batch')->nullable()->after('course_name');
        });
    }

    public function down(): void
    {
        Schema::table('course_enquiries', function (Blueprint $table) {
            $table->dropColumn('batch');
        });
    }
};
