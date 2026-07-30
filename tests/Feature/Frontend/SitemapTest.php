<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Course;
use App\Models\Department;
use App\Models\SeoPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_static_pages_and_active_courses(): void
    {
        $course = $this->makeCourse('Talent Acquisition Training', 'talent-acquisition-training');
        $hidden = $this->makeCourse('Retired Course', 'retired-course', false);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $response->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
        $response->assertSee(route('frontend.index'), false);
        $response->assertSee(route('frontend.courses'), false);
        $response->assertSee(route('frontend.contact-us'), false);
        $response->assertSee(route('frontend.course-details', $course->slug), false);

        // Switched-off courses must not be advertised to crawlers.
        $response->assertDontSee(route('frontend.course-details', $hidden->slug), false);
    }

    public function test_pages_marked_noindex_are_left_out(): void
    {
        // The migration seeds a row per frontend page, so update rather than insert.
        SeoPage::updateOrCreate(
            ['key' => 'testimonials'],
            ['label' => 'Testimonials', 'meta_robots' => 'noindex, nofollow', 'is_active' => true],
        );

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertDontSee(route('frontend.testimonials'), false);
        $response->assertSee(route('frontend.about-us'), false);
    }

    public function test_output_is_well_formed_xml_with_the_prolog_first(): void
    {
        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>' . "\n", $xml);

        // What a crawler's parser does. Leading whitespace or a stray tag fails here.
        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($xml));
    }

    public function test_the_template_carries_no_php_open_tag(): void
    {
        // Blade tokenises templates with token_get_all(), so a literal "<?" in
        // this template is read as an opening PHP tag on any host that has
        // short_open_tag enabled and the view stops compiling — which is how the
        // live sitemap came to return a 500. The prolog is prepended by the
        // controller for exactly this reason; keep the template clean of it.
        $template = file_get_contents(resource_path('views/frontend/sitemap.blade.php'));

        $this->assertStringNotContainsString('<?', $template);
    }

    public function test_sitemap_url_redirects_to_the_xml(): void
    {
        $this->get('/sitemap')->assertRedirect('/sitemap.xml');
    }

    public function test_robots_txt_points_at_the_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('Sitemap: ' . url('sitemap.xml'), false);
    }

    private function makeCourse(string $name, string $slug, bool $active = true): Course
    {
        // Slugs are unique, so each course gets its own department/category.
        $department = Department::create([
            'name'      => 'Department for ' . $name,
            'slug'      => 'dept-' . $slug,
            'is_active' => true,
        ]);

        $category = Category::create([
            'department_id' => $department->id,
            'name'          => 'Category for ' . $name,
            'slug'          => 'cat-' . $slug,
            'is_active'     => true,
        ]);

        return Course::create([
            'category_id' => $category->id,
            'name'        => $name,
            'slug'        => $slug,
            'is_active'   => $active,
        ]);
    }
}
