<?php

namespace Tests\Feature;

use App\Models\ContentPage;
use App\Models\User;
use App\Support\AdminModules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Content Management — the Terms & Conditions and Privacy Policy pages, from the
 * admin form through to the public page and the footer link.
 */
class ContentPageTest extends TestCase
{
    use RefreshDatabase;

    private function signedInAdmin(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession([
            'admin_logged_in' => true,
            'admin_id'        => $admin->id,
            'admin_name'      => $admin->name,
            'admin_email'     => $admin->email,
        ]);
    }

    private function page(string $key): ContentPage
    {
        return ContentPage::where('key', $key)->firstOrFail();
    }

    /* ============================== THE ROWS ============================== */

    public function test_the_migration_creates_both_pages_ready_to_serve(): void
    {
        $this->assertSame(2, ContentPage::count());

        foreach (array_keys(ContentPage::PAGES) as $key) {
            $page = $this->page($key);
            $this->assertTrue($page->is_active);
            $this->assertNotEmpty($page->content, "The {$key} page was seeded empty.");
        }

        $this->assertSame('Terms & Conditions', $this->page('terms-conditions')->title);
        $this->assertSame('Privacy Policy', $this->page('privacy-policy')->title);
    }

    /* ============================== FRONTEND ============================== */

    public function test_both_pages_are_public(): void
    {
        $this->get(route('frontend.terms-conditions'))
            ->assertOk()
            ->assertSee('Terms &amp; Conditions', false);

        $this->get(route('frontend.privacy-policy'))
            ->assertOk()
            ->assertSee('Privacy Policy');
    }

    public function test_the_page_renders_the_admins_html(): void
    {
        $this->page('privacy-policy')->update([
            'content' => '<h2>What we collect</h2><p>Only what you <strong>give us</strong>.</p><ul><li>Your name</li></ul>',
        ]);

        $this->get(route('frontend.privacy-policy'))
            ->assertOk()
            // Rendered as markup, not escaped into visible tags. The heading
            // gains an id on the way through so a clause can be linked to.
            ->assertSee('<h2 id="what-we-collect">What we collect</h2>', false)
            ->assertSee('<strong>give us</strong>', false)
            ->assertSee('<li>Your name</li>', false);
    }

    public function test_an_unpublished_page_is_a_404(): void
    {
        $this->page('terms-conditions')->update(['is_active' => false]);

        $this->get(route('frontend.terms-conditions'))->assertNotFound();
        // The other one is untouched.
        $this->get(route('frontend.privacy-policy'))->assertOk();
    }

    public function test_the_title_is_escaped_once_in_the_browser_tab(): void
    {
        // "Terms & Conditions" is exactly the case the inline @section form
        // double-escapes into "Terms &amp;amp; Conditions".
        $html = $this->get(route('frontend.terms-conditions'))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Terms &amp; Conditions', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    public function test_the_seo_fields_win_when_filled_in(): void
    {
        $this->page('privacy-policy')->update([
            'seo_title'       => 'How we handle your data',
            'seo_description' => 'Our full privacy commitments.',
            'seo_keywords'    => 'privacy, data',
        ]);

        $this->get(route('frontend.privacy-policy'))
            ->assertOk()
            ->assertSee('<title>How we handle your data', false)
            ->assertSee('Our full privacy commitments.')
            ->assertSee('privacy, data');
    }

    /* =============================== FOOTER =============================== */

    public function test_the_footer_links_both_pages(): void
    {
        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertStringContainsString(route('frontend.privacy-policy'), $html);
        $this->assertStringContainsString(route('frontend.terms-conditions'), $html);
    }

    public function test_an_unpublished_page_loses_its_footer_link(): void
    {
        $this->page('terms-conditions')->update(['is_active' => false]);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        // A live link to a page that 404s is worse than no link.
        $this->assertStringNotContainsString(route('frontend.terms-conditions'), $html);
        $this->assertStringContainsString(route('frontend.privacy-policy'), $html);
        // One page left means no separator between them.
        $this->assertStringNotContainsString('hm-footer__legal-sep', $html);
    }

    public function test_the_registration_modal_links_the_terms_page(): void
    {
        $event = \App\Models\Event::create([
            'speaker' => 'R', 'title' => 'An Event', 'type' => 'Live Event', 'tone' => 'purple',
            'image' => 'x.webp', 'is_active' => true, 'show_home' => true,
        ]);

        $this->get(route('frontend.event-details', $event->slug))
            ->assertOk()
            ->assertSee(route('frontend.terms-conditions'), false);
    }

    /* ================================ ADMIN ================================ */

    public function test_the_admin_can_open_and_save_a_page(): void
    {
        $this->signedInAdmin()
            ->get(route('backend.content-pages.edit', 'privacy-policy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('name="content"', false)
            // Both pages are reachable from either form.
            ->assertSee(route('backend.content-pages.edit', 'terms-conditions'), false);

        $this->signedInAdmin()
            ->put(route('backend.content-pages.update', 'privacy-policy'), [
                'title'           => 'Our Privacy Policy',
                'content'         => '<p>Rewritten.</p>',
                'seo_description' => 'A short summary.',
                'is_active'       => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $page = $this->page('privacy-policy');

        $this->assertSame('Our Privacy Policy', $page->title);
        $this->assertSame('<p>Rewritten.</p>', $page->content);
        $this->assertTrue($page->is_active);

        // The renamed title reaches the public page and the footer link.
        $this->get(route('frontend.privacy-policy'))->assertOk()->assertSee('Our Privacy Policy');
        $this->get(route('frontend.index'))->assertOk()->assertSee('Our Privacy Policy');
    }

    public function test_unticking_published_switches_the_page_off(): void
    {
        $this->signedInAdmin()
            ->put(route('backend.content-pages.update', 'terms-conditions'), [
                'title'   => 'Terms & Conditions',
                'content' => '<p>Terms.</p>',
                // The checkbox posts nothing when unticked.
            ])
            ->assertRedirect();

        $this->assertFalse($this->page('terms-conditions')->is_active);
        $this->get(route('frontend.terms-conditions'))->assertNotFound();
    }

    public function test_a_title_is_required(): void
    {
        $this->signedInAdmin()
            ->put(route('backend.content-pages.update', 'terms-conditions'), ['title' => '', 'content' => '<p>x</p>'])
            ->assertSessionHasErrors('title');
    }

    public function test_an_unknown_key_is_a_404_in_both_directions(): void
    {
        $this->signedInAdmin()->get(route('backend.content-pages.edit', 'refund-policy'))->assertNotFound();

        // forKey() must not invent a row for a key with no route or menu entry.
        $this->assertNull(ContentPage::forKey('refund-policy'));
        $this->assertSame(2, ContentPage::count());
    }

    public function test_the_module_is_grantable_and_guarded(): void
    {
        $this->assertTrue(AdminModules::isGrantable('content-pages'));
        $this->assertSame('content-pages', AdminModules::forRoute('backend.content-pages.edit'));
        $this->assertArrayHasKey('Content Management', AdminModules::GROUPS);

        $staff = User::create([
            'name' => 'Staff', 'email' => 'staff-cp@example.com', 'password' => 'password123',
            'is_active' => true, 'modules' => ['courses'],
        ]);

        $this->withSession(['admin_logged_in' => true, 'admin_id' => $staff->id])
            ->get(route('backend.content-pages.edit', 'terms-conditions'))
            ->assertRedirect(route('backend.dashboard'));
    }

    public function test_the_sidebar_shows_the_group_for_a_permitted_admin(): void
    {
        $this->signedInAdmin()
            ->get(route('backend.content-pages.edit', 'terms-conditions'))
            ->assertOk()
            ->assertSee('Content Management')
            ->assertSee(route('backend.content-pages.edit', 'privacy-policy'), false);
    }

    public function test_the_content_field_gets_a_rich_text_editor(): void
    {
        $html = $this->signedInAdmin()
            ->get(route('backend.content-pages.edit', 'terms-conditions'))
            ->assertOk()
            ->getContent();

        // jsdelivr, not cdn.tiny.cloud: that one demands an API key and shows a
        // "register this domain" banner over the toolbar.
        $this->assertStringContainsString('cdn.jsdelivr.net/npm/tinymce@6', $html);
        $this->assertStringNotContainsString('cdn.tiny.cloud', $html);

        // Bound to the content field, and only loaded once.
        $this->assertStringContainsString("selector: '#content'", $html);
        $this->assertSame(1, substr_count($html, 'cdn.jsdelivr.net/npm/tinymce'));

        // Still a real textarea underneath, so the field works without the CDN
        // and the form posts the same `content` either way.
        $this->assertStringContainsString('<textarea id="content" name="content"', $html);
    }

    /* ============================== HEADINGS ============================== */

    public function test_headings_carry_ids_so_a_clause_can_be_linked_to(): void
    {
        $this->page('privacy-policy')->update([
            'content' => '<p>Intro.</p><h2>What we collect</h2><p>a</p><h2>How we use it</h2><p>b</p>',
        ]);

        $html = $this->get(route('frontend.privacy-policy'))->assertOk()->getContent();

        $this->assertStringContainsString('<h2 id="what-we-collect"', $html);
        $this->assertStringContainsString('<h2 id="how-we-use-it"', $html);

        // The "On this page" rail was removed — the page is one column.
        $this->assertStringNotContainsString('On this page', $html);
        $this->assertStringNotContainsString('hm-cp__toc', $html);
    }

    public function test_repeated_heading_names_still_get_unique_anchors(): void
    {
        $this->page('privacy-policy')->update([
            'content' => '<h2>Contact</h2><p>a</p><h2>Other</h2><p>b</p><h2>Contact</h2><p>c</p>',
        ]);

        $html = $this->get(route('frontend.privacy-policy'))->assertOk()->getContent();

        $this->assertStringContainsString('<h2 id="contact"', $html);
        $this->assertStringContainsString('<h2 id="contact-2"', $html);
        $this->assertSame(1, substr_count($html, 'id="contact"'));
    }

    public function test_the_banner_no_longer_borrows_the_events_photograph(): void
    {
        $html = $this->get(route('frontend.terms-conditions'))->assertOk()->getContent();

        // A stage full of chairs behind a privacy policy read as an events page.
        $this->assertStringNotContainsString('events/event-listing', $html);
        $this->assertStringContainsString('hm-cp__banner-glow', $html);
        // "Last updated" belongs at the top of a legal page, not buried at the foot.
        $this->assertStringContainsString('hm-cp__stamp', $html);
    }

    public function test_malformed_admin_html_does_not_break_the_page(): void
    {
        // The editor normally closes its own tags, but a paste through the source
        // view can leave anything behind — it must not surface as a warning.
        $this->page('privacy-policy')->update([
            'content' => '<h2>Open<p>Unclosed paragraph<ul><li>stray',
        ]);

        $this->get(route('frontend.privacy-policy'))
            ->assertOk()
            ->assertSee('Unclosed paragraph');
    }
}
