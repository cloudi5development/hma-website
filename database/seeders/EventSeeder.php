<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

/**
 * Seeds the three events verbatim from the old hardcoded array in
 * frontend/index.blade.php, so the rendered carousel stays identical.
 */
class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'speaker' => 'Rochelle Fernandez',
                'title'   => 'Learn about no-code tools',
                'image'   => 'assets/images/events/person-2.webp',
                'tone'    => 'purple',
            ],
            [
                'speaker' => 'Regina Phalange',
                'title'   => 'Nail your interviews',
                'image'   => 'assets/images/events/person-3.webp',
                'tone'    => 'teal',
            ],
            [
                'speaker' => 'Rachel Bennett',
                'title'   => 'Sell your first product online',
                'image'   => 'assets/images/events/person-1.webp',
                'tone'    => 'green',
            ],
        ];

        foreach ($events as $i => $e) {
            Event::updateOrCreate(
                ['speaker' => $e['speaker'], 'title' => $e['title']],
                [
                    'type'       => 'Live Event',
                    'price'      => '₹499/-',
                    'link'       => '#',
                    'image'      => $e['image'],
                    'tone'       => $e['tone'],
                    'sort_order' => $i,
                    'is_active'  => true,
                    'show_home'  => true,
                ]
            );
        }
    }
}
