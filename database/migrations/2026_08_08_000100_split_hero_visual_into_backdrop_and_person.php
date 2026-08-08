<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The hero's right-hand visual used to be one baked image: the yellow blob and
 * the student photographed onto it. It is now two layers —
 *
 *   • the yellow backdrop, static, shipped with the theme;
 *   • the person, an admin upload, optional.
 *
 * So `image` becomes nullable: clearing it leaves the backdrop on its own rather
 * than a hole in the layout, which is what the panel offers as "no photo".
 */
return new class extends Migration
{
    /** The old combined asset, retired by this change. */
    private const COMBINED = 'assets/images/Hero-section/hero-right-img.webp';

    /** The cut-out that replaces it. */
    private const PERSON = 'assets/images/Hero-section/hero-right-person.webp';

    public function up(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
        });

        // Any row still pointing at the combined file would draw the old blob a
        // second time on top of the new backdrop. Move it to the cut-out; rows
        // carrying a real admin upload are left alone.
        DB::table('heroes')->where('image', self::COMBINED)->update(['image' => self::PERSON]);
    }

    public function down(): void
    {
        DB::table('heroes')->where('image', self::PERSON)->update(['image' => self::COMBINED]);
        DB::table('heroes')->whereNull('image')->update(['image' => self::COMBINED]);

        Schema::table('heroes', function (Blueprint $table) {
            $table->string('image')->nullable(false)->change();
        });
    }
};
