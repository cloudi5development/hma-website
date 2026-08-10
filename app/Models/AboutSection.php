<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One editable block on the About Us page.
 *
 * The four rows are fixed and keyed — the panel edits them, it never creates or
 * deletes one — so everything that varies between the blocks (what the repeater
 * rows are called, which colours they may take, how many the layout holds) is
 * declared here rather than hard-coded in the views.
 */
class AboutSection extends Model
{
    /** The four blocks, in the order they appear on the page. */
    public const SECTIONS = [
        'story'    => 'Our Story',
        'purpose'  => 'Our Purpose',
        'features' => 'Our Features',
        'approach' => 'Our Approach',
    ];

    /** What one repeater row is called, singular / plural, per section. */
    public const ITEM_NAMES = [
        'story'    => ['Chapter', 'Chapters'],
        'purpose'  => ['Card', 'Cards'],
        'features' => ['Feature', 'Features'],
        'approach' => ['Pill', 'Pills'],
    ];

    /**
     * The colour sets about.css ships for each section, in the order the design
     * uses them — the value is the `--modifier` appended to the card / chip
     * class.
     *
     * The panel does not offer these: picking a palette is a design decision,
     * not something an admin writing copy should have to make. Rows take their
     * colour from their place in the list (see toneFor), which reproduces the
     * page exactly as it was drawn and keeps it looking deliberate however many
     * rows are added.
     */
    public const TONES = [
        'story'    => [],
        'purpose'  => ['vision', 'mission'],
        'features' => ['purple', 'red', 'peach', 'yellow'],
        'approach' => ['blue', 'pink', 'beige', 'green', 'yellow'],
    ];

    /**
     * The six places a pill can sit around the Approach circle, clockwise down
     * the left side then the right. Assigned by row order for the same reason
     * the tones are — see POSITIONS' use in positionFor().
     */
    public const POSITIONS = ['lt', 'lm', 'lb', 'rt', 'rm', 'rb'];

    protected $fillable = ['key', 'label', 'title', 'lead', 'image', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function items(): HasMany
    {
        return $this->hasMany(AboutSectionItem::class)->orderBy('display_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Public URL for the section photo, or null when it carries none. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }

    public function getNameAttribute(): string
    {
        return static::SECTIONS[$this->key] ?? $this->key;
    }

    /** "Chapter" / "Chapters" for this section's repeater rows. */
    public function itemLabel(bool $plural = false): string
    {
        $names = static::ITEM_NAMES[$this->key] ?? ['Item', 'Items'];

        return $plural ? $names[1] : $names[0];
    }

    /** @return array<int, string> the colour cycle, empty when the section has none */
    public function tones(): array
    {
        return static::TONES[$this->key] ?? [];
    }

    /**
     * The colour for the row in this position, wrapping round when there are
     * more rows than colours — so a fifth feature card starts the palette again
     * rather than coming out unstyled.
     */
    public function toneFor(int $index): ?string
    {
        $tones = $this->tones();

        return $tones ? $tones[$index % count($tones)] : null;
    }

    /**
     * Where the pill in this position sits around the circle. There are six
     * places; a seventh pill would land back on the first, so the form says so
     * rather than refusing the row.
     */
    public function positionFor(int $index): ?string
    {
        if (! $this->usesPositions()) {
            return null;
        }

        return static::POSITIONS[$index % count(static::POSITIONS)];
    }

    /** Only the approach pills are placed around a circle. */
    public function usesPositions(): bool
    {
        return $this->key === 'approach';
    }

    /** Story chapters are the only rows that carry their own photo. */
    public function usesItemImages(): bool
    {
        return $this->key === 'story';
    }

    /** Purpose and Approach each show one photo beside / inside their block. */
    public function usesImage(): bool
    {
        return in_array($this->key, ['purpose', 'approach'], true);
    }

    /** The story is told with the label alone — it has no heading or lead. */
    public function usesHeading(): bool
    {
        return $this->key !== 'story';
    }
}
