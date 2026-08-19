<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audience — who a course is aimed at ("Freshers, working professionals…").
 *
 * Purely additive and nullable, so every course that already exists keeps
 * working with the column simply left empty until an admin fills it in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('audience')->nullable()->after('certification');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
