<?php

namespace App\Models;

use App\Models\Concerns\HasPageVisibility;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasPageVisibility;

    /** Where the reviewer comes from — the only two values the admin may pick. */
    public const COMPANY_TYPES = ['College', 'Company'];

    protected $fillable = [
        'name', 'role', 'company', 'review', 'rating', 'photo', 'sort_order',
        'is_active', 'show_home', 'show_about', 'show_testimonials',
    ];

    protected $casts = [
        'rating'            => 'integer',
        'is_active'         => 'boolean',
        'show_home'         => 'boolean',
        'show_about'        => 'boolean',
        'show_testimonials' => 'boolean',
        'sort_order'        => 'integer',
    ];

    /** Public URL for the photo (seeded asset path or admin upload). */
    public function getPhotoUrlAttribute(): string
    {
        return asset($this->photo);
    }
}
