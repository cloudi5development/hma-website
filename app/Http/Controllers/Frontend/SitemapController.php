<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Course;
use App\Models\SeoPage;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Static frontend pages, as route name => [priority, change frequency].
     * Slug-driven pages (courses, blog posts) are appended from the database.
     */
    private const PAGES = [
        'frontend.index'        => ['1.0', 'weekly'],
        'frontend.about-us'     => ['0.8', 'monthly'],
        'frontend.courses'      => ['0.9', 'weekly'],
        'frontend.testimonials' => ['0.7', 'monthly'],
        'frontend.blog'         => ['0.8', 'weekly'],
        'frontend.contact-us'   => ['0.7', 'yearly'],
    ];

    /**
     * XML sitemap for search engines. Every public page is listed with its last
     * modified date, so Google re-crawls a course or post when it is edited
     * rather than on its own schedule.
     *
     * Pages the admin has set to "noindex" in SEO → Page SEO are left out — a
     * sitemap that advertises pages you asked not to be indexed is a Search
     * Console warning.
     */
    public function index(): Response
    {
        // Only active rows count — an inactive row is not applied to the page
        // either, so its robots value should not exclude anything here.
        $noindex = SeoPage::query()
            ->where('is_active', true)
            ->where('meta_robots', 'like', '%noindex%')
            ->pluck('key')
            ->all();

        $urls = [];

        foreach (self::PAGES as $route => [$priority, $frequency]) {
            $key = str_replace('frontend.', '', $route);

            if (in_array($key, $noindex, true)) {
                continue;
            }

            $urls[] = [
                'loc'        => route($route),
                'lastmod'    => now(),
                'changefreq' => $frequency,
                'priority'   => $priority,
            ];
        }

        if (! in_array('course-details', $noindex, true)) {
            foreach (Course::active()->get(['slug', 'updated_at']) as $course) {
                $urls[] = [
                    'loc'        => route('frontend.course-details', $course->slug),
                    'lastmod'    => $course->updated_at,
                    'changefreq' => 'monthly',
                    'priority'   => '0.8',
                ];
            }
        }

        if (! in_array('blog-details', $noindex, true)) {
            foreach (Blog::active()->get(['slug', 'updated_at']) as $blog) {
                $urls[] = [
                    'loc'        => route('frontend.blog-details', $blog->slug),
                    'lastmod'    => $blog->updated_at,
                    'changefreq' => 'monthly',
                    'priority'   => '0.6',
                ];
            }
        }

        // The prolog is prepended here rather than written in the Blade file: a
        // literal "<?xml" in a template is read as an opening PHP tag on hosts
        // with short_open_tag enabled (live is one), and the view then fails to
        // compile. Doing it here also guarantees the prolog is the first thing
        // in the document, with no leading blank line for a strict XML parser
        // to reject.
        $body = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . ltrim(view('frontend.sitemap', ['urls' => $urls])->render());

        return response($body)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * robots.txt. Served by Laravel rather than shipped as a static file so the
     * sitemap line always carries the domain the site is actually running on
     * (localhost while developing, the live host in production).
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /storage/',
            '',
            'Sitemap: ' . url('sitemap.xml'),
        ];

        return response(implode("\n", $lines) . "\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
