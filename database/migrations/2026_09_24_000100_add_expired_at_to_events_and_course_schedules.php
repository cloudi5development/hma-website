<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When an event or batch was switched off automatically because its start date
 * had passed (see App\Support\ContentExpiry).
 *
 * The stamp is what lets an admin bring one back: the daily sweep only touches
 * rows that have never expired, so once an admin re-ticks "Active" on an expired
 * row it stays on until they switch it off themselves. Giving the row a new
 * start date clears the stamp, and it expires again after that date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('expired_at')->nullable()->after('is_active');
        });

        Schema::table('course_schedules', function (Blueprint $table) {
            $table->timestamp('expired_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('expired_at');
        });

        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropColumn('expired_at');
        });
    }
};
