<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hero extends Model
{
    protected $fillable = [
        'badge_text', 'title', 'description',
        'btn1_text', 'btn1_url', 'btn2_text', 'btn2_url', 'image',
    ];

    /**
     * The single hero row. Falls back to an unsaved instance carrying the
     * original design copy, so the home page renders identically even before the
     * seeder runs (and without a write during a GET request).
     */
    public static function current(): self
    {
        return static::first() ?? static::make(static::defaults());
    }

    /**
     * The original hardcoded hero content — the single source of truth shared by
     * the seeder and the read-time fallback above.
     */
    public static function defaults(): array
    {
        return [
            'badge_text'  => 'Learn • Practice • Get Hired',
            'title'       => 'Your Future Starts With the Right Skills',
            'description' => "Build practical knowledge, work on real-world projects, and prepare for opportunities across today's fastest-growing industries.",
            'btn1_text'   => 'Explore Course',
            'btn1_url'    => '#courses',
            'btn2_text'   => 'Apply',
            'btn2_url'    => null,   // null → links to the Contact page at render time
            'image'       => 'assets/images/Hero-section/hero-right-img.webp',
        ];
    }

    /** Public URL for the right-column image (seeded asset path or admin upload). */
    public function getImageUrlAttribute(): string
    {
        return asset($this->image);
    }
}
