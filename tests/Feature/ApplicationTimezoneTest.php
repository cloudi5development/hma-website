<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The app runs on the clock the .env names, and the panel prints that clock.
 *
 * `config/app.php` used to hardcode 'UTC' while the .env had named Asia/Kolkata
 * for months, so nothing read it: every timestamp was stored and shown in UTC,
 * and an enquiry that arrived at 1:02 pm was listed as 7:32 am. The bug was one
 * word in a config file and invisible until someone compared a row against their
 * watch, so it is pinned here.
 */
class ApplicationTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_timezone_is_read_from_the_environment_not_hardcoded(): void
    {
        $this->assertSame(
            env('APP_TIMEZONE', 'UTC'),
            config('app.timezone'),
            'config/app.php is ignoring APP_TIMEZONE — the app is running on the wrong clock.',
        );
    }

    public function test_php_itself_is_running_on_the_configured_clock(): void
    {
        $this->assertSame(config('app.timezone'), date_default_timezone_get());
    }

    /**
     * The one that would have caught it: what gets stored is the local wall
     * clock, not a UTC translation of it.
     */
    public function test_an_enquiry_records_the_local_wall_clock_time(): void
    {
        $enquiry = ContactEnquiry::create([
            'name'    => 'Priya',
            'email'   => 'priya@example.com',
            'phone'   => '9876543210',
            'message' => 'Please call me back.',
        ]);

        $wallClock = new DateTime('now', new DateTimeZone(config('app.timezone')));

        $this->assertLessThanOrEqual(
            60,
            abs($enquiry->created_at->getTimestamp() - $wallClock->getTimestamp()),
            'A new enquiry was stamped with a time that is not the local clock.',
        );

        // ...and the admin listing prints exactly that, with no conversion of
        // its own that could put the drift back.
        $admin = User::where('is_super_admin', true)->firstOrFail();

        $this->withSession([
            'admin_logged_in' => true,
            'admin_id'        => $admin->id,
            'admin_name'      => $admin->name,
            'admin_email'     => $admin->email,
        ])
            ->get(route('backend.contact-enquiries.index'))
            ->assertOk()
            ->assertSee($wallClock->format('g:i a'));
    }
}
