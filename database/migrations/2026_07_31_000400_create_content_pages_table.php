<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Content Management — the standalone written pages (Terms & Conditions,
 * Privacy Policy) the admin edits from the panel.
 *
 * Keyed rather than slugged: the two routes are fixed and the footer links at
 * them, so `key` is the stable identifier and a rename of the visible `title`
 * never moves a URL. The rows are created here rather than left to a seeder,
 * because the admin panel only edits these pages — it cannot add or remove
 * them, and a missing row would mean a menu entry that 404s.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // terms-conditions | privacy-policy
            $table->string('title');
            $table->longText('content')->nullable();  // basic HTML, same as the blog body
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('seo_keywords')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('content_pages')->insert([
            [
                'key'        => 'terms-conditions',
                'title'      => 'Terms & Conditions',
                'content'    => $this->placeholder('Terms & Conditions'),
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'privacy-policy',
                'title'      => 'Privacy Policy',
                'content'    => $this->placeholder('Privacy Policy'),
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }

    /**
     * Something readable until the admin writes the real thing — an empty page
     * behind a live footer link looks broken.
     */
    private function placeholder(string $name): string
    {
        return '<p>This ' . $name . ' page has not been written yet. '
            . 'An administrator can edit it from the admin panel under '
            . 'Content Management.</p>';
    }
};
