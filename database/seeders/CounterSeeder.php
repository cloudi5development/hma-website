<?php

namespace Database\Seeders;

use App\Models\Counter;
use Illuminate\Database\Seeder;

/**
 * Seeds the four counters hardcoded in partials/counters.blade.php. All three
 * pages (home, about, testimonials) showed the same set, so every visibility
 * flag is on — keeping those pages identical.
 */
class CounterSeeder extends Seeder
{
    public function run(): void
    {
        $counters = [
            ['label' => 'Students Trained',           'number' => '2.5K+'],
            ['label' => 'Industry-Focused Courses',   'number' => '150+'],
            ['label' => 'Learner Satisfaction',       'number' => '95%'],
            ['label' => 'Hiring & Training Partners', 'number' => '50+'],
        ];

        foreach ($counters as $i => $c) {
            Counter::updateOrCreate(
                ['label' => $c['label']],
                [
                    'number'            => $c['number'],
                    'sort_order'        => $i,
                    'is_active'         => true,
                    'show_home'         => true,
                    'show_about'        => true,
                    'show_testimonials' => true,
                ]
            );
        }
    }
}
