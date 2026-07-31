<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Everything the event details page (/events/{slug}) needs.
 *
 * Additive only. The columns the carousel and the listing already read —
 * speaker, title, event_date, event_time, location, type, price, link, image,
 * tone, is_active, show_home — keep their meaning:
 *   • event_time stays the START time; end_time is new beside it. Renaming it to
 *     start_time would have meant touching the admin form, the listing, the
 *     carousel and their tests for no functional gain.
 *   • location stays the one-line address used by those surfaces; venue/city/
 *     state/country are the structured fields the details page composes from,
 *     falling back to location when they are blank.
 *   • is_active is the publish flag, so no separate `status` column is added.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');

            $table->text('short_description')->nullable()->after('slug');
            $table->longText('description')->nullable()->after('short_description');

            $table->string('banner_image')->nullable()->after('image');
            $table->string('thumbnail')->nullable()->after('banner_image');

            $table->time('end_time')->nullable()->after('event_time');

            $table->string('venue')->nullable()->after('location');
            $table->string('city', 120)->nullable()->after('venue');
            $table->string('state', 120)->nullable()->after('city');
            $table->string('country', 120)->nullable()->after('state');

            // Prices are display strings ("₹499/-"), matching the existing `price`.
            $table->string('offer_price', 40)->nullable()->after('price');
            $table->unsignedSmallInteger('discount_percentage')->nullable()->after('offer_price');

            $table->unsignedInteger('total_seats')->nullable()->after('discount_percentage');
            $table->unsignedInteger('available_seats')->nullable()->after('total_seats');

            $table->string('duration', 80)->nullable()->after('available_seats');
            $table->string('language', 80)->nullable()->after('duration');
            $table->string('level', 80)->nullable()->after('language');

            $table->string('organizer')->nullable()->after('level');

            $table->boolean('is_featured')->default(false)->after('show_home');

            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('og_image')->nullable();
            $table->text('schema_json')->nullable();
        });

        // Backfill slugs for rows that predate the column, keeping them unique.
        $seen = [];

        foreach (DB::table('events')->select('id', 'title')->get() as $event) {
            $base = Str::slug($event->title) ?: 'event';
            $slug = $base;
            $n = 2;

            while (in_array($slug, $seen, true) || DB::table('events')->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $n++;
            }

            $seen[] = $slug;
            DB::table('events')->where('id', $event->id)->update(['slug' => $slug]);
        }

        Schema::table('events', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug', 'short_description', 'description', 'banner_image', 'thumbnail',
                'end_time', 'venue', 'city', 'state', 'country',
                'offer_price', 'discount_percentage', 'total_seats', 'available_seats',
                'duration', 'language', 'level', 'organizer', 'is_featured',
                'seo_title', 'seo_description', 'seo_keywords', 'og_image', 'schema_json',
            ]);
        });
    }
};
