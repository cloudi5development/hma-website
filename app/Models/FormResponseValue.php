<?php

namespace App\Models;

use App\Support\FormFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One answer, filed against the question that was asked.
 *
 * It keeps a snapshot of that question — key, label and type — beside the
 * foreign key, and the reason is worth stating plainly: an admin who renames
 * "Course" to "Select Your Preferred Course" a year from now has not changed
 * what the people who already answered were asked, and an admin who deletes the
 * question entirely has not made their answers meaningless. The foreign key is
 * how the current form reads its own responses; the snapshot is how a response
 * stays readable when the form has moved on.
 */
class FormResponseValue extends Model
{
    protected $fillable = [
        'response_id', 'field_id', 'field_key', 'field_label', 'field_type', 'value', 'sort_order',
    ];

    protected $casts = ['sort_order' => 'integer', 'field_id' => 'integer', 'response_id' => 'integer'];

    public function response(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class, 'response_id');
    }

    /** The question, even when it has since been removed from the form. */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'field_id')->withTrashed();
    }

    /**
     * The stored value as PHP: a list for the multi-answer types, a string for
     * everything else.
     *
     * The type is read off the snapshot rather than the live field, so a field
     * whose type was changed after this was submitted still decodes the way it
     * was written.
     */
    public function decoded(): array|string|null
    {
        if (! $this->isList()) {
            return $this->value;
        }

        $decoded = json_decode((string) $this->value, true);

        return is_array($decoded) ? $decoded : array_filter([$this->value]);
    }

    /** True when this value was stored as a JSON list. */
    public function isList(): bool
    {
        return FormFieldType::isMultiple($this->field_type)
            || ($this->field_type === FormFieldType::FILE && str_starts_with((string) $this->value, '['));
    }

    /** True for the two grid types, whose value is a map of row => answer(s). */
    public function isGrid(): bool
    {
        return FormFieldType::needsGrid($this->field_type);
    }

    /**
     * A grid's answers as row LABEL => list of column LABELS.
     *
     * The column stores values, which is what keeps a stored answer stable when
     * the wording changes; this is the reading of it, resolved through the
     * field's current rows and columns and falling back to the raw value for
     * anything the grid no longer offers.
     *
     * @return array<string, array<int, string>>
     */
    public function gridAnswers(): array
    {
        if (! $this->isGrid()) {
            return [];
        }

        $decoded = json_decode((string) $this->value, true);

        if (! is_array($decoded)) {
            return [];
        }

        $rows    = $this->field?->rows->pluck('label', 'value')->all() ?? [];
        $columns = $this->field?->columns->pluck('label', 'value')->all() ?? [];

        $answers = [];

        foreach ($decoded as $row => $picked) {
            $answers[$rows[$row] ?? $row] = collect((array) $picked)
                ->map(fn ($value) => $columns[$value] ?? $value)
                ->all();
        }

        return $answers;
    }

    public function isFile(): bool
    {
        return $this->field_type === FormFieldType::FILE;
    }

    /**
     * The answer as one line of text — what a table cell, a response detail and
     * an export column show.
     *
     * A list joins with commas; a file shows its own names rather than the
     * random ones it is stored under; a choice shows the option's LABEL rather
     * than the value stored against it, so an admin who wrote "Python Full
     * Stack" reads that back and not "python-full-stack".
     *
     * Callers that render many of these should eager-load
     * FormResponse::WITH_ANSWERS — the label lookups go through those relations,
     * and without them a listing of fifty responses is a query per answer.
     */
    public function getDisplayAttribute(): string
    {
        $decoded = $this->decoded();

        if ($this->isFile()) {
            return collect((array) $decoded)
                ->map(fn ($path) => static::originalName($path))
                ->implode(', ');
        }

        // A grid reads as "Product: Excellent · Service: Good" — one line that
        // says which row got which answer, which is the only way a grid fits a
        // table cell or an export column at all.
        if ($this->isGrid()) {
            return collect($this->gridAnswers())
                ->map(fn (array $answers, string $row) => $row . ': ' . implode(', ', $answers))
                ->implode(' · ');
        }

        $values = is_array($decoded) ? $decoded : (blank($decoded) ? [] : [$decoded]);

        return collect($values)->map(fn ($value) => $this->optionLabel((string) $value))->implode(', ');
    }

    /**
     * An option's label, where the field still offers that choice.
     *
     * Falls back to the stored value whenever it cannot be resolved — the field
     * was deleted, or the option was — which is the honest answer: it is what
     * the person chose, even if the form no longer offers it.
     */
    private function optionLabel(string $value): string
    {
        if (! FormFieldType::needsOptions($this->field_type)) {
            return $value;
        }

        return $this->field?->options->firstWhere('value', $value)?->label ?? $value;
    }

    /** The uploaded files this value points at, as stored paths. @return array<int, string> */
    public function filePaths(): array
    {
        if (! $this->isFile()) {
            return [];
        }

        return array_values(array_filter((array) $this->decoded()));
    }

    /**
     * The name a stored upload should download as.
     *
     * Files are saved as "<random>__<original name>" so the readable name
     * survives without trusting it as the path — see FormSubmissionService.
     */
    public static function originalName(string $path): string
    {
        $basename = basename($path);
        $parts    = explode('__', $basename, 2);

        return $parts[1] ?? $basename;
    }
}
