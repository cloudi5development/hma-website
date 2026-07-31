<?php

namespace Tests\Feature\Backend;

use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTabsTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession(['admin_logged_in' => true, 'admin_id' => $admin->id]);
    }

    private function notification(array $attributes = []): AdminNotification
    {
        return AdminNotification::create(array_merge([
            'type'    => 'contact',
            'title'   => 'New Contact Enquiry Received',
            'body'    => 'Someone — General enquiry',
            'url'     => null,
            'is_read' => false,
        ], $attributes));
    }

    /** The markup of one tab panel, so assertions cannot match the other tab. */
    private function panel(string $html, string $which): string
    {
        $open = 'data-notif-panel="' . $which . '"';
        $start = strpos($html, $open);

        $this->assertNotFalse($start, "the {$which} panel is missing");

        $end = strpos($html, '</ul>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_the_bell_shows_an_unread_and_a_read_tab(): void
    {
        $html = $this->asAdmin()->get(route('backend.users.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-notif-tab="unread"', $html);
        $this->assertStringContainsString('data-notif-tab="read"', $html);
        $this->assertStringContainsString('data-notif-panel="unread"', $html);
        $this->assertStringContainsString('data-notif-panel="read"', $html);
    }

    public function test_a_new_notification_starts_in_the_unread_tab(): void
    {
        $this->notification(['title' => 'Fresh Enquiry']);

        $html = $this->asAdmin()->get(route('backend.users.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Fresh Enquiry', $this->panel($html, 'unread'));
        $this->assertStringNotContainsString('Fresh Enquiry', $this->panel($html, 'read'));
    }

    public function test_opening_a_notification_moves_it_to_the_read_tab(): void
    {
        $notification = $this->notification(['title' => 'Clicked Enquiry']);

        $this->asAdmin()
            ->get(route('backend.notifications.open', $notification))
            ->assertRedirect();

        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at, 'read_at should record when it was opened');

        $html = $this->asAdmin()->get(route('backend.users.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Clicked Enquiry', $this->panel($html, 'read'));
        $this->assertStringNotContainsString('Clicked Enquiry', $this->panel($html, 'unread'));
    }

    public function test_reopening_a_read_notification_keeps_its_original_read_time(): void
    {
        $notification = $this->notification();

        $this->asAdmin()->get(route('backend.notifications.open', $notification));
        $firstReadAt = $notification->fresh()->read_at;

        $this->travel(5)->minutes();

        $this->asAdmin()->get(route('backend.notifications.open', $notification));

        $this->assertEquals(
            $firstReadAt->timestamp,
            $notification->fresh()->read_at->timestamp,
            'read_at should stamp the first open, not every visit'
        );
    }

    public function test_mark_all_read_empties_the_unread_tab(): void
    {
        $this->notification(['title' => 'One']);
        $this->notification(['title' => 'Two']);

        $this->asAdmin()->post(route('backend.notifications.read-all'))->assertRedirect();

        $this->assertSame(0, AdminNotification::unread()->count());
        $this->assertSame(2, AdminNotification::read()->whereNotNull('read_at')->count());

        $html = $this->asAdmin()->get(route('backend.users.index'))->assertOk()->getContent();

        $this->assertStringContainsString("You're all caught up.", $this->panel($html, 'unread'));
        $this->assertStringContainsString('One', $this->panel($html, 'read'));
        $this->assertStringContainsString('Two', $this->panel($html, 'read'));
    }

    public function test_the_unread_badge_counts_only_unread(): void
    {
        $this->notification(['title' => 'A']);
        $read = $this->notification(['title' => 'B']);
        $read->markRead();

        $html = $this->asAdmin()->get(route('backend.users.index'))->assertOk()->getContent();

        // The bell badge and the Unread tab count both show 1, not 2.
        $this->assertSame(1, AdminNotification::unread()->count());
        $this->assertStringContainsString('app-notif__badge">1<', $html);
    }

    public function test_each_tab_has_its_own_empty_state(): void
    {
        $html = $this->asAdmin()->get(route('backend.users.index'))->assertOk()->getContent();

        $this->assertStringContainsString("You're all caught up.", $this->panel($html, 'unread'));
        $this->assertStringContainsString('Nothing read yet.', $this->panel($html, 'read'));
    }
}
