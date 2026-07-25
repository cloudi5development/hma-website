<?php

namespace App\Models;

use App\Models\Concerns\HasPageVisibility;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasPageVisibility;

    /** The three card colour sets the CSS ships (hm-ev-card--{tone}). */
    public const TONES = ['purple', 'teal', 'green'];

    protected $fillable = [
        'speaker', 'title', 'type', 'price', 'link', 'image', 'tone',
        'sort_order', 'is_active', 'show_home',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'show_home'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Public URL for the speaker cut-out (seeded asset path or admin upload). */
    public function getImageUrlAttribute(): string
    {
        return asset($this->image);
    }
}
