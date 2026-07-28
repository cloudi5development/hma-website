<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The department form now owns the department ↔ category link (tick to attach,
 * untick to detach), so an unticked category has to land somewhere — it becomes
 * unassigned rather than being deleted. Hence department_id goes nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Detached categories have no department to go back to — park them on
        // the first one so the column can be NOT NULL again.
        DB::table('categories')->whereNull('department_id')
            ->update(['department_id' => DB::table('departments')->min('id')]);

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable(false)->change();
        });
    }
};
