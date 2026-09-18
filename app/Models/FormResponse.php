<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One submission of a form.
 *
 * The answers are FormResponseValue rows, not columns here — which is the whole
 * point of the module. This row carries only what is true of every submission
 * whatever the form asked: when it arrived, from where, and where the admin has
 * got to with it.
 */
class FormResponse extends Model
{
    /** The workflow, extending the one the enquiry tables already use. */
    public const STATUSES = ['New', 'Contacted', 'In Progress', 'Converted', 'Closed', 'Rejected'];

    /**
     * Everything a rendered answer needs.
     *
     * An answer is stored as values and read back as labels — the option's for a
     * choice, the row's and column's for a grid — so anything that renders many
     * responses loads these or pays a query per answer. Three relations on one
     * table, so three queries however many responses come back.
     */
    public const WITH_ANSWERS = ['values.field.options', 'values.field.rows', 'values.field.columns'];

    protected $fillable = ['form_id', 'status', 'ip_address', 'submitted_at'];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(FormResponseValue::class, 'response_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Answers keyed by field key, for reading one value out without a loop:
     * `$response->keyed()['student_name']`.
     *
     * @return array<string, FormResponseValue>
     */
    public function keyed(): array
    {
        return $this->values->keyBy('field_key')->all();
    }

    /**
     * This response's answer to one question, or null if it has none.
     *
     * Matched on the question's ID, which an answer keeps for good — not on its
     * key. Keys were rebuilt from the label on every save until that was fixed,
     * so a renamed question's history sat under a key it no longer had and
     * showed as blank in the table and the exports. The key is only the
     * fallback for an answer whose question row is gone entirely.
     */
    public function answerFor(FormField $field): ?FormResponseValue
    {
        return $this->values->first(fn (FormResponseValue $value) => $value->field_id === $field->id)
            ?? $this->values->first(fn (FormResponseValue $value) => $value->field_id === null && $value->field_key === $field->field_key);
    }

    public function scopeStatus(Builder $q, ?string $status): Builder
    {
        return $q->when(filled($status), fn ($x) => $x->where('status', $status));
    }

    /**
     * Free-text search across the submitted answers.
     *
     * It has to reach into the values table because there is no "name" column to
     * search — the form may not have asked for a name at all.
     */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $q;
        }

        return $q->whereHas('values', fn (Builder $v) => $v->where('value', 'like', "%{$term}%"));
    }

    /** Slug of the status, for the pill class: "In Progress" → "in-progress". */
    public function getStatusSlugAttribute(): string
    {
        return \Illuminate\Support\Str::slug($this->status);
    }

    public function getSubmittedLabelAttribute(): string
    {
        return ($this->submitted_at ?? $this->created_at)->format('d M Y, g:i a');
    }
}
