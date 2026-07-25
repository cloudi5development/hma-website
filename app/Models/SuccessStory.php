<?php

namespace App\Models;

use App\Models\Concerns\HasPageVisibility;
use Illuminate\Database\Eloquent\Model;

class SuccessStory extends Model
{
    use HasPageVisibility;

    /** The four card colour sets the CSS ships (hm-story--{tone}). */
    public const TONES = ['olive', 'teal', 'green', 'violet'];

    protected $fillable = [
        'name', 'role', 'salary', 'image', 'tone',
        'sort_order', 'is_active', 'show_home',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'show_home'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Public URL for the portrait (seeded asset path or admin upload). */
    public function getImageUrlAttribute(): string
    {
        return asset($this->image);
    }
}
