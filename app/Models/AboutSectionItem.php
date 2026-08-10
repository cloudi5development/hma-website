<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One repeating piece inside an About Us block — a story chapter, a vision /
 * mission card, a feature card or an approach pill. Which of the columns matter
 * depends on the parent section; see AboutSection.
 */
class AboutSectionItem extends Model
{
    protected $fillable = [
        'about_section_id', 'title', 'text', 'image',
        'tone', 'position', 'zoom', 'eyes',
        'display_order', 'is_active',
    ];

    protected $casts = [
        'zoom'          => 'boolean',
        'eyes'          => 'boolean',
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(AboutSection::class, 'about_section_id');
    }

    /** Public URL for the row's photo, or null when it carries none. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }
}
