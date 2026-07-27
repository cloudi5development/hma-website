<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activity logs — an audit trail of admin actions (status changes, deletes,
 * views) shown in the dashboard's "Recent Activity" feed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor')->default('Admin');     // who did it (admin email/name)
            $table->string('action');                      // e.g. Status Updated / Enquiry Deleted / Enquiry Viewed
            $table->string('description');                 // human-readable detail
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
