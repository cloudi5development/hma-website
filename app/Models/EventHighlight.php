<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventHighlight extends Model
{
    protected $fillable = ["event_id", "icon", "title", "description", "display_order", "is_active"];

    protected $casts = [
        'display_order' => 'integer',
        'is_active'     => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Switched-on rows, in the order the admin arranged them. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('display_order')->orderBy('id');
    }

    /**
     * The icon drawing for this row. A key with no artwork — a row saved before
     * the icon set changed, say — falls back to the first icon rather than
     * leaving a blank circle in the middle of the grid.
     */
    public function getIconUrlAttribute(): string
    {
        // reset() takes a reference, so it cannot be handed a constant directly.
        $files = Event::HIGHLIGHT_ICON_FILES;
        $file = $files[$this->icon] ?? reset($files);

        return asset('assets/images/icons-details/' . $file);
    }
}
