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
            'image'       => self::DEFAULT_PERSON,
        ];
    }

    /**
     * The yellow shape behind the hero photo. Part of the theme rather than the
     * database: it is the section's artwork, it never changes with the copy, and
     * it has to keep drawing when there is no photo on top of it.
     */
    public const BACKDROP = 'assets/images/Hero-section/hero-right-bg.webp';

    /** The cut-out the panel ships with, until an admin uploads their own. */
    public const DEFAULT_PERSON = 'assets/images/Hero-section/hero-right-person.webp';

    /**
     * Public URL for the person layered over the backdrop — null when no photo
     * is set, which is a supported state: the backdrop then stands alone.
     */
    public function getImageUrlAttribute(): ?string
    {
        return filled($this->image) ? asset($this->image) : null;
    }

    /** Public URL for the static yellow backdrop. Always present. */
    public function getBackdropUrlAttribute(): string
    {
        return asset(self::BACKDROP);
    }
}
