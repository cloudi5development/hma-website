<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bring contact_enquiries in line with the enquiry spec: a workflow status
 * (New / Contacted / Closed), an optional subject, and the sender's IP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_enquiries', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('phone');
            $table->string('ip_address', 45)->nullable()->after('message');
            $table->string('status')->default('New')->after('ip_address');   // New | Contacted | Closed
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('contact_enquiries', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['subject', 'ip_address', 'status']);
        });
    }
};
