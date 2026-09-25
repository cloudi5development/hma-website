<?php

namespace Tests\Feature\Frontend;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The blog listing's toolbar.
 *
 * The category dropdown used to be a hard-coded list of plain buttons whose
 * only job was to close itself — picking one changed nothing, because the
 * controller never looked at the request. These pin the filtering down.
 */
class BlogFilterTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(string $title, ?string $category, array $overrides = []): Blog
    {
        return Blog::create(array_merge([
            'title'    => $title,
            'slug'     => \Illuminate\Support\Str::slug($title),
            'excerpt'  => 'A short summary.',
            'content'  => '<p>Body.</p>',
            'image'    => 'assets/images/blog/blog-1.webp',
            'category' => $category,
        ], $overrides));
    }

    private function seedThree(): void
    {
        $this->makePost('Landing Your First Developer Role', 'Development');
        $this->makePost('Resume Mistakes to Avoid', 'Career Advice');
        $this->makePost('Designing a Portfolio', 'Design');
    }

    /* ============================== FILTERING ============================== */

    public function test_choosing_a_category_shows_only_that_category(): void
    {
        $this->seedThree();

        $page = $this->get(route('frontend.blog', ['category' => 'Development']))
            ->assertOk()
            ->assertSee('Landing Your First Developer Role')
            ->assertDontSee('Resume Mistakes to Avoid')
            ->assertDontSee('Designing a Portfolio')
            ->getContent();

        // The trigger says which one is being shown.
        $this->assertStringContainsString('>Development</span>', $page);
    }

    public function test_no_category_shows_every_post(): void
    {
        $this->seedThree();

        $this->get(route('frontend.blog'))
            ->assertOk()
            ->assertSee('Landing Your First Developer Role')
            ->assertSee('Resume Mistakes to Avoid')
            ->assertSee('Designing a Portfolio')
            ->assertSee('>Categories</span>', false);
    }

    /** An inactive post stays hidden whatever the filter says. */
    public function test_a_hidden_post_is_not_revealed_by_a_filter(): void
    {
        $this->makePost('Draft Developer Post', 'Development', ['is_active' => false]);
        $this->makePost('Live Developer Post', 'Development');

        $this->get(route('frontend.blog', ['category' => 'Development']))
            ->assertOk()
            ->assertSee('Live Developer Post')
            ->assertDontSee('Draft Developer Post');
    }

    /** A category nothing uses returns nothing rather than everything. */
    public function test_an_unknown_category_matches_nothing(): void
    {
        $this->seedThree();

        $this->get(route('frontend.blog', ['category' => 'Astrophysics']))
            ->assertOk()
            ->assertDontSee('Landing Your First Developer Role')
            ->assertSee('Nothing matched that filter');
    }

    /* =============================== SEARCH ================================ */

    public function test_the_search_box_filters_too(): void
    {
        $this->seedThree();

        $this->get(route('frontend.blog', ['q' => 'Resume']))
            ->assertOk()
            ->assertSee('Resume Mistakes to Avoid')
            ->assertDontSee('Designing a Portfolio');
    }

    public function test_a_search_and_a_category_work_together(): void
    {
        $this->makePost('Resume Mistakes to Avoid', 'Career Advice');
        $this->makePost('Resume Tips for Designers', 'Design');

        $this->get(route('frontend.blog', ['category' => 'Design', 'q' => 'Resume']))
            ->assertOk()
            ->assertSee('Resume Tips for Designers')
            ->assertDontSee('Resume Mistakes to Avoid');
    }

    /** A % in the box is the character, not "match everything". */
    public function test_a_wildcard_typed_into_the_search_is_taken_literally(): void
    {
        $this->seedThree();

        $this->get(route('frontend.blog', ['q' => '%']))
            ->assertOk()
            ->assertDontSee('Landing Your First Developer Role')
            ->assertSee('Nothing matched that filter');
    }

    /* ============================== THE MENU =============================== */

    /**
     * The menu is built from the posts, so a category typed in the panel shows
     * up and one nothing uses never does.
     */
    public function test_the_menu_lists_the_categories_the_posts_actually_use(): void
    {
        $this->makePost('A Post', 'Placement Support');
        $this->makePost('Another', null);

        $page = $this->get(route('frontend.blog'))->assertOk()->getContent();

        $this->assertStringContainsString('Placement Support', $page);
        $this->assertStringContainsString('All Categories', $page);
        // The old hard-coded list is gone.
        $this->assertStringNotContainsString('Interview Tips', $page);
    }

    /** Every item is a link that carries the filter, not an inert button. */
    public function test_each_category_is_a_link(): void
    {
        $this->seedThree();

        $page = $this->get(route('frontend.blog'))->assertOk()->getContent();

        $this->assertStringContainsString('category=Development', $page);
        $this->assertMatchesRegularExpression(
            '#<a[^>]+href="[^"]*category=Development[^"]*"#',
            $page,
            'the category must be a link, not a button',
        );
    }

    /** Searching keeps the category, and picking a category keeps the search. */
    public function test_the_two_filters_carry_each_other(): void
    {
        $this->seedThree();

        $page = $this->get(route('frontend.blog', ['category' => 'Design', 'q' => 'port']))
            ->assertOk()->getContent();

        // The form posts the category back alongside a new search term.
        $this->assertStringContainsString('name="category" value="Design"', $page);
        // And the menu links keep the search term.
        $this->assertStringContainsString('q=port', $page);
    }

    /* ============================= PAGINATION ============================== */

    /** Page 2 of a filtered listing must stay filtered. */
    public function test_the_filter_survives_pagination(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $this->makePost("Development Post {$i}", 'Development');
        }
        $this->makePost('An Unrelated Post', 'Design');

        $page = $this->get(route('frontend.blog', ['category' => 'Development']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('category=Development', $page);
        $this->assertMatchesRegularExpression(
            '#href="[^"]*category=Development[^"]*page=2#',
            $page,
            'the page links must keep the category',
        );

        $this->get(route('frontend.blog', ['category' => 'Development', 'page' => 2]))
            ->assertOk()
            ->assertDontSee('An Unrelated Post');
    }
}
