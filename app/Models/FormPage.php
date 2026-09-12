<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One step of a multi-page form.
 *
 * Carries nothing but a heading and its place in the sequence — the questions
 * point at it, not the other way round, so a page can be renamed or reordered
 * without touching a single answer.
 */
class FormPage extends Model
{
    protected $fillable = ['form_id', 'title', 'description', 'sort_order'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class)->orderBy('sort_order')->orderBy('id');
    }

    /** The questions sitting directly on this page, outside any section. */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * What the stepper shows for this page.
     *
     * A page needs no title — "Page 2" is a perfectly good answer when the admin
     * only wanted to break a long form in half — so the fallback is numbered
     * rather than blank, and is generated at read time rather than stored.
     */
    public function heading(int $position): string
    {
        return filled($this->title) ? $this->title : 'Page ' . $position;
    }
}
