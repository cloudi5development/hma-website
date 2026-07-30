<?php

namespace App\Models;

use App\Models\Concerns\HasPageVisibility;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasPageVisibility;

    /** The three card colour sets the CSS ships (hm-ev-card--{tone}). */
    public const TONES = ['purple', 'teal', 'green'];

    protected $fillable = [
        'speaker', 'title', 'event_date', 'event_time', 'location',
        'type', 'price', 'link', 'image', 'tone',
        'sort_order', 'is_active', 'show_home',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'show_home'  => 'boolean',
        'sort_order' => 'integer',
        'event_date' => 'date',
    ];

    /** Public URL for the speaker cut-out (seeded asset path or admin upload). */
    public function getImageUrlAttribute(): string
    {
        return asset($this->image);
    }

    /**
     * "20 July, 2026" for the card, or null when no date is set so the row is
     * left out rather than printed empty.
     */
    public function getFormattedDateAttribute(): ?string
    {
        return $this->event_date?->format('j F, Y');
    }

    /**
     * "10:00 AM". Stored as a TIME column, which Eloquent hands back as
     * "10:00:00", so it is parsed rather than cast.
     */
    public function getFormattedTimeAttribute(): ?string
    {
        if (blank($this->event_time)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($this->event_time)->format('g:i A');
        } catch (\Throwable) {
            return (string) $this->event_time;   // unexpected format — show it as stored
        }
    }

    /** "10:00" for the <input type="time"> on the admin form. */
    public function getTimeInputValueAttribute(): ?string
    {
        if (blank($this->event_time)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($this->event_time)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }
}
