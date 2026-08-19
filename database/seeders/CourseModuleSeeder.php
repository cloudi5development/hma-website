<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseFaq;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the full Courses hierarchy from the site's original hardcoded content:
 * the four mega-menu departments, their categories (icons/tones taken from the
 * home "Top Categories" grid), and enough courses per home category to match
 * the original course-count labels — so the frontend renders as before while
 * everything is now database-driven.
 */
class CourseModuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->tree() as $order => $dept) {
            $department = Department::updateOrCreate(
                ['slug' => Str::slug($dept['name'])],
                [
                    'name'       => $dept['name'],
                    'description' => $dept['description'] ?? null,
                    'image'      => $dept['image'],
                    'sort_order' => $order,
                    'is_active'  => true,
                ]
            );

            foreach ($dept['categories'] as $catOrder => $cat) {
                $category = Category::updateOrCreate(
                    ['slug' => Str::slug($cat['name'])],
                    [
                        'department_id' => $department->id,
                        'name'          => $cat['name'],
                        'description'   => $cat['description'] ?? null,
                        'icon'          => 'assets/images/categories/' . $cat['icon'],
                        'tone'          => $cat['tone'],
                        'sort_order'    => $catOrder,
                        'is_active'     => true,
                        'show_home'     => $cat['home'] ?? false,
                        'is_featured'   => $cat['featured'] ?? false,
                    ]
                );

                $this->seedCourses($category, $cat['courses'] ?? 0);
            }
        }

        $this->markPopular();
    }

    /**
     * Mark exactly four courses as Popular (the home grid's cap) — the first
     * course of the first four home categories, matching the original four-card
     * "Popular Courses" section.
     */
    private function markPopular(): void
    {
        Course::query()->update(['is_popular' => false]);

        $slugs = ['IT & Software', 'Cloud & DevOps', 'Data & AI', 'Cyber Security'];

        foreach ($slugs as $catName) {
            Course::where('slug', Str::slug($catName . ' Fundamentals'))->update(['is_popular' => true]);
        }
    }

    /** Create N sample courses under a category (idempotent by slug). */
    private function seedCourses(Category $category, int $count): void
    {
        if ($count < 1) {
            return;
        }

        $suffixes = ['Fundamentals', 'Professional Program', 'Bootcamp', 'Masterclass',
                     'Advanced Track', 'Certification Course', 'Practical Workshop'];
        $durations = ['30 Days', '45 Days', '3 Months', '6 Months'];
        $modes     = ['Online', 'Offline', 'Hybrid'];
        $levels    = ['Beginner', 'Intermediate', 'Advanced'];

        for ($i = 0; $i < $count; $i++) {
            $name = $category->name . ' ' . $suffixes[$i % count($suffixes)];
            $slug = Str::slug($name);

            $course = Course::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id'          => $category->id,
                    'name'                 => $name,
                    'image'                => 'assets/images/courses/course-' . (($i % 4) + 1) . '.webp',
                    'duration'             => $durations[$i % count($durations)],
                    'skill_level'          => $levels[$i % count($levels)],
                    'rating'               => 4.5,
                    'short_description'    => 'Build practical, job-ready skills in ' . $category->name . ' through hands-on projects and expert mentorship.',
                    'full_description'     => 'This ' . $name . ' is designed to take you from the fundamentals through to real-world application, with live projects, interview preparation and dedicated placement support.',
                    'overview'             => 'A career-focused program blending practical learning, live projects and mentorship so you graduate genuinely job-ready.',
                    'learning_outcomes'    => "Master core concepts and tools\nBuild real-world projects for your portfolio\nPrepare for interviews with mock sessions\nEarn an industry-recognised certificate",
                    'prerequisites'        => 'No prior experience required — just commitment and curiosity.',
                    'certification'        => 'On completion you receive an industry-recognised Hire Minds Academy certificate.',
                    'sort_order'           => $i,
                    'is_active'            => true,
                    'is_popular'           => false,
                    'is_continue_learning' => $i === 0,
                    'is_featured'          => $i === 0,
                    'meta_title'           => $name . ' — Hire Minds Academy',
                    'meta_description'     => 'Enrol in the ' . $name . ' at Hire Minds Academy and gain practical, job-ready skills.',
                    'meta_keywords'        => strtolower($category->name) . ', training, course, hire minds academy',
                ]
            );

            $this->seedFaqs($course);
            $this->seedBatch($course, $modes[$i % count($modes)], 14 + $i * 7);
        }
    }

    /**
     * One upcoming batch per course.
     *
     * The course row used to carry a batch_start_date and a training_mode of its
     * own; both moved onto the batch, and the card's mode line and the details
     * hero's dates read off the soonest upcoming one. So a seeded install has to
     * schedule something, or every course renders with those slots empty.
     */
    private function seedBatch(Course $course, string $mode, int $startsInDays): void
    {
        $start = now()->addDays($startsInDays)->startOfDay();

        $course->schedules()->updateOrCreate(
            ['start_date' => $start->toDateString()],
            [
                'end_date'      => $start->copy()->addMonths(3)->toDateString(),
                'duration'      => $course->duration,
                'training_mode' => $mode,
                'is_active'     => true,
                'show_fee'      => true,
            ]
        );
    }

    /** Four standard FAQs per course (idempotent — cleared then re-added). */
    private function seedFaqs(Course $course): void
    {
        $course->faqs()->delete();

        $faqs = [
            ['q' => 'Who is this course for?', 'a' => 'Anyone looking to build practical, job-ready skills — beginners and working professionals alike. No prior experience is required.'],
            ['q' => 'How long is the program?', 'a' => 'It runs for ' . ($course->duration ?: 'a few months') . ' with flexible weekday and weekend batches to fit your schedule.'],
            ['q' => 'Will I receive placement support?', 'a' => 'Yes. Every learner gets resume building, mock interviews and direct referrals to our hiring partners.'],
            ['q' => 'Do I get a certificate?', 'a' => 'Absolutely. On successful completion you receive an industry-recognised certificate you can add to your resume and LinkedIn.'],
        ];

        foreach ($faqs as $order => $f) {
            $course->faqs()->create([
                'question'   => $f['q'],
                'answer'     => $f['a'],
                'sort_order' => $order,
            ]);
        }
    }

    /**
     * The department → category tree. Icons/tones/course-counts for the eight
     * home categories come straight from the original "Top Categories" grid;
     * the rest keep the mega-menu categories with cycled icons/tones.
     */
    private function tree(): array
    {
        // Cycle for categories that had no icon/tone in the original design.
        $fallbackIcons = ['iconsax-setting.png', 'iconsax-share.png', 'iconsax-monitor.png',
                          'iconsax-cloud.png', 'iconsax-bank.png', 'iconsax-shield-security.png'];
        $fallbackTones = Category::TONES;
        $n = 0;
        $fill = function (array $cat) use (&$n, $fallbackIcons, $fallbackTones) {
            $cat['icon'] ??= $fallbackIcons[$n % count($fallbackIcons)];
            $cat['tone'] ??= $fallbackTones[$n % count($fallbackTones)];
            $n++;

            return $cat;
        };

        return [
            [
                'name' => 'Technical', 'image' => 'assets/images/Header/domain-1.webp',
                'categories' => array_map($fill, [
                    ['name' => 'IT & Software',  'icon' => 'iconsax-monitor.png',         'tone' => 'red',    'home' => true, 'featured' => true, 'courses' => 7],
                    ['name' => 'Cloud & DevOps', 'icon' => 'iconsax-cloud.png',           'tone' => 'purple', 'home' => true, 'courses' => 4],
                    ['name' => 'Data & AI',      'icon' => 'iconsax-setting.png',         'tone' => 'teal',   'home' => true, 'courses' => 6],
                    ['name' => 'Cyber Security', 'icon' => 'iconsax-shield-security.png', 'tone' => 'pink',   'home' => true, 'courses' => 3],
                    ['name' => 'Engineering',    'icon' => 'iconsax-setting.png',         'tone' => 'blue',   'home' => true, 'courses' => 5],
                ]),
            ],
            [
                'name' => 'Non-technical', 'image' => 'assets/images/Header/domain-2.webp',
                'categories' => array_map($fill, [
                    ['name' => 'Communication',  'icon' => 'iconsax-share.png', 'tone' => 'gold',  'home' => true, 'courses' => 3],
                    ['name' => 'Leadership',     'icon' => 'iconsax-share.png', 'tone' => 'peach', 'home' => true, 'courses' => 2],
                    ['name' => 'HR & Behavioral'],
                    ['name' => 'Sales'],
                    ['name' => 'Early Career'],
                    ['name' => 'Trainer'],
                ]),
            ],
            [
                'name' => 'Business', 'image' => 'assets/images/Header/domain-3.webp',
                'categories' => array_map($fill, [
                    ['name' => 'Project Management'],
                    ['name' => 'Finance', 'icon' => 'iconsax-bank.png', 'tone' => 'green', 'home' => true, 'courses' => 4],
                    ['name' => 'Operations'],
                    ['name' => 'Digital Marketing'],
                ]),
            ],
            [
                'name' => 'Industry', 'image' => 'assets/images/Header/domain-4.webp',
                'categories' => array_map($fill, [
                    ['name' => 'Manufacturing'],
                    ['name' => 'Healthcare'],
                    ['name' => 'Banking'],
                    ['name' => 'Hospitality'],
                    ['name' => 'Energy'],
                ]),
            ],
        ];
    }
}
