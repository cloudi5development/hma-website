<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When and where an event happens, so the carousel card can show it.
 *
 * All three are nullable: an event that has not been scheduled yet simply renders
 * without those rows, exactly as the card looked before this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('event_date')->nullable()->after('title');
            $table->time('event_time')->nullable()->after('event_date');
            // Free text rather than structured address fields — the card prints it
            // verbatim, and venues vary too much to model (campus room, hotel, online).
            $table->string('location', 255)->nullable()->after('event_time');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['event_date', 'event_time', 'location']);
        });
    }
};
