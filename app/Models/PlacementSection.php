<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One block of the Placement Readiness page.
 *
 * The nine rows are fixed and keyed — the panel edits them, it never creates or
 * deletes one — so everything that differs between the blocks (which heading
 * fields they draw, what their repeating rows are called, which of title /
 * second line / paragraph each of those rows uses) is declared here instead of
 * being written into the views. One admin form and one renderer then serve all
 * nine, and a change of copy is never a change of code.
 *
 * Same shape as AboutSection, deliberately: this is the pattern the panel
 * already uses for a page whose sections are fixed but whose contents are not.
 */
class PlacementSection extends Model
{
    /** The nine blocks, in the order they appear on the page. */
    public const SECTIONS = [
        'hero'       => 'Hero',
        'challenge'  => 'The Challenge',
        'framework'  => 'The HMA Framework',
        'diagnostic' => 'Readiness Diagnostic',
        'modules'    => 'Training Modules',
        'mock'       => 'Mock Recruitment',
        'formats'    => 'Engagement Formats',
        'why'        => 'Why HireMinds Academy',
        'cta'        => 'CTA / Contact',
    ];

    /**
     * The heading fields each block draws. The edit form asks for exactly these
     * — a section is never given a box for something its markup ignores.
     *
     * 'note' is the one extra line a block carries: the heading on the hero's
     * card, the responsible-placement promise under the mock-recruitment flow.
     */
    public const FIELDS = [
        'hero'       => ['eyebrow', 'title', 'lead', 'note', 'buttons'],
        'challenge'  => ['eyebrow', 'title', 'lead'],
        'framework'  => ['eyebrow', 'title', 'lead'],
        'diagnostic' => ['eyebrow', 'title', 'lead'],
        'modules'    => ['eyebrow', 'title', 'lead'],
        'mock'       => ['eyebrow', 'title', 'lead', 'note'],
        'formats'    => ['eyebrow', 'title', 'lead'],
        'why'        => ['eyebrow', 'title', 'lead'],
        'cta'        => ['eyebrow', 'title', 'lead', 'primary', 'contact'],
    ];

    /**
     * The repeating lists inside each block.
     *
     *   label / plural  what the panel calls one row
     *   fields          which of title / subtitle / text that row uses
     *   titleLabel …    what those boxes are called on this list
     *   hint            the line under the list's heading in the panel
     *
     * A block can hold more than one list: the diagnostic has its score bands
     * and its three audience cards, the mock recruitment its flow steps and its
     * two outcome cards.
     *
     * @var array<string, array<string, array>>
     */
    public const GROUPS = [
        'hero' => [
            'point' => [
                'label' => 'Point', 'plural' => 'Card Points', 'fields' => ['title'],
                'titleLabel' => 'Point', 'hint' => 'The list inside the card beside the heading.',
            ],
        ],
        'challenge' => [
            'card' => [
                'label' => 'Challenge', 'plural' => 'Challenges', 'fields' => ['title', 'text'],
                'hint' => 'Numbered 01, 02, 03 … in the order below.',
            ],
        ],
        'framework' => [
            'stage' => [
                'label' => 'Stage', 'plural' => 'Stages', 'fields' => ['title', 'text'],
                'hint' => 'Diagnose, Develop, Demonstrate, Deploy — numbered in the order below.',
            ],
        ],
        'diagnostic' => [
            'band' => [
                'label' => 'Band', 'plural' => 'Score Bands', 'fields' => ['title', 'subtitle', 'text'],
                'titleLabel' => 'Classification', 'subtitleLabel' => 'Score', 'textLabel' => 'Recommended intervention',
                'hint' => 'One row of the score table.',
            ],
            'audience' => [
                'label' => 'Card', 'plural' => 'What Each Audience Gets', 'fields' => ['title', 'text'],
                'hint' => 'The three cards under the table — student, department, institution.',
            ],
        ],
        'modules' => [
            'module' => [
                'label' => 'Module', 'plural' => 'Training Modules', 'fields' => ['title', 'text'],
                'hint' => 'Add, edit, reorder or hide a module — the page draws whatever is here.',
            ],
        ],
        'mock' => [
            'step' => [
                'label' => 'Step', 'plural' => 'Recruitment Flow', 'fields' => ['title'],
                'titleLabel' => 'Step', 'hint' => 'The numbered stages of the mock selection process.',
            ],
            'outcome' => [
                'label' => 'Card', 'plural' => 'Outcomes & Support', 'fields' => ['title', 'text'],
                'hint' => 'The cards under the flow.',
            ],
        ],
        'formats' => [
            'format' => [
                'label' => 'Format', 'plural' => 'Formats', 'fields' => ['title', 'subtitle', 'text'],
                'titleLabel' => 'Format', 'subtitleLabel' => 'Duration', 'textLabel' => 'Best suited for',
                'hint' => 'One row of the engagement table.',
            ],
        ],
        'why' => [
            'feature' => [
                'label' => 'Reason', 'plural' => 'Reasons', 'fields' => ['title', 'text'],
                'hint' => 'The cards in the grid.',
            ],
        ],
        'cta' => [],
    ];

    protected $fillable = [
        'key', 'label', 'eyebrow', 'title', 'lead', 'note', 'image',
        'primary_label', 'primary_url', 'secondary_label', 'secondary_url',
        'phone', 'email', 'display_order', 'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PlacementItem::class)->orderBy('display_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /* ============================== WHAT IT DRAWS ============================= */

    /** Does this block draw that heading field? ('eyebrow', 'note', 'buttons', 'contact', …) */
    public function uses(string $field): bool
    {
        return in_array($field, self::FIELDS[$this->key] ?? [], true);
    }

    /** The repeating lists this block holds, keyed by group. */
    public function groups(): array
    {
        return self::GROUPS[$this->key] ?? [];
    }

    /** One list's settings, or an empty array when this block has no such list. */
    public function group(string $group): array
    {
        return $this->groups()[$group] ?? [];
    }

    /** What one row of a list is called: itemLabel('band') → "Band" / plural → "Score Bands". */
    public function itemLabel(string $group, bool $plural = false): string
    {
        $settings = $this->group($group);

        return $plural
            ? ($settings['plural'] ?? 'Rows')
            : ($settings['label'] ?? 'Row');
    }

    /** Does a row of this list use that box? ('title', 'subtitle', 'text') */
    public function groupUses(string $group, string $field): bool
    {
        return in_array($field, $this->group($group)['fields'] ?? [], true);
    }

    /** What a row's box is called on this list — "Classification" rather than "Title". */
    public function fieldLabel(string $group, string $field): string
    {
        return $this->group($group)[$field . 'Label'] ?? ucfirst($field);
    }

    /** This block's rows in one list, from the already-loaded relation. */
    public function itemsIn(string $group): Collection
    {
        return $this->items->where('group', $group)->values();
    }

    /** The same, but only what the public page should draw. */
    public function visibleIn(string $group): Collection
    {
        return $this->itemsIn($group)->where('is_active', true)->values();
    }

    /* ================================ DISPLAY ================================ */

    /** The panel's name for this block, falling back to the key's own label. */
    public function getNameAttribute(): string
    {
        return $this->label ?: (self::SECTIONS[$this->key] ?? ucfirst($this->key));
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }

    /** The nine blocks in page order, whatever order the database returns them in. */
    public static function inPageOrder(Builder|string|null $query = null)
    {
        $order = array_keys(self::SECTIONS);

        return ($query instanceof Builder ? $query : static::query())
            ->get()
            ->sortBy(fn (self $section) => array_search($section->key, $order, true))
            ->values();
    }
}
