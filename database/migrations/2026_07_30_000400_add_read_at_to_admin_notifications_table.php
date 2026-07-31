<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a notification was opened.
 *
 * The bell now splits into Unread and Read tabs, and the Read tab is more useful
 * ordered by when it was actually read than by when the enquiry came in. Existing
 * read rows are backfilled from created_at so they are not left blank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('is_read');
        });

        // Anything already marked read predates this column.
        \Illuminate\Support\Facades\DB::table('admin_notifications')
            ->where('is_read', true)
            ->whereNull('read_at')
            ->update(['read_at' => \Illuminate\Support\Facades\DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }
};
