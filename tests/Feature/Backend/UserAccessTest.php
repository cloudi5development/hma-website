<?php

namespace Tests\Feature\Backend;

use App\Models\User;
use App\Support\AdminModules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    /** The main admin — created and flagged by migration. */
    private function mainAdmin(): User
    {
        return User::where('is_super_admin', true)->firstOrFail();
    }

    /** A normal admin account holding exactly the given modules. */
    private function staff(array $modules = []): User
    {
        return User::create([
            'name'      => 'Staff Member',
            'email'     => 'staff' . User::count() . '@example.com',
            'password'  => 'password123',
            'is_active' => true,
            'modules'   => $modules,
        ]);
    }

    private function signedInAs(User $user): self
    {
        return $this->withSession([
            'admin_logged_in' => true,
            'admin_id'        => $user->id,
            'admin_name'      => $user->name,
            'admin_email'     => $user->email,
        ]);
    }

    public function test_migration_creates_exactly_one_main_admin(): void
    {
        $this->assertSame(1, User::where('is_super_admin', true)->count());
        $this->assertSame('admin@gmail.com', $this->mainAdmin()->email);
    }

    public function test_only_the_main_admin_can_reach_user_management(): void
    {
        $this->signedInAs($this->mainAdmin())->get(route('backend.users.index'))->assertOk();

        // Even with every grantable module ticked, staff cannot manage users.
        $this->signedInAs($this->staff(AdminModules::keys()))
            ->get(route('backend.users.index'))
            ->assertRedirect(route('backend.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_the_add_user_form_offers_every_grantable_module(): void
    {
        $html = $this->signedInAs($this->mainAdmin())
            ->get(route('backend.users.create'))
            ->assertOk()
            ->getContent();

        foreach (AdminModules::GROUPS as $group => $modules) {
            $this->assertStringContainsString($group, $html);

            foreach (array_keys($modules) as $key) {
                $this->assertStringContainsString('value="' . $key . '"', $html);
            }
        }

        // Users is never offered as a tickable module.
        $this->assertStringNotContainsString('value="users"', $html);
    }

    public function test_only_the_main_admin_can_create_users(): void
    {
        $payload = [
            'name'                  => 'Created By Staff',
            'email'                 => 'nope@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'is_active'             => 1,
        ];

        $this->signedInAs($this->staff(AdminModules::keys()))
            ->post(route('backend.users.store'), $payload)
            ->assertRedirect(route('backend.dashboard'));

        $this->assertDatabaseMissing('users', ['email' => 'nope@example.com']);
    }

    public function test_main_admin_creates_a_user_with_the_chosen_modules(): void
    {
        $this->signedInAs($this->mainAdmin())
            ->post(route('backend.users.store'), [
                'name'                  => 'Content Editor',
                'email'                 => 'editor@example.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'is_active'             => 1,
                'modules'               => ['blogs', 'testimonials'],
            ])
            ->assertRedirect(route('backend.users.index'));

        $editor = User::where('email', 'editor@example.com')->firstOrFail();

        // moduleKeys() reports in registry order, not the order they were posted.
        $this->assertEqualsCanonicalizing(['blogs', 'testimonials'], $editor->moduleKeys());
        $this->assertFalse($editor->is_super_admin);
        $this->assertTrue($editor->canAccessModule('blogs'));
        $this->assertFalse($editor->canAccessModule('courses'));
    }

    public function test_a_granted_module_is_reachable_and_others_are_not(): void
    {
        $staff = $this->staff(['blogs']);

        $this->signedInAs($staff)->get(route('backend.blogs.index'))->assertOk();

        $this->signedInAs($staff)
            ->get(route('backend.courses.index'))
            ->assertRedirect(route('backend.dashboard'))
            ->assertSessionHas('error');

        // Write routes of a module they lack are refused too, not just the listing.
        $this->signedInAs($staff)
            ->get(route('backend.settings.general'))
            ->assertRedirect(route('backend.dashboard'));
    }

    public function test_the_dashboard_and_bell_are_never_gated(): void
    {
        // A user with no modules at all still lands somewhere: only routes the
        // registry knows about are gated, and the dashboard is not one of them.
        $this->assertNull(AdminModules::forRoute('backend.dashboard'));
        $this->assertNull(AdminModules::forRoute('backend.notifications.open'));
        $this->assertSame('blogs', AdminModules::forRoute('backend.blogs.edit'));
        $this->assertSame('settings', AdminModules::forRoute('backend.settings.general'));
    }

    public function test_the_sidebar_only_lists_permitted_modules(): void
    {
        // Rendered on a page this user CAN open (the dashboard's chart query uses
        // MySQL-only SQL, so it cannot render on the sqlite test connection).
        $html = $this->signedInAs($this->staff(['blogs']))
            ->get(route('backend.blogs.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('backend.blogs.index'), $html);
        $this->assertStringNotContainsString(route('backend.courses.index'), $html);
        $this->assertStringNotContainsString(route('backend.settings.general'), $html);

        // Matched with the closing quote: "My Profile" links to
        // /admin/users/<id>/edit, which contains the listing URL as a prefix.
        $this->assertStringNotContainsString('href="' . route('backend.users.index') . '"', $html);

        // Headings whose every child is hidden go too.
        $this->assertStringNotContainsString('>Leads<', $html);
    }

    public function test_the_main_admin_sidebar_lists_user_management(): void
    {
        $html = $this->signedInAs($this->mainAdmin())
            ->get(route('backend.users.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('backend.users.index'), $html);
        $this->assertStringContainsString(route('backend.courses.index'), $html);
        $this->assertStringContainsString('Full access', $html);
    }

    public function test_super_admin_only_modules_cannot_be_granted(): void
    {
        $this->signedInAs($this->mainAdmin())
            ->post(route('backend.users.store'), [
                'name'                  => 'Sneaky',
                'email'                 => 'sneaky@example.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'is_active'             => 1,
                'modules'               => ['users'],
            ])
            ->assertSessionHasErrors('modules.0');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_granting_users_directly_in_the_column_still_denies_access(): void
    {
        // Belt and braces: even a hand-edited database row cannot open Users.
        $staff = $this->staff(['users', 'blogs']);

        $this->assertFalse($staff->canAccessModule('users'));

        $this->signedInAs($staff)
            ->get(route('backend.users.index'))
            ->assertRedirect(route('backend.dashboard'));
    }

    public function test_the_main_admin_account_cannot_be_deleted_or_disabled(): void
    {
        $main  = $this->mainAdmin();
        $other = $this->staff(['blogs']);

        // Signed in as another account so the "that's you" guard is not what blocks it.
        $other->forceFill(['is_super_admin' => true])->save();
        $this->signedInAs($other)
            ->delete(route('backend.users.destroy', $main))
            ->assertRedirect(route('backend.users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $main->id]);

        $this->signedInAs($other)
            ->put(route('backend.users.update', $main), [
                'name'      => $main->name,
                'email'     => $main->email,
                'is_active' => 0,
            ])
            ->assertSessionHas('error');

        $this->assertTrue($main->fresh()->is_active);
    }

    public function test_staff_can_open_and_save_their_own_profile(): void
    {
        $staff = $this->staff(['blogs']);

        $this->signedInAs($staff)->get(route('backend.users.edit', $staff))->assertOk();

        $this->signedInAs($staff)
            ->put(route('backend.users.update', $staff), [
                'name'  => 'Renamed Themselves',
                'email' => $staff->email,
            ])
            ->assertRedirect();

        $this->assertSame('Renamed Themselves', $staff->fresh()->name);
    }

    public function test_staff_cannot_open_or_edit_another_account(): void
    {
        $staff = $this->staff(['blogs']);
        $other = $this->staff(['faqs']);

        $this->signedInAs($staff)
            ->get(route('backend.users.edit', $other))
            ->assertRedirect(route('backend.dashboard'));

        $this->signedInAs($staff)
            ->put(route('backend.users.update', $other), [
                'name'  => 'Hijacked',
                'email' => $other->email,
            ])
            ->assertRedirect(route('backend.dashboard'));

        $this->assertNotSame('Hijacked', $other->fresh()->name);
    }

    public function test_staff_cannot_grant_themselves_modules_via_their_profile(): void
    {
        $staff = $this->staff(['blogs']);

        $this->signedInAs($staff)
            ->put(route('backend.users.update', $staff), [
                'name'      => $staff->name,
                'email'     => $staff->email,
                'modules'   => AdminModules::keys(),
                'is_active' => 1,
            ])
            ->assertRedirect();

        // The post went through for name/email, but access is unchanged.
        $this->assertSame(['blogs'], $staff->fresh()->moduleKeys());
    }

    public function test_editing_a_user_can_clear_every_module(): void
    {
        $staff = $this->staff(['blogs', 'faqs']);

        $this->signedInAs($this->mainAdmin())
            ->put(route('backend.users.update', $staff), [
                'name'      => $staff->name,
                'email'     => $staff->email,
                'is_active' => 1,
            ])
            ->assertRedirect(route('backend.users.index'));

        $this->assertSame([], $staff->fresh()->moduleKeys());
    }
}
