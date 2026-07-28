<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rounds the SEO record out into a full page-SEO editor: a canonical URL, the
 * Open Graph and Twitter Card fields (which until now borrowed the meta title
 * and description), and optional JSON-LD blocks emitted into the page head.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->string('canonical_url')->nullable()->after('meta_robots');

            $table->string('og_title')->nullable()->after('canonical_url');
            $table->text('og_description')->nullable()->after('og_title');
            // og_image already exists — it stays where it is.

            $table->string('twitter_title')->nullable()->after('og_image');
            $table->text('twitter_description')->nullable()->after('twitter_title');
            $table->string('twitter_image')->nullable()->after('twitter_description');

            $table->longText('schema_json')->nullable()->after('twitter_image');
            $table->longText('breadcrumb_schema')->nullable()->after('schema_json');
            $table->longText('faq_schema')->nullable()->after('breadcrumb_schema');
        });
    }

    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->dropColumn([
                'canonical_url', 'og_title', 'og_description',
                'twitter_title', 'twitter_description', 'twitter_image',
                'schema_json', 'breadcrumb_schema', 'faq_schema',
            ]);
        });
    }
};
