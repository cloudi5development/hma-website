<?php

namespace App\Models;

use App\Models\Concerns\HasPageVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasPageVisibility;

    /** The three card colour sets the CSS ships (hm-ev-card--{tone}). */
    public const TONES = ['purple', 'teal', 'green'];

    /** The formats an event may run in — the badge printed on the card. */
    public const TYPES = ['Live', 'Online', 'Workshop', 'Webinar', 'Classroom'];

    /**
     * Icons offered for a "What You Will Learn" highlight — the label the admin
     * picks from. One entry per drawing in HIGHLIGHT_ICON_FILES, so the dropdown
     * can never offer an icon that has no artwork behind it.
     */
    public const HIGHLIGHT_ICONS = [
        'ai'      => 'AI & Insight',
        'tech'    => 'Tools & Technology',
        'project' => 'Projects & Code',
        'career'  => 'Career',
        'network' => 'People & Networking',
        'seating' => 'Sessions & Seating',
    ];

    /**
     * The artwork behind each key, from public/assets/images/icons-details/ —
     * the same set the course details page draws from, so the two pages share
     * one icon language.
     *
     * These are transparent outline PNGs, which is why the details page paints
     * them with a CSS mask rather than an <img>: that lets the glyph take the
     * page's green instead of the near-black it ships as.
     */
    public const HIGHLIGHT_ICON_FILES = [
        'ai'      => 'hugeicons_ai-brain-03.png',
        'tech'    => 'cpu.png',
        'project' => 'square-code.png',
        'career'  => 'briefcase-business.png',
        'network' => 'user-star.png',
        'seating' => 'armchair.png',
    ];

    protected $fillable = [
        'speaker', 'title', 'slug', 'short_description', 'description',
        'event_date', 'event_time', 'end_time',
        'location', 'venue', 'city', 'state', 'country',
        'type', 'price', 'offer_price', 'discount_percentage',
        'total_seats', 'available_seats',
        'duration', 'language', 'level', 'organizer',
        'link', 'image', 'banner_image', 'thumbnail', 'tone',
        'sort_order', 'is_active', 'show_home', 'is_featured',
        'seo_title', 'seo_description', 'seo_keywords', 'og_image', 'schema_json',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'show_home'           => 'boolean',
        'is_featured'         => 'boolean',
        'sort_order'          => 'integer',
        'discount_percentage' => 'integer',
        'total_seats'         => 'integer',
        'available_seats'     => 'integer',
        'event_date'          => 'date',
    ];

    /** Slug is derived from the title whenever it is left blank. */
    protected static function booted(): void
    {
        static::saving(function (Event $event) {
            if (blank($event->slug)) {
                $event->slug = static::uniqueSlug($event->title, $event->id);
            }
        });

        // The card colour is handed out here rather than chosen in the panel, so
        // the carousel and the listing stay varied on their own. Only on create:
        // the tone is stored, not derived per list, which is what keeps an event
        // the same colour on the carousel, the listing and its own details page.
        static::creating(function (Event $event) {
            if (blank($event->tone)) {
                $event->tone = static::nextTone();
            }
        });
    }

    /**
     * The next colour in the palette. Keyed off how many events already exist,
     * so consecutive additions walk the palette rather than repeating.
     */
    public static function nextTone(): string
    {
        return static::TONES[static::count() % count(static::TONES)];
    }

    /** A slug not already taken by another event. */
    public static function uniqueSlug(?string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $title) ?: 'event';
        $slug = $base;
        $n = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    // NOTE: getRouteKeyName() is deliberately NOT overridden. The admin panel
    // binds {event} implicitly, so switching the route key to the slug would
    // rewrite every /admin/events/{id} URL as a side effect. The frontend does
    // not need it — Frontend\EventController::show() queries the slug directly.

    /* ============================== RELATIONS ============================== */

    public function highlights(): HasMany
    {
        return $this->hasMany(EventHighlight::class)->orderBy('display_order')->orderBy('id');
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(EventSpeaker::class)->orderBy('display_order')->orderBy('id');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(EventFaq::class)->orderBy('display_order')->orderBy('id');
    }

    /* =============================== SCOPES =============================== */

    /** Events other than this one, for the sidebar's "Upcoming Events". */
    public function scopeUpcomingBesides(Builder $q, self $event, int $take = 3): Builder
    {
        return $q->active()->whereKeyNot($event->id)->limit($take);
    }

    /* ============================= ACCESSORS ============================= */

    /** Public URL for the speaker cut-out (seeded asset path or admin upload). */
    public function getImageUrlAttribute(): string
    {
        return asset($this->image);
    }

    /**
     * Banner for the details page. Falls back to the shared events banner rather
     * than the speaker cut-out: that image is a transparent portrait and stretches
     * badly across a full-width strip.
     */
    public function getBannerUrlAttribute(): string
    {
        return asset($this->banner_image ?: 'assets/images/events/event-listing.webp');
    }

    /** Small square used by the sidebar list; falls back to the card image. */
    public function getThumbnailUrlAttribute(): string
    {
        return asset($this->thumbnail ?: $this->image);
    }

    /**
     * "20 July, 2026" for the card, or null when no date is set so the row is
     * left out rather than printed empty.
     */
    public function getFormattedDateAttribute(): ?string
    {
        return $this->event_date?->format('j F, Y');
    }

    /** "Sunday" under the date on the details page. */
    public function getWeekdayAttribute(): ?string
    {
        return $this->event_date?->format('l');
    }

    /**
     * "10:00 AM". Stored as a TIME column, which Eloquent hands back as
     * "10:00:00", so it is parsed rather than cast.
     */
    public function getFormattedTimeAttribute(): ?string
    {
        return $this->formatTime($this->event_time);
    }

    public function getFormattedEndTimeAttribute(): ?string
    {
        return $this->formatTime($this->end_time);
    }

    /** "10:00 AM - 06:00 PM", or just the start when there is no end. */
    public function getTimeRangeAttribute(): ?string
    {
        $start = $this->formatted_time;

        if (! $start) {
            return null;
        }

        return $this->formatted_end_time ? $start . ' - ' . $this->formatted_end_time : $start;
    }

    /** "10:00" for the <input type="time"> on the admin form. */
    public function getTimeInputValueAttribute(): ?string
    {
        return $this->timeInput($this->event_time);
    }

    public function getEndTimeInputValueAttribute(): ?string
    {
        return $this->timeInput($this->end_time);
    }

    /**
     * The address as one line: the structured fields when the admin filled them,
     * otherwise the single `location` field the carousel and listing already use.
     */
    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([$this->venue, $this->city, $this->state, $this->country]);

        return $parts ? implode(', ', $parts) : ($this->location ?: null);
    }

    /** True when the event carries a discounted price worth showing a badge for. */
    public function getHasOfferAttribute(): bool
    {
        return filled($this->offer_price) && filled($this->price);
    }

    /** What the visitor pays — the offer when there is one, else the price. */
    public function getPayablePriceAttribute(): ?string
    {
        return $this->has_offer ? $this->offer_price : $this->price;
    }

    /** Seats still open, never negative and null when seating is not tracked. */
    public function getSeatsLeftAttribute(): ?int
    {
        return $this->available_seats === null ? null : max(0, $this->available_seats);
    }

    private function formatTime(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('g:i A');
        } catch (\Throwable) {
            return (string) $value;   // unexpected format — show it as stored
        }
    }

    private function timeInput(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }
}
