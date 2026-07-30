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
                'speaker'  => 'Rochelle Fernandez',
                'title'    => 'Learn about no-code tools',
                'image'    => 'assets/images/events/person-2.webp',
                'tone'     => 'purple',
                'date'     => '+2 weeks',
                'time'     => '11:00',
                'location' => 'HireMinds Academy, T. Nagar, Chennai',
            ],
            [
                'speaker'  => 'Regina Phalange',
                'title'    => 'Nail your interviews',
                'image'    => 'assets/images/events/person-3.webp',
                'tone'     => 'teal',
                'date'     => '+3 weeks',
                'time'     => '10:00',
                'location' => 'Plot 456, T. Nagar, Chennai, Tamil Nadu, 600020',
            ],
            [
                'speaker'  => 'Rachel Bennett',
                'title'    => 'Sell your first product online',
                'image'    => 'assets/images/events/person-1.webp',
                'tone'     => 'green',
                'date'     => '+4 weeks',
                'time'     => '16:30',
                'location' => 'Online — Google Meet',
            ],
        ];

        foreach ($events as $i => $e) {
            Event::updateOrCreate(
                ['speaker' => $e['speaker'], 'title' => $e['title']],
                [
                    // Dates are relative so a fresh install always seeds events
                    // that are still upcoming rather than a hardcoded past date.
                    'event_date' => now()->modify($e['date'])->toDateString(),
                    'event_time' => $e['time'],
                    'location'   => $e['location'],
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
