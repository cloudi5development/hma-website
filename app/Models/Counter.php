<?php

namespace App\Models;

use App\Models\Concerns\HasPageVisibility;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasPageVisibility;

    protected $fillable = [
        'label', 'number', 'sort_order', 'is_active',
        'show_home', 'show_about', 'show_testimonials',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'show_home'         => 'boolean',
        'show_about'        => 'boolean',
        'show_testimonials' => 'boolean',
        'sort_order'        => 'integer',
    ];

    /** Numeric part of `number` — the count-up target (e.g. "2.5" from "2.5K+"). */
    public function getTargetAttribute(): string
    {
        preg_match('/^[\d.]+/', $this->number, $m);

        return $m[0] ?? '0';
    }

    /** Trailing non-numeric part — the suffix (e.g. "K+", "%", "+"). */
    public function getSuffixAttribute(): string
    {
        return trim(preg_replace('/^[\d.]+/', '', $this->number));
    }

    /** Decimal places in the target, so the count-up formats correctly. */
    public function getDecimalsAttribute(): int
    {
        $dot = strpos($this->target, '.');

        return $dot === false ? 0 : strlen(substr($this->target, $dot + 1));
    }
}
