<?php

namespace Database\Seeders;

use App\Models\Blog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a handful of blog posts so the listing, details page and the home
 * "Latest Blog" grid render exactly as the original hardcoded design did.
 * The first post carries the full "technical interview" article verbatim.
 */
class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $excerpt = 'Explore articles, career advice, interview tips, and industry updates written to keep you ahead in a competitive job market.';

        $feature = <<<'HTML'
<p>Hello there! As a marketing manager in the SaaS industry, you might be looking for innovative ways to engage your audience. I bet generative AI has crossed your mind as an option for creating content. Well, let me share from my firsthand experience.</p>
<p>Google encourages high-quality blogs regardless of whether they're written by humans or created using artificial intelligence like ChatGPT. Here's what matters: producing original material with expertise and trustworthiness based on Google E-E-A-T principles.</p>
<p>This means focusing more on producing content for writing rather than primarily employing AI tools to manipulate search rankings. There comes a time when many experienced professionals want to communicate their insights but get stuck due to limited writing skills — that's where <strong>Generative AI</strong> can step in.</p>
<h3>Steering Clear of Common AI Writing Pitfalls</h3>
<p>Jumping headfirst into using AI without a content strategy can lead to some unfortunate results. One common pitfall is people opting for <strong>quantity over quality</strong> — they churn out blogs, but each one feels robotic and soulless.</p>
<p>Remember, our goal here isn't merely satisfying search engines but, more importantly, <strong>knowledge-hungry humans seeking reliable information online</strong>. So keep your audience's needs at heart while leveraging technology's assistance!</p>
<h3>Understand Your Readers</h3>
<p>Understanding your readers is vital when producing blog posts. It's not about filling blanks with popular search terms. Real readability goes beyond that — your content has to "speak" directly to your target audience.</p>
<h3>Conclusion</h3>
<p>As we wrap up, let's remember the heart of blog creation is serving our readers. Whether a post was drafted by experts or AI doesn't matter as long as it's meaningful and high-quality.</p>
HTML;

        $short = <<<'HTML'
<p>Your first technical interview can feel intimidating, but with the right preparation it becomes an opportunity to show how you think. Focus on the fundamentals, practise out loud, and treat every mock interview as the real thing.</p>
<h3>Study the fundamentals</h3>
<p>Data structures, time complexity and a language you're comfortable in will carry you further than memorising trick questions. Depth beats breadth.</p>
<h3>Practise like it's real</h3>
<p>Solve problems on a whiteboard or a blank editor, narrate your reasoning, and review what tripped you up. Consistency is what separates strong candidates from the rest.</p>
HTML;

        $posts = [
            [
                'title'     => 'How to Prepare for Your First Technical Interview',
                'image'     => 'assets/images/blog/blog-11.webp',
                'category'  => 'Interview Tips',
                'content'   => $feature,
                'show_home' => true,
                'is_latest' => true,
            ],
            [
                'title'     => 'Resume Mistakes that Could Cost You Your Dream Job',
                'image'     => 'assets/images/blog/blog-4.webp',
                'category'  => 'Career Advice',
                'content'   => $short,
                'show_home' => true,
                'is_latest' => true,
            ],
            [
                'title'     => 'Why Hands-On Practice Matters More Than Certificates',
                'image'     => 'assets/images/blog/blog-3.webp',
                'category'  => 'Career Advice',
                'content'   => $short,
                'show_home' => true,
                'is_latest' => true,
            ],
            [
                'title'     => 'Landing Your First Developer Role: A Practical Guide',
                'image'     => 'assets/images/blog/blog-2.webp',
                'category'  => 'Development',
                'content'   => $short,
                'show_home' => true,
                'is_latest' => false,
            ],
            [
                'title'     => 'Design Portfolios That Actually Get You Hired',
                'image'     => 'assets/images/blog/blog-1.webp',
                'category'  => 'Design',
                'content'   => $short,
                'show_home' => false,
                'is_latest' => false,
            ],
            [
                'title'     => 'How to Keep Learning After You Get the Job',
                'image'     => 'assets/images/blog/blog-4.webp',
                'category'  => 'Career Advice',
                'content'   => $short,
                'show_home' => false,
                'is_latest' => false,
            ],
        ];

        foreach ($posts as $i => $p) {
            Blog::updateOrCreate(
                ['slug' => Str::slug($p['title'])],
                [
                    'title'        => $p['title'],
                    'excerpt'      => $excerpt,
                    'content'      => $p['content'],
                    'image'        => $p['image'],
                    'author'       => 'Hireminds Academy Admin',
                    'category'     => $p['category'],
                    'published_at' => '2024-07-20',
                    'sort_order'   => $i,
                    'is_active'    => true,
                    'show_home'    => $p['show_home'],
                    'is_latest'    => $p['is_latest'],
                ]
            );
        }
    }
}
