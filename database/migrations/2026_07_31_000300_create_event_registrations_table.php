<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Event registrations — submissions from the "Register for the Event" modal on
 * the event details page.
 *
 * Shaped like course_enquiries on purpose, so the admin list, the status
 * workflow and the exports all behave the same way across the Leads menu:
 *   • event_id is nullable and nulls on delete, so a registration outlives the
 *     event it was made against;
 *   • event_title is a snapshot taken at submit time, which is what the list and
 *     the export read — a later rename must not rewrite historical records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('event_title');                  // snapshot at registration time
            $table->string('name');
            $table->string('email');
            $table->string('phone');                        // mobile number
            $table->string('city')->nullable();
            $table->string('professional_status')->nullable();
            $table->string('organisation')->nullable();     // company or college
            // The visitor has to tick the box to submit; stored so the consent is
            // on the record rather than only implied by the row existing.
            $table->boolean('agreed_terms')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('status')->default('New');       // New | Contacted | Closed
            $table->timestamps();

            $table->index('event_id');
            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
