<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Services\RecaptchaVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * reCAPTCHA **v3** on the contact form: the score, the action and what happens
 * when Google cannot be reached. The site runs v2 by default, so these set the
 * version explicitly; RecaptchaEveryFormTest covers the v2 checkbox.
 *
 * Google is faked throughout: these tests are about what this site does with
 * each verdict, not about Google's scoring.
 */
class ContactRecaptchaTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY = 'https://www.google.com/recaptcha/api/siteverify';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->withKeys();
    }

    private function withKeys(): void
    {
        config()->set('services.recaptcha.version', 'v3');
        config()->set('services.recaptcha.site_key', 'site-key-for-tests');
        config()->set('services.recaptcha.secret_key', 'secret-key-for-tests');
        config()->set('services.recaptcha.min_score', 0.5);
    }

    private function withoutKeys(): void
    {
        config()->set('services.recaptcha.site_key', null);
        config()->set('services.recaptcha.secret_key', null);
    }

    /** What Google would answer. */
    private function googleSays(array $body): void
    {
        Http::fake([self::VERIFY => Http::response($body)]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'                 => 'Asha Kumar',
            'email'                => 'asha@example.com',
            'phone'                => '9876543210',
            'looking_for'          => 'Course Information',
            'message'              => 'Please call me back.',
            'g-recaptcha-response' => 'a-token-from-the-page',
        ], $overrides);
    }

    /* ============================== ACCEPTED =============================== */

    public function test_a_human_score_is_accepted_and_stored(): void
    {
        $this->googleSays(['success' => true, 'score' => 0.9, 'action' => 'contact_enquiry']);

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, ContactEnquiry::count());
        $this->assertSame('Asha Kumar', ContactEnquiry::first()->name);
    }

    /** The token is proof, not data — it must not be written to the record. */
    public function test_the_token_is_not_stored_on_the_enquiry(): void
    {
        $this->googleSays(['success' => true, 'score' => 0.9, 'action' => 'contact_enquiry']);

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload());

        $stored = ContactEnquiry::first()->getAttributes();

        $this->assertArrayNotHasKey('g-recaptcha-response', $stored);
        foreach ($stored as $value) {
            $this->assertNotSame('a-token-from-the-page', $value);
        }
    }

    /** The secret goes to Google and nowhere else. */
    public function test_the_secret_is_sent_to_google_not_to_the_page(): void
    {
        $this->googleSays(['success' => true, 'score' => 0.9, 'action' => 'contact_enquiry']);

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload());

        Http::assertSent(function ($request) {
            return $request->url() === self::VERIFY
                && $request['secret'] === 'secret-key-for-tests'
                && $request['response'] === 'a-token-from-the-page';
        });

        $page = $this->get(route('frontend.contact-us'))->assertOk()->getContent();
        $this->assertStringNotContainsString('secret-key-for-tests', $page);
        $this->assertStringContainsString('site-key-for-tests', $page);
    }

    /* ============================== REFUSED ================================ */

    public function test_a_bot_score_is_refused_and_nothing_is_stored(): void
    {
        $this->googleSays(['success' => true, 'score' => 0.1, 'action' => 'contact_enquiry']);

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('g-recaptcha-response');

        $this->assertSame(0, ContactEnquiry::count());
        Mail::assertNothingSent();
    }

    public function test_a_token_google_rejects_is_refused(): void
    {
        $this->googleSays(['success' => false, 'error-codes' => ['invalid-input-response']]);

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload())
            ->assertStatus(422);

        $this->assertSame(0, ContactEnquiry::count());
    }

    /** A token minted for another page must not be replayed against this form. */
    public function test_a_token_for_a_different_action_is_refused(): void
    {
        $this->googleSays(['success' => true, 'score' => 0.9, 'action' => 'login']);

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload())
            ->assertStatus(422);

        $this->assertSame(0, ContactEnquiry::count());
    }

    public function test_a_submission_with_no_token_at_all_is_refused(): void
    {
        Http::fake();

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload(['g-recaptcha-response' => null]))
            ->assertStatus(422);

        $this->assertSame(0, ContactEnquiry::count());
        Http::assertNothingSent();   // no point asking Google about nothing
    }

    /** The refusal says something a person can act on. */
    public function test_the_refusal_is_explained_in_words(): void
    {
        $this->googleSays(['success' => true, 'score' => 0.1, 'action' => 'contact_enquiry']);

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload())
            ->assertStatus(422)
            ->assertJsonFragment(['g-recaptcha-response' => [
                'We could not verify that you are a person. Please reload the page and try again.',
            ]]);
    }

    /* ========================= WHEN GOOGLE IS DOWN ========================== */

    /**
     * An enquiry is worth more than a perfect check: if Google cannot be
     * reached the submission goes through rather than being lost.
     */
    public function test_a_submission_survives_google_being_unreachable(): void
    {
        Http::fake([self::VERIFY => fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out')]);

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, ContactEnquiry::count());
    }

    public function test_a_submission_survives_google_returning_an_error(): void
    {
        Http::fake([self::VERIFY => Http::response('gateway error', 502)]);

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload())->assertOk();

        $this->assertSame(1, ContactEnquiry::count());
    }

    /* =========================== NOT CONFIGURED ============================= */

    /** A clone with no keys behaves exactly as the site did before. */
    public function test_without_keys_nothing_changes(): void
    {
        $this->withoutKeys();
        Http::fake();

        $this->postJson(route('frontend.contact-enquiry.store'), $this->payload(['g-recaptcha-response' => null]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, ContactEnquiry::count());
        Http::assertNothingSent();

        $page = $this->get(route('frontend.contact-us'))->assertOk()->getContent();
        $this->assertStringNotContainsString('recaptcha/api.js', $page);
        $this->assertStringNotContainsString('g-recaptcha-response', $page);
    }

    public function test_the_verifier_is_off_until_both_keys_are_set(): void
    {
        $verifier = app(RecaptchaVerifier::class);

        $this->assertTrue($verifier->enabled());

        config()->set('services.recaptcha.secret_key', null);
        $this->assertFalse($verifier->enabled(), 'a site key alone must not switch it on');

        $this->withoutKeys();
        $this->assertFalse($verifier->enabled());
        $this->assertTrue($verifier->passes(null, 'contact_enquiry'), 'unconfigured must not refuse anybody');
    }

    /* ============================== THE PAGE ================================ */

    public function test_the_page_carries_the_widget_and_the_attribution(): void
    {
        $page = $this->get(route('frontend.contact-us'))->assertOk()->getContent();

        $this->assertStringContainsString('recaptcha/api.js?render=site-key-for-tests', $page);
        $this->assertStringContainsString('name="g-recaptcha-response"', $page);
        $this->assertStringContainsString('This site is protected by reCAPTCHA', $page);
        $this->assertStringContainsString('policies.google.com/privacy', $page);
    }

    /** Google's script has to be allowed through the site's own CSP. */
    public function test_the_policy_allows_googles_script(): void
    {
        $header = $this->get(route('frontend.contact-us'))->headers->get('Content-Security-Policy');

        $this->assertNotNull($header, 'no CSP header to check');

        preg_match('/script-src ([^;]+)/', $header, $script);
        $this->assertStringContainsString('https://www.google.com', $script[1]);
        $this->assertStringContainsString('https://www.gstatic.com', $script[1]);

        preg_match('/frame-src ([^;]+)/', $header, $frame);
        $this->assertStringContainsString('https://www.google.com', $frame[1]);
    }
}
