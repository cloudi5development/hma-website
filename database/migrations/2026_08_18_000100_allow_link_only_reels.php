<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A reel may now be an Instagram link instead of an uploaded clip.
 *
 * `video` becomes nullable so a reel can be added by pasting its Instagram URL,
 * with no file to upload. Existing reels are untouched — they keep their upload
 * and keep autoplaying; only the new link-only kind renders as an embed.
 *
 * The two behave differently on the site and that is unavoidable: an uploaded
 * clip is served from this host and can autoplay muted, while Instagram's embed
 * is click-to-play by their design and cannot be made to start on its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reels', function (Blueprint $table) {
            $table->string('video')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Anything added as a link has no file to fall back on, so those rows
        // are cleared rather than left holding a value the column cannot take.
        \Illuminate\Support\Facades\DB::table('reels')->whereNull('video')->delete();

        Schema::table('reels', function (Blueprint $table) {
            $table->string('video')->nullable(false)->change();
        });
    }
};
