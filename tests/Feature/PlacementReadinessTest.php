<?php

namespace Tests\Feature;

use App\Models\PlacementItem;
use App\Models\PlacementSection;
use App\Models\User;
use App\Support\AdminModules;
use Database\Seeders\PlacementSectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Placement Readiness — the page, the panel that writes it, and the wiring
 * between them.
 *
 * The page is built entirely from placement_sections / placement_items, so
 * these tests do what an admin does (edit a block, add a row, reorder, hide)
 * and then read the public page to see it.
 */
class PlacementReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlacementSectionSeeder::class);
    }

    private function signedIn(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ]);
    }

    private function section(string $key): PlacementSection
    {
        return PlacementSection::where('key', $key)->firstOrFail();
    }

    /* ================================ THE SEED ============================== */

    public function test_the_seed_ships_the_whole_page(): void
    {
        $this->assertSame(array_keys(PlacementSection::SECTIONS), PlacementSection::inPageOrder()->pluck('key')->all());

        $this->assertCount(5, $this->section('hero')->itemsIn('point'));
        $this->assertCount(3, $this->section('challenge')->itemsIn('card'));
        $this->assertCount(4, $this->section('framework')->itemsIn('stage'));
        $this->assertCount(4, $this->section('diagnostic')->itemsIn('band'));
        $this->assertCount(3, $this->section('diagnostic')->itemsIn('audience'));
        $this->assertCount(6, $this->section('modules')->itemsIn('module'));
        $this->assertCount(5, $this->section('mock')->itemsIn('step'));
        $this->assertCount(2, $this->section('mock')->itemsIn('outcome'));
        $this->assertCount(5, $this->section('formats')->itemsIn('format'));
        $this->assertCount(6, $this->section('why')->itemsIn('feature'));
    }

    /** Running the seeder again restores the shipped copy rather than duplicating the page. */
    public function test_the_seeder_can_be_run_again(): void
    {
        $this->seed(PlacementSectionSeeder::class);

        $this->assertSame(9, PlacementSection::count());
        $this->assertSame(43, PlacementItem::count());
    }

    /* ============================== THE PAGE ================================ */

    public function test_the_page_renders_every_section_from_the_database(): void
    {
        $page = $this->get(route('frontend.placement-readiness'))->assertOk();

        // Hero
        $page->assertSee('Placement Success Program')
            ->assertSee('Diagnose. Develop. Demonstrate. Deploy.', false)
            ->assertSee('Built for pre-final and final-year students')
            ->assertSee('Individual readiness scorecards');

        // Challenge, framework, diagnostic
        $page->assertSee('Training is completed. But are students ready to clear recruitment?')
            ->assertSee('Different students, different gaps')
            ->assertSee('Four stages from diagnosis to opportunity')
            ->assertSee('Demonstrate')
            ->assertSee('HMA Placement Readiness Diagnostic')
            ->assertSee('Placement Ready')
            ->assertSee('80-100')
            ->assertSee('Company-specific preparation')
            ->assertSee('For the institution');

        // Modules, mock recruitment, formats, why, CTA
        $page->assertSee('Targeted training modules')
            ->assertSee('Resume &amp; LinkedIn Readiness', false)
            ->assertSee('Group discussion')
            ->assertSee('Placement support')
            ->assertSee('A responsible promise:', false)
            ->assertSee('Placement Accelerator')
            ->assertSee('45-60 hours')
            ->assertSee('Recruiter-led curriculum')
            ->assertSee('Begin with one final-year batch.')
            ->assertSee('+91 78240 94044')
            ->assertSee('info@hiremindsacademy.com');
    }

    public function test_the_page_uses_the_sites_own_layout_and_stylesheet(): void
    {
        $html = $this->get(route('frontend.placement-readiness'))->assertOk()->getContent();

        $this->assertStringContainsString('id="hmNavbar"', $html, 'the site header');
        $this->assertStringContainsString('id="hmFooter"', $html, 'the site footer');
        $this->assertStringContainsString('assets/css/frontend/placement-readiness.css', $html);
        $this->assertStringNotContainsString('#0fa7a0', strtolower($html), 'the reference document palette must not be used');
    }

    public function test_the_navbar_links_to_the_page_and_lights_up_on_it(): void
    {
        $this->get(route('frontend.index'))
            ->assertOk()
            ->assertSee(route('frontend.placement-readiness'), false)
            ->assertSee('Placement Readiness');

        $html = $this->get(route('frontend.placement-readiness'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<a href="' . preg_quote(route('frontend.placement-readiness'), '#') . '"[^>]*class="[^"]*active#',
            $html,
        );
    }

    /** A block switched off is left out; so is a row. */
    public function test_hidden_sections_and_rows_are_left_out(): void
    {
        $this->section('why')->update(['is_active' => false]);
        $this->section('modules')->items()->where('title', 'Interview Preparation')->update(['is_active' => false]);

        $this->get(route('frontend.placement-readiness'))
            ->assertOk()
            ->assertDontSee('Recruitment understanding built into the program')
            ->assertDontSee('Interview Preparation')
            ->assertSee('Targeted training modules');
    }

    /* ============================== THE PANEL =============================== */

    public function test_the_module_is_registered_and_guarded(): void
    {
        $this->assertTrue(AdminModules::isGrantable('placement-readiness'));
        $this->assertSame('placement-readiness', AdminModules::forRoute('backend.placement-readiness.edit'));
        $this->assertArrayHasKey('placement-readiness', AdminModules::GROUPS['Website Content']);

        // Signed out.
        $this->get(route('backend.placement-readiness.index'))->assertRedirect(route('backend.auth.login'));

        // Signed in without this module.
        $staff = User::create([
            'name' => 'Courses Only', 'email' => 'courses-only@example.com',
            'password' => bcrypt('secret123'), 'modules' => ['courses'],
        ]);

        $this->withSession(['admin_logged_in' => true, 'admin_id' => $staff->id])
            ->get(route('backend.placement-readiness.index'))
            ->assertRedirect(route('backend.dashboard'));
    }

    public function test_the_overview_lists_every_block_with_its_row_counts(): void
    {
        $this->signedIn()->get(route('backend.placement-readiness.index'))
            ->assertOk()
            ->assertSee('Placement Readiness')
            ->assertSee('Training Modules')
            ->assertSee('6 training modules')
            ->assertSee(route('backend.placement-readiness.edit', 'hero'), false);
    }

    /**
     * The sidebar renders on every panel screen; this reads it off one of this
     * module's own (the dashboard's monthly chart uses MySQL's MONTH(), which
     * the sqlite test driver has no function for — a pre-existing limitation
     * unrelated to this module).
     */
    public function test_the_sidebar_shows_the_module(): void
    {
        $html = $this->signedIn()->get(route('backend.placement-readiness.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#app-nav__link[^>]*is-active[^>]*href="' . preg_quote(route('backend.placement-readiness.index'), '#') . '"#',
            $html,
            'the sidebar entry is present and lit on this module',
        );
        $this->assertStringContainsString('<span class="app-nav__label">Placement Readiness</span>', $html);
    }

    public function test_an_edit_screen_asks_only_for_what_its_block_draws(): void
    {
        // The hero has buttons and a card heading; the challenge has neither.
        $hero = $this->signedIn()->get(route('backend.placement-readiness.edit', 'hero'))->assertOk();
        $hero->assertSee('name="primary_label"', false)
            ->assertSee('name="secondary_url"', false)
            ->assertSee('name="note"', false)
            ->assertSee('name="items[point][0][title]"', false);

        $challenge = $this->signedIn()->get(route('backend.placement-readiness.edit', 'challenge'))->assertOk();
        $challenge->assertDontSee('name="primary_label"', false)
            ->assertDontSee('name="note"', false)
            ->assertSee('name="items[card][0][title]"', false);

        // The diagnostic has two lists, and its band rows carry a score box.
        $diagnostic = $this->signedIn()->get(route('backend.placement-readiness.edit', 'diagnostic'))->assertOk();
        $diagnostic->assertSee('name="items[band][0][subtitle]"', false)
            ->assertSee('name="items[audience][0][title]"', false)
            ->assertSee('Score Bands')
            ->assertSee('id="bandTemplate"', false)
            ->assertSee('items[band][__I__][title]', false);

        $this->signedIn()->get(route('backend.placement-readiness.edit', 'nonsense'))->assertNotFound();
    }

    /** Editing the copy of a block, and what it does to the page. */
    public function test_a_block_can_be_edited_and_the_page_follows(): void
    {
        $this->signedIn()->put(route('backend.placement-readiness.update', 'cta'), [
            'eyebrow'       => 'Start here',
            'title'         => 'Book a readiness diagnostic',
            'lead'          => 'One batch, one morning, one report.',
            'primary_label' => 'Arrange a call',
            'primary_url'   => '/contact-us',
            'phone'         => '+91 90000 11111',
            'email'         => 'placements@example.com',
            'is_active'     => 1,
        ])->assertRedirect(route('backend.placement-readiness.index'))->assertSessionHas('success');

        $this->get(route('frontend.placement-readiness'))
            ->assertOk()
            ->assertSee('Book a readiness diagnostic')
            ->assertSee('Arrange a call')
            ->assertSee('+91 90000 11111')
            ->assertSee('placements@example.com')
            ->assertDontSee('Begin with one final-year batch.');
    }

    /** Add, edit, reorder, hide and delete rows — all in one save, as the screen does it. */
    public function test_rows_can_be_added_reordered_hidden_and_removed(): void
    {
        $modules = $this->section('modules');
        $first   = $modules->itemsIn('module')->first();

        $this->signedIn()->put(route('backend.placement-readiness.update', 'modules'), [
            'title'     => $modules->title,
            'is_active' => 1,
            'items'     => [
                'module' => [
                    // A brand-new module, first in the list.
                    ['title' => 'Campus to Corporate', 'text' => 'The first ninety days.', 'is_active' => 1],
                    // The old first one, renamed and hidden.
                    ['title' => 'Aptitude Readiness', 'text' => $first->text, 'is_active' => 0],
                    // A row an admin added and never filled in: dropped.
                    ['title' => '', 'text' => 'orphan'],
                ],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $rows = $this->section('modules')->fresh('items')->itemsIn('module');

        $this->assertCount(2, $rows, 'the blank row is not stored, and the rest were replaced');
        $this->assertSame(['Campus to Corporate', 'Aptitude Readiness'], $rows->pluck('title')->all());
        $this->assertSame([0, 1], $rows->pluck('display_order')->all());
        $this->assertFalse($rows[1]->is_active);

        $this->get(route('frontend.placement-readiness'))
            ->assertOk()
            ->assertSee('Campus to Corporate')
            ->assertDontSee('Aptitude Readiness');
    }

    /** A list belongs to its block: rows posted for a list the block does not have are ignored. */
    public function test_a_list_another_block_owns_is_not_written(): void
    {
        $this->signedIn()->put(route('backend.placement-readiness.update', 'challenge'), [
            'title' => 'The challenge',
            'is_active' => 1,
            'items' => [
                'card'   => [['title' => 'A real challenge', 'is_active' => 1]],
                'module' => [['title' => 'Not mine', 'is_active' => 1]],
            ],
        ])->assertRedirect();

        $this->assertSame(['A real challenge'], $this->section('challenge')->fresh('items')->itemsIn('card')->pluck('title')->all());
        $this->assertSame(0, PlacementItem::where('title', 'Not mine')->count());
        $this->assertCount(6, $this->section('modules')->fresh('items')->itemsIn('module'), 'the modules block is untouched');
    }

    public function test_the_form_is_validated(): void
    {
        $this->signedIn()
            ->from(route('backend.placement-readiness.edit', 'hero'))
            ->put(route('backend.placement-readiness.update', 'hero'), ['title' => '', 'is_active' => 1])
            ->assertSessionHasErrors('title');

        $this->signedIn()
            ->put(route('backend.placement-readiness.update', 'hero'), [
                'title' => 'Fine', 'primary_url' => 'javascript:alert(1)', 'is_active' => 1,
            ])
            ->assertSessionHasErrors('primary_url');

        $this->signedIn()
            ->put(route('backend.placement-readiness.update', 'cta'), [
                'title' => 'Fine', 'email' => 'not-an-email', 'is_active' => 1,
            ])
            ->assertSessionHasErrors('email');

        // Nothing was written by any of those.
        $this->assertSame('Placement Success Program', $this->section('hero')->title);
    }

    public function test_a_block_can_be_switched_off_from_its_own_screen(): void
    {
        $this->signedIn()->put(route('backend.placement-readiness.update', 'formats'), [
            'title' => 'Choose the depth that matches your placement calendar',
        ])->assertRedirect();

        $this->assertFalse($this->section('formats')->is_active, 'an unticked switch posts nothing');

        $this->get(route('frontend.placement-readiness'))->assertOk()->assertDontSee('Placement Accelerator');
    }
}
