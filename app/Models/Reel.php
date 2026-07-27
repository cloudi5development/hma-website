<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Reel extends Model
{
    protected $fillable = [
        'title', 'video', 'instagram_url', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Active reels, in display order. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /** Public URL for the uploaded clip (seeded asset path or admin upload). */
    public function getVideoUrlAttribute(): string
    {
        return asset($this->video);
    }
}
