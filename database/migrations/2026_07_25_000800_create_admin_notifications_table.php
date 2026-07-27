<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin notifications — one row per new enquiry, surfaced in the topbar bell
 * (unread count + dropdown) across the admin panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');            // contact | course
            $table->string('title');           // "New Contact Enquiry Received"
            $table->string('body')->nullable();// "John Doe — Python Course"
            $table->string('url')->nullable(); // deep link to the enquiry
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
