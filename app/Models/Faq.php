<?php

namespace App\Models;

use App\Models\Concerns\HasPageVisibility;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasPageVisibility;

    protected $fillable = [
        'question', 'answer', 'sort_order', 'is_active',
        'show_home', 'show_about', 'show_courses', 'show_testimonials',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'show_home'         => 'boolean',
        'show_about'        => 'boolean',
        'show_courses'      => 'boolean',
        'show_testimonials' => 'boolean',
        'sort_order'        => 'integer',
    ];

    /** FAQ adds a Courses page (course-details); contact still falls back to home. */
    protected function pageColumn(string $page): string
    {
        return match ($page) {
            'about-us'                    => 'show_about',
            'courses', 'course-details'   => 'show_courses',
            'testimonials'                => 'show_testimonials',
            default                       => 'show_home',
        };
    }
}
