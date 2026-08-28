<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A third way to add a reel: paste a YouTube link.
 *
 * The module already took an uploaded clip or an Instagram link. Plenty of the
 * academy's footage lives on YouTube — Shorts especially, which are already the
 * portrait shape these cards want — and re-uploading it here only to serve the
 * same video twice was the alternative.
 *
 * Additive and nullable, so every existing reel is untouched: a row with a file
 * keeps playing its file, a row with an Instagram link keeps its embed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reels', function (Blueprint $table) {
            $table->string('youtube_url')->nullable()->after('instagram_url');
        });
    }

    public function down(): void
    {
        Schema::table('reels', function (Blueprint $table) {
            $table->dropColumn('youtube_url');
        });
    }
};
