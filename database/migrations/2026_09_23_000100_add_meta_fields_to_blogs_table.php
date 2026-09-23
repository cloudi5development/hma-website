<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-post SEO, the same three fields a course already carries, plus the alt
 * text for the cover image.
 *
 * A blog post cannot take its meta from SEO → Page SEO: that module is keyed by
 * route name and deliberately skips the "-details" routes, because every post
 * shares one route and needs its own title and description. Left blank, each
 * field falls back to what the page used before — the post title, the excerpt,
 * and the title again for the alt text — so existing posts read exactly as they
 * did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('content');
            $table->string('meta_description')->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');
            // Beside the image it describes, not with the meta fields.
            $table->string('image_alt')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'meta_keywords', 'image_alt']);
        });
    }
};
