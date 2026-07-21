<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Seeds the 12-strong pool from partials/testimonials.blade.php verbatim.
 * The six review photos repeat (customer-1..6, each used twice), matching the
 * original $rimg($n) mapping so the rendered section stays identical.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $pool = [
            ['name' => 'Crystal Maiden', 'role' => 'UI/UX Designer',    'review' => "The mentorship here is on another level. Every project pushed me to think like a real designer, and the feedback was honest and practical. I landed my dream role within weeks of finishing."],
            ['name' => 'Arjun Mehta',    'role' => 'Software Developer', 'review' => "I came in knowing almost nothing and left building full applications with confidence. The hands-on approach and constant support made all the difference in my career."],
            ['name' => 'Priya Nair',     'role' => 'Data Analyst',       'review' => "What stood out was how industry-focused everything felt. Real datasets, real problems, real interviews. I felt prepared from day one when I stepped into my new job."],
            ['name' => 'Rahul Verma',    'role' => 'Frontend Engineer',  'review' => "The trainers genuinely care about your growth. They answered every doubt and helped me polish my portfolio until it truly stood out to recruiters."],
            ['name' => 'Sneha Kapoor',   'role' => 'Product Manager',    'review' => "From resume reviews to mock interviews, the career guidance was incredible. I switched fields completely and still felt supported every single step of the way."],
            ['name' => 'Vikram Singh',   'role' => 'DevOps Engineer',    'review' => "Practical, intense, and worth every minute. The projects mirror exactly what companies expect, so the transition into my first role felt seamless and natural."],
            ['name' => 'Ananya Rao',     'role' => 'Business Analyst',    'review' => "I joined unsure of my direction and left with a clear path and a job offer. The structured roadmap and mentor check-ins kept me motivated the whole way through."],
            ['name' => 'Karan Malhotra', 'role' => 'Cloud Engineer',     'review' => "The labs felt exactly like a real workplace. By the time I interviewed, nothing surprised me — I had already solved similar problems dozens of times here."],
            ['name' => 'Meera Iyer',     'role' => 'QA Engineer',        'review' => "Supportive community, sharp instructors, and projects that actually matter. I rebuilt my confidence and my resume at the same time, and it paid off quickly."],
            ['name' => 'Rohan Das',      'role' => 'Backend Developer',  'review' => "Every doubt I raised got a thoughtful answer. The pace was challenging but fair, and the placement team stayed with me until I signed my offer letter."],
            ['name' => 'Divya Menon',    'role' => 'Digital Marketer',   'review' => "They don't just teach tools, they teach how to think. That mindset shift is what got me hired over candidates with far more experience than me."],
            ['name' => 'Aditya Joshi',   'role' => 'ML Engineer',        'review' => "From fundamentals to deployment, everything connected. I walked into my first role already comfortable shipping real features to real users."],
        ];

        foreach ($pool as $i => $t) {
            $photo = 'assets/images/review/customer-' . (($i % 6) + 1) . '.png';

            Testimonial::updateOrCreate(
                ['name' => $t['name']],
                [
                    'role'              => $t['role'],
                    'company'           => null,
                    'review'            => $t['review'],
                    'rating'            => 5,
                    'photo'             => $photo,
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
