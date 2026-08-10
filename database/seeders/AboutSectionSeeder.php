<?php

namespace Database\Seeders;

use App\Models\AboutSection;
use Illuminate\Database\Seeder;

/**
 * The About Us page exactly as it was written before the four blocks became
 * editable. Keyed on the section key and re-runnable: the section row is
 * updateOrCreate'd, its rows are replaced.
 *
 * Image paths point at the artwork that already ships in public/assets — an
 * upload through the panel replaces one with a "storage/…" path, and the page
 * renders either the same way.
 */
class AboutSectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sections() as $key => $data) {
            $section = AboutSection::updateOrCreate(
                ['key' => $key],
                [
                    'label'     => $data['label'],
                    'title'     => $data['title'] ?? null,
                    'lead'      => $data['lead'] ?? null,
                    'image'     => $data['image'] ?? null,
                    'is_active' => true,
                ],
            );

            $section->items()->delete();

            foreach (array_values($data['items']) as $order => $item) {
                $section->items()->create($item + [
                    'display_order' => $order,
                    'is_active'     => true,
                ]);
            }
        }
    }

    private function sections(): array
    {
        $art = 'assets/images/about-page/';

        return [
            'story' => [
                'label' => 'Our Story',
                'items' => [
                    [
                        'image' => $art . 'people-1.webp',
                        'title' => 'It All Started with a Simple Mission',
                        'text'  => 'HireMinds Academy was founded with a clear purpose—to bridge the gap between traditional education and real industry expectations. We recognized that many learners possessed academic knowledge but lacked the practical skills needed to build successful careers. This vision inspired us to create training programs that focus on hands-on learning, expert mentorship, and career readiness from day one.',
                    ],
                    [
                        'image' => $art . 'people-2.webp',
                        'title' => 'Learning That Matches Industry Needs',
                        'text'  => 'Every course at HireMinds is carefully designed around current industry requirements rather than outdated academic models. Our learners gain practical experience through real-world projects, interactive classroom sessions, case studies, and expert guidance. By focusing on skills that employers actively seek, we help students build confidence while preparing them for professional challenges and workplace expectations.',
                    ],
                    [
                        'image' => $art . 'people-3.webp',
                        'title' => 'Guiding Every Step of the Career Journey',
                        'text'  => 'Our responsibility goes beyond delivering quality training. We support learners throughout their career journey with personalized mentorship, resume building, interview preparation, communication skills, and placement assistance. Every student receives the guidance needed to confidently transition from learning to employment, ensuring they are prepared for opportunities in today\'s competitive job market.',
                    ],
                    [
                        // people-4.webp ships with a baked-in shadow border, so its
                        // photo only fills ~79% of the canvas. zoom crops past that
                        // frame so it matches the framing of the other four.
                        'image' => $art . 'people-4.webp',
                        'zoom'  => true,
                        'title' => 'Building Careers, Creating Impact',
                        'text'  => 'Today, HireMinds Academy continues to empower students, graduates, career switchers, and working professionals through industry-focused education. Every successful placement, completed project, and learner achievement reflects our commitment to creating meaningful career opportunities. As industries continue to evolve, we remain dedicated to helping learners develop future-ready skills that support long-term professional growth and success.',
                    ],
                    [
                        'image' => $art . 'people-5.webp',
                        'title' => 'Shaping the Future of Professional Learning',
                        'text'  => 'As technology and industries continue to evolve, HireMinds Academy remains committed to delivering future-ready education that adapts to changing workforce demands. We continuously update our programs, strengthen industry partnerships, and introduce innovative learning experiences that help learners stay competitive. Our journey doesn\'t end with a certificate—it begins with building confident professionals ready to make a lasting impact in their careers.',
                    ],
                ],
            ],

            'purpose' => [
                'label' => 'Our Purpose',
                'title' => 'Driven by Purpose. Guided by Vision.',
                'lead'  => 'Everything we do is built around one goal—to equip learners with practical skills, inspire confidence, and create opportunities that lead to meaningful careers and long-term success.',
                'image' => $art . 'our-purpose.webp',
                'items' => [
                    [
                        'tone'  => 'vision',
                        'title' => 'Our Vision !',
                        'text'  => 'To deliver practical, industry-focused training that empowers individuals with job-ready skills, builds confidence through hands-on learning, and prepares them for long-term career success. We are committed to bridging the gap between academic knowledge and industry expectations by providing expert-led instruction, real-world projects, continuous mentorship, and career guidance. At the same time, today\'s fast-changing business environment.',
                    ],
                    [
                        'tone'  => 'mission',
                        'title' => 'Our Mission !',
                        'text'  => 'To empower individuals and organizations by building skilled, confident, and future-ready professionals through practical, industry-focused learning experiences. We envision creating a workforce that embraces innovation, adapts to emerging technologies, and thrives in an evolving job market. By fostering continuous learning, professional excellence, and career growth, we aim to strengthening organizations across industries.',
                    ],
                ],
            ],

            'features' => [
                'label' => 'Our Features',
                'title' => 'Shaping Future-Ready Professionals',
                'lead'  => 'At HireMinds Academy, we combine expert guidance, practical training, and career support to help learners achieve their professional goals.',
                'items' => [
                    ['tone' => 'purple', 'title' => 'Who We Are', 'text' => null, 'eyes' => true],
                    ['tone' => 'red',    'title' => 'Industry-Focused Training', 'text' => 'Learn with a curriculum designed around real industry requirements.'],
                    ['tone' => 'peach',  'title' => 'Hands-On Learning', 'text' => 'Learn by doing with projects and practical exercises.'],
                    ['tone' => 'yellow', 'title' => 'Career Support', 'text' => 'Get guidance, interview preparation, and placement assistance.'],
                ],
            ],

            'approach' => [
                'label' => 'Our Approach',
                'title' => 'Our Learning Approach',
                'lead'  => 'From classroom sessions to career guidance, every step of our training is designed to prepare learners for real-world opportunities.',
                'image' => $art . 'our-approach-square.webp',
                'items' => [
                    ['position' => 'lt', 'tone' => 'blue',   'title' => 'Industry-Aligned Curriculum'],
                    ['position' => 'lm', 'tone' => 'pink',   'title' => 'Hands-On Projects'],
                    ['position' => 'lb', 'tone' => 'beige',  'title' => 'Classroom Learning'],
                    ['position' => 'rt', 'tone' => 'green',  'title' => 'Hands-On Projects'],
                    ['position' => 'rm', 'tone' => 'yellow', 'title' => 'Career Guidance'],
                    ['position' => 'rb', 'tone' => 'blue',   'title' => 'Placement Support'],
                ],
            ],
        ];
    }
}
