<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group of questions under a heading — "Personal Information", "Education".
 *
 * Hangs off a page on a multi-page form and off the form itself on a single-page
 * one, which is why form_page_id is nullable. Purely presentational: a section
 * decides where a question is drawn, never how it is validated or stored.
 */
class FormSection extends Model
{
    protected $fillable = ['form_id', 'form_page_id', 'title', 'description', 'sort_order'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(FormPage::class, 'form_page_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function heading(int $position): string
    {
        return filled($this->title) ? $this->title : 'Section ' . $position;
    }
}
