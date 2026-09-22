<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One repeating row inside a Placement Readiness block: a challenge card, a
 * framework stage, a score band, a training module, a step of the mock
 * recruitment, an engagement format, a reason to choose HMA.
 *
 * What the row MEANS is its `group` — see PlacementSection::GROUPS, which also
 * says which of title / subtitle / text that group uses and what to call them.
 */
class PlacementItem extends Model
{
    protected $fillable = [
        'placement_section_id', 'group', 'title', 'subtitle', 'text', 'display_order', 'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(PlacementSection::class, 'placement_section_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** "01", "02" … — the number the page prints on a card, from its place in its list. */
    public function number(int $index): string
    {
        return str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    }
}
