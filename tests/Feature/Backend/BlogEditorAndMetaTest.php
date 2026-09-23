<?php

namespace Tests\Feature\Backend;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The blog editor: a WYSIWYG article body, and the per-post SEO fields
 * (meta title / description / keywords) plus the cover image's alt text.
 */
class BlogEditorAndMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** Signed in as the main admin, who reaches every module. */
    private function admin(): self
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        return $this->withSession([
            'admin_logged_in' => true,
            'admin_id'        => $admin->id,
            'admin_name'      => $admin->name,
            'admin_email'     => $admin->email,
        ]);
    }

    /** @return array<string, mixed> A complete, valid post. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title'      => 'Interview Prep',
            'slug'       => 'interview-prep',
            'excerpt'    => 'A short summary for the cards.',
            'content'    => '<h2>Heading</h2><p>Body text.</p>',
            'image'      => UploadedFile::fake()->image('cover.jpg', 1200, 630),
            'author'     => 'Hireminds Academy Admin',
            'sort_order' => 0,
            'is_active'  => 1,
        ], $overrides);
    }

    private function makePost(array $attributes = []): Blog
    {
        return Blog::create(array_merge([
            'title'   => 'Interview Prep',
            'slug'    => 'interview-prep',
            'excerpt' => 'A short summary for the cards.',
            'content' => '<p>Body text.</p>',
            'image'   => 'assets/images/blog/blog-1.webp',
        ], $attributes));
    }

    /* ============================== THE EDITOR ============================== */

    public function test_the_article_body_is_a_rich_text_editor(): void
    {
        $page = $this->admin()->get(route('backend.blogs.create'))->assertOk()->getContent();

        $this->assertStringContainsString('tinymce.min.js', $page, 'the editor script is not loaded');
        $this->assertStringContainsString("selector: '#content'", $page, 'the editor is not bound to the body field');
        // Still a real textarea underneath, so the field posts without the CDN.
        $this->assertStringContainsString('name="content"', $page);
    }

    public function test_the_editors_html_is_stored_and_rendered(): void
    {
        $this->admin()->post(route('backend.blogs.store'), $this->payload([
            'content' => '<h2>Section</h2><p>Body <strong>in bold</strong>.</p>',
        ]))->assertRedirect(route('backend.blogs.index'));

        $blog = Blog::firstWhere('slug', 'interview-prep');
        $this->assertSame('<h2>Section</h2><p>Body <strong>in bold</strong>.</p>', $blog->content);

        $this->get(route('frontend.blog-details', $blog->slug))
            ->assertOk()
            ->assertSee('<strong>in bold</strong>', false);
    }

    /**
     * Clearing the editor leaves "<p></p>" behind rather than an empty field.
     * That must be refused like an empty box, not saved as a blank article.
     */
    public function test_an_emptied_editor_is_refused(): void
    {
        foreach (['<p></p>', '<p>&nbsp;</p>', '<p><br></p>', '   '] as $empty) {
            $this->admin()
                ->post(route('backend.blogs.store'), $this->payload(['content' => $empty]))
                ->assertSessionHasErrors('content');
        }

        $this->assertSame(0, Blog::count());
    }

    /** A body that is a table and no prose is still an article. */
    public function test_a_body_of_markup_without_prose_is_kept(): void
    {
        $this->admin()->post(route('backend.blogs.store'), $this->payload([
            'content' => '<table><tr><td>Day 1</td></tr></table>',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, Blog::count());
    }

    /* ================================ SEO =================================== */

    public function test_the_form_offers_the_meta_and_alt_fields(): void
    {
        $page = $this->admin()->get(route('backend.blogs.create'))->assertOk()->getContent();

        foreach (['meta_title', 'meta_description', 'meta_keywords', 'image_alt'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $page, "{$field} is missing from the form");
        }
    }

    public function test_the_meta_and_alt_fields_are_saved(): void
    {
        $this->admin()->post(route('backend.blogs.store'), $this->payload([
            'meta_title'       => 'Interview Prep | Hire Minds',
            'meta_description' => 'Everything a fresher needs before the first round.',
            'meta_keywords'    => 'interview, freshers, placement',
            'image_alt'        => 'A student at a mock interview',
        ]))->assertSessionHasNoErrors();

        $blog = Blog::firstWhere('slug', 'interview-prep');

        $this->assertSame('Interview Prep | Hire Minds', $blog->meta_title);
        $this->assertSame('Everything a fresher needs before the first round.', $blog->meta_description);
        $this->assertSame('interview, freshers, placement', $blog->meta_keywords);
        $this->assertSame('A student at a mock interview', $blog->image_alt);
    }

    public function test_the_article_page_uses_the_posts_own_meta(): void
    {
        $blog = $this->makePost([
            'meta_title'       => 'A Title Only For Search',
            'meta_description' => 'A description only for search.',
            'meta_keywords'    => 'one, two, three',
        ]);

        $page = $this->get(route('frontend.blog-details', $blog->slug))->assertOk()->getContent();

        $this->assertStringContainsString('<title>A Title Only For Search</title>', $page);
        $this->assertStringContainsString('content="A description only for search."', $page);
        $this->assertStringContainsString('content="one, two, three"', $page);
    }

    /** Blank meta falls back to what the page showed before the fields existed. */
    public function test_a_post_without_meta_falls_back_to_its_title_and_excerpt(): void
    {
        $blog = $this->makePost(['title' => 'Plain Post', 'excerpt' => 'The excerpt stands in.']);

        $page = $this->get(route('frontend.blog-details', $blog->slug))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Plain Post — Hire Minds Academy</title>', $page);
        $this->assertStringContainsString('content="The excerpt stands in."', $page);
    }

    /**
     * yieldContent() escapes its default argument, so a fallback passed through
     * it and printed with {{ }} used to come out escaped twice - "R&D" reached
     * og:title as "R&amp;amp;D" while <title> was right.
     */
    public function test_an_ampersand_in_the_meta_title_is_escaped_once(): void
    {
        $blog = $this->makePost(['meta_title' => 'R&D Careers']);

        $page = $this->get(route('frontend.blog-details', $blog->slug))->assertOk()->getContent();

        $this->assertStringContainsString('<title>R&amp;D Careers</title>', $page);
        $this->assertStringContainsString('property="og:title" content="R&amp;D Careers"', $page);
        $this->assertStringNotContainsString('&amp;amp;', $page);
    }

    /* ============================== ALT TEXT ================================ */

    public function test_the_alt_text_is_used_wherever_the_cover_appears(): void
    {
        $blog = $this->makePost([
            'image_alt' => 'A student at a mock interview',
            'is_active' => true,
            'show_home' => true,
        ]);

        $this->get(route('frontend.blog-details', $blog->slug))
            ->assertOk()->assertSee('alt="A student at a mock interview"', false);

        $this->get(route('frontend.blog'))
            ->assertOk()->assertSee('alt="A student at a mock interview"', false);

        $this->get(route('frontend.index'))
            ->assertOk()->assertSee('alt="A student at a mock interview"', false);
    }

    /** Left blank, the cover keeps the alt it always had: the post title. */
    public function test_the_alt_text_falls_back_to_the_title(): void
    {
        $blog = $this->makePost(['title' => 'Plain Post', 'image_alt' => null]);

        $this->assertSame('Plain Post', $blog->image_alt_text);

        $this->get(route('frontend.blog-details', $blog->slug))
            ->assertOk()->assertSee('alt="Plain Post"', false);
    }

    /* ============================== VALIDATION ============================== */

    public function test_the_meta_fields_are_bounded(): void
    {
        $this->admin()->post(route('backend.blogs.store'), $this->payload([
            'meta_title'       => str_repeat('a', 181),
            'meta_description' => str_repeat('b', 301),
            'meta_keywords'    => str_repeat('c', 256),
            'image_alt'        => str_repeat('d', 181),
        ]))->assertSessionHasErrors(['meta_title', 'meta_description', 'meta_keywords', 'image_alt']);
    }

    /** Editing a post leaves the meta alone unless the form changes it. */
    public function test_editing_keeps_the_meta_it_was_given(): void
    {
        $blog = $this->makePost(['meta_title' => 'Kept', 'image_alt' => 'Kept alt']);

        $this->admin()->put(route('backend.blogs.update', $blog), $this->payload([
            'title'      => 'Interview Prep',
            'slug'       => $blog->slug,
            'image'      => null,
            'meta_title' => 'Kept',
            'image_alt'  => 'Kept alt',
        ]))->assertSessionHasNoErrors();

        $blog->refresh();
        $this->assertSame('Kept', $blog->meta_title);
        $this->assertSame('Kept alt', $blog->image_alt);
    }
}
