<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the departments↔categories change: the category form now owns the
 * category ↔ course link (search to attach, remove the tag to detach), and a
 * detached course has to land somewhere — so category_id goes nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Detached courses have no category to go back to — park them on the
        // first one so the column can be NOT NULL again.
        DB::table('courses')->whereNull('category_id')
            ->update(['category_id' => DB::table('categories')->min('id')]);

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }
};
