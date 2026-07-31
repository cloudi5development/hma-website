<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSpeaker extends Model
{
    protected $fillable = ["event_id", "name", "designation", "company", "photo", "linkedin", "display_order", "is_active"];

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
}
