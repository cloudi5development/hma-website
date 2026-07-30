<?php

namespace Tests\Feature\Backend;

use App\Models\User;
use App\Support\AdminRemember;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attributes = []): User
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        // 'password' is a hashed cast, so the plain value is hashed on save.
        $admin->forceFill(array_merge(['password' => 'password123'], $attributes))->save();

        return $admin;
    }

    /**
     * The queued remember cookie on a response, if any.
     *
     * Matched with getName() rather than firstWhere('name', …): Symfony's Cookie
     * keeps $name private, so data_get() cannot see it and firstWhere silently
     * returns null for every cookie.
     */
    private function rememberCookie($response)
    {
        return collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === AdminRemember::COOKIE);
    }

    /**
     * The plaintext behind an encrypted response cookie.
     *
     * Note the asymmetry: RESPONSE cookies are encrypted, so they have to be
     * decrypted to be inspected — but cookies handed to withCookie() must be
     * PLAINTEXT, because the test client encrypts and name-prefixes them itself.
     * Passing an already-encrypted value there encrypts it twice and the
     * middleware receives gibberish.
     */
    private function readCookie($cookie): string
    {
        return CookieValuePrefix::remove(decrypt($cookie->getValue(), false));
    }

    /** A cookie is "cleared" by an expiry in the past, not by an empty value. */
    private function assertCookieCleared($cookie): void
    {
        $this->assertNotNull($cookie);
        $this->assertLessThan(time(), $cookie->getExpiresTime());
    }

    /* ============================== REMEMBER ME ============================== */

    public function test_logging_in_without_remember_sets_no_cookie(): void
    {
        $admin = $this->admin();

        $response = $this->post(route('backend.auth.authenticate'), [
            'username' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('backend.dashboard'));

        // Either no cookie at all, or one explicitly expired — never a live token.
        $cookie = $this->rememberCookie($response);

        if ($cookie !== null) {
            $this->assertCookieCleared($cookie);
        }

        $this->assertNull($admin->fresh()->remember_token);
    }

    public function test_logging_in_with_remember_issues_a_cookie_and_stores_only_a_hash(): void
    {
        $admin = $this->admin();

        $response = $this->post(route('backend.auth.authenticate'), [
            'username' => $admin->email,
            'password' => 'password123',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('backend.dashboard'));

        $cookie = $this->rememberCookie($response);

        $this->assertNotNull($cookie, 'the remember cookie was not queued');

        [$id, $token] = explode('|', $this->readCookie($cookie), 2);

        $this->assertSame($admin->id, (int) $id);
        $this->assertNotEmpty($token);

        // The database keeps only the hash, so a leaked row cannot be replayed.
        $stored = $admin->fresh()->remember_token;
        $this->assertNotSame($token, $stored);
        $this->assertSame(hash('sha256', $token), $stored);
    }

    public function test_a_valid_remember_cookie_reopens_the_session(): void
    {
        $admin = $this->admin();

        // Log in for real, then reuse the exact cookie the app issued — a round
        // trip through the framework's own encryption rather than a forgery.
        $login = $this->post(route('backend.auth.authenticate'), [
            'username' => $admin->email,
            'password' => 'password123',
            'remember' => '1',
        ]);

        $cookie = $this->rememberCookie($login);
        $this->assertNotNull($cookie);

        // Throw the session away: only the cookie is left, as after a browser restart.
        $this->flushSession();

        $this->withCookie(AdminRemember::COOKIE, $this->readCookie($cookie))
            ->get(route('backend.users.index'))
            ->assertOk();

        $this->assertTrue(session('admin_logged_in'));
        $this->assertSame($admin->id, session('admin_id'));
    }

    public function test_a_tampered_or_unknown_remember_cookie_is_refused(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['remember_token' => hash('sha256', Str::random(60))])->save();

        foreach ([
            $admin->id . '|wrong-token',
            '999999|whatever',
            'not-even-close',
        ] as $value) {
            $this->flushSession();
            $this->withCookie(AdminRemember::COOKIE, $value)
                ->get(route('backend.dashboard'))
                ->assertRedirect(route('backend.auth.login'));
        }
    }

    public function test_a_remember_cookie_for_a_deactivated_account_is_refused(): void
    {
        // A second account, so the main admin is not the one being disabled.
        $staff = User::create([
            'name' => 'Staff', 'email' => 'staff@example.com',
            'password' => 'password123', 'is_active' => false, 'modules' => ['blogs'],
        ]);

        $token = Str::random(60);
        $staff->forceFill(['remember_token' => hash('sha256', $token)])->save();

        $this->withCookie(AdminRemember::COOKIE, $staff->id . '|' . $token)
            ->get(route('backend.dashboard'))
            ->assertRedirect(route('backend.auth.login'));
    }

    public function test_logging_out_clears_the_cookie_and_the_stored_token(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['remember_token' => hash('sha256', Str::random(60))])->save();

        $response = $this->withSession(['admin_logged_in' => true, 'admin_id' => $admin->id])
            ->post(route('backend.auth.logout'));

        $response->assertRedirect(route('backend.auth.login'));

        $cookie = $this->rememberCookie($response);

        $this->assertCookieCleared($cookie);
        $this->assertNull($admin->fresh()->remember_token);
    }

    /* ============================ FORGOT PASSWORD ============================ */

    public function test_the_forgot_password_page_loads_and_the_login_links_to_it(): void
    {
        $this->get(route('backend.auth.login'))
            ->assertOk()
            ->assertSee(route('backend.auth.password.request'), false)
            ->assertSee('Remember me')
            ->assertSee('Forgot Password?');

        $this->get(route('backend.auth.password.request'))
            ->assertOk()
            ->assertSee('Forgot your password?');
    }

    public function test_a_reset_link_is_emailed_and_points_at_the_admin_panel(): void
    {
        Notification::fake();

        $admin = $this->admin();

        $this->post(route('backend.auth.password.email'), ['email' => $admin->email])
            ->assertRedirect()
            ->assertSessionHas('status_message');

        Notification::assertSentTo($admin, ResetPassword::class, function (ResetPassword $notification) use ($admin) {
            $url = $notification->toMail($admin)->actionUrl;

            // Must land on the admin reset screen, not a non-existent frontend one.
            return str_contains($url, '/admin/reset-password/')
                && str_contains($url, urlencode($admin->email));
        });
    }

    public function test_an_unknown_address_gets_the_same_answer_as_a_known_one(): void
    {
        Notification::fake();

        // Otherwise the form would let anyone enumerate admin accounts.
        $this->post(route('backend.auth.password.email'), ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status_message')
            ->assertSessionMissing('login_error');

        Notification::assertNothingSent();
    }

    public function test_a_deactivated_account_cannot_request_a_reset(): void
    {
        Notification::fake();

        $staff = User::create([
            'name' => 'Staff', 'email' => 'staff@example.com',
            'password' => 'password123', 'is_active' => false,
        ]);

        $this->post(route('backend.auth.password.email'), ['email' => $staff->email])
            ->assertSessionHas('status_message');

        Notification::assertNothingSent();
    }

    public function test_the_password_can_be_reset_with_a_valid_token(): void
    {
        $admin = $this->admin();
        $token = app('auth.password.broker')->createToken($admin);

        $this->get(route('backend.auth.password.reset', ['token' => $token, 'email' => $admin->email]))
            ->assertOk()
            ->assertSee('Set a new password');

        $this->post(route('backend.auth.password.update'), [
            'token'                 => $token,
            'email'                 => $admin->email,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('backend.auth.login'));

        $this->assertTrue(Hash::check('brand-new-pass', $admin->fresh()->password));

        // And the new password actually signs in.
        $this->post(route('backend.auth.authenticate'), [
            'username' => $admin->email,
            'password' => 'brand-new-pass',
        ])->assertRedirect(route('backend.dashboard'));
    }

    public function test_a_stale_token_is_rejected(): void
    {
        $admin = $this->admin();

        $this->post(route('backend.auth.password.update'), [
            'token'                 => 'not-a-real-token',
            'email'                 => $admin->email,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHas('login_error');

        $this->assertTrue(Hash::check('password123', $admin->fresh()->password));
    }

    public function test_a_mismatched_confirmation_is_rejected(): void
    {
        $admin = $this->admin();
        $token = app('auth.password.broker')->createToken($admin);

        $this->post(route('backend.auth.password.update'), [
            'token'                 => $token,
            'email'                 => $admin->email,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'something-else',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password123', $admin->fresh()->password));
    }

    public function test_resetting_invalidates_any_existing_remember_cookie(): void
    {
        $admin = $this->admin();
        $token = Str::random(60);
        $admin->forceFill(['remember_token' => hash('sha256', $token)])->save();

        $reset = app('auth.password.broker')->createToken($admin);

        $this->post(route('backend.auth.password.update'), [
            'token'                 => $reset,
            'email'                 => $admin->email,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('backend.auth.login'));

        // The old cookie must no longer open a session.
        $this->withCookie(AdminRemember::COOKIE, $admin->id . '|' . $token)
            ->get(route('backend.dashboard'))
            ->assertRedirect(route('backend.auth.login'));
    }
}
