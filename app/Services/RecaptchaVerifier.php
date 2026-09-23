<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Checks a reCAPTCHA response with Google.
 *
 * Both kinds are supported, chosen by services.recaptcha.version:
 *
 *   v2  the visitor ticks "I'm not a robot" and the widget puts a response in
 *       the form. Google answers success/failure, with no score and no action.
 *   v3  invisible: the page fetches a token as the form is sent and Google
 *       scores it, 0.0 (bot) to 1.0 (person).
 *
 * A submission is refused only when Google actually says so.
 */
class RecaptchaVerifier
{
    private const ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    /** Configured, and therefore switched on, only when both keys are present. */
    public function enabled(): bool
    {
        return filled($this->siteKey()) && filled($this->secretKey());
    }

    /**
     * The admin's own keys win.
     *
     * Settings -> reCAPTCHA is where they are typed, so a key can be changed
     * without touching the server; .env is the fallback, which keeps an
     * install that was configured that way working, and gives the test suite
     * something to set. Empty in the panel means "fall back", not "off".
     */
    public function siteKey(): ?string
    {
        return $this->fromSettings('recaptcha_site_key')
            ?? config('services.recaptcha.site_key');
    }

    public function secretKey(): ?string
    {
        return $this->fromSettings('recaptcha_secret_key')
            ?? config('services.recaptcha.secret_key');
    }

    /** A stored setting, or null when it is missing, blank or unreadable. */
    private function fromSettings(string $key): ?string
    {
        try {
            $value = trim((string) Setting::get($key));
        } catch (Throwable $e) {
            return null;   // no database yet (a fresh install, an early boot)
        }

        return $value !== '' ? $value : null;
    }

    /** 'v2' (the checkbox) or 'v3' (invisible). */
    public function version(): string
    {
        return config('services.recaptcha.version') === 'v3' ? 'v3' : 'v2';
    }

    public function isCheckbox(): bool
    {
        return $this->version() === 'v2';
    }

    /**
     * Is this token good enough to accept the submission?
     *
     * @param  string|null  $token   The g-recaptcha-response the page sent.
     * @param  string       $action  v3 only: the action the page asked for, which
     *                               Google echoes back — it must match, or a token
     *                               lifted from another page would pass here.
     * @param  string|null  $ip      The visitor's address, which sharpens the score.
     */
    public function passes(?string $token, string $action, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;       // nothing configured: behave as before
        }

        if (blank($token)) {
            return false;      // the page sent nothing at all
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('services.recaptcha.timeout', 5))
                ->post(self::ENDPOINT, array_filter([
                    'secret'   => $this->secretKey(),
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (Throwable $e) {
            // Google unreachable. Losing a real enquiry is the worse outcome of
            // the two, so the submission is let through and the failure logged.
            Log::warning('reCAPTCHA could not be reached; submission allowed', ['error' => $e->getMessage()]);

            return true;
        }

        if ($response->failed()) {
            Log::warning('reCAPTCHA returned an error; submission allowed', ['status' => $response->status()]);

            return true;
        }

        $body = $response->json();

        if (! is_array($body) || ! ($body['success'] ?? false)) {
            Log::info('reCAPTCHA refused a submission', ['errors' => $body['error-codes'] ?? null]);

            return false;
        }

        // The rest is v3's: a v2 answer is a ticked box and carries neither an
        // action nor a score, so there is nothing further to check.
        if ($this->isCheckbox()) {
            return true;
        }

        // A token is issued for one action. Accepting any action would let a
        // token minted on some other page be replayed against this form.
        if (isset($body['action']) && $body['action'] !== $action) {
            Log::info('reCAPTCHA action did not match', ['expected' => $action, 'got' => $body['action']]);

            return false;
        }

        $score = (float) ($body['score'] ?? 0);

        if ($score < (float) config('services.recaptcha.min_score', 0.5)) {
            Log::info('reCAPTCHA score below the threshold', ['score' => $score]);

            return false;
        }

        return true;
    }
}
