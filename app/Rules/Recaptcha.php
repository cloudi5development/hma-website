<?php

namespace App\Rules;

use App\Services\RecaptchaVerifier;
use Illuminate\Contracts\Validation\ImplicitRule;

/**
 * Validation rule for a reCAPTCHA v3 token, so a failed check is reported
 * against the form like any other validation error rather than as a crash.
 *
 * IMPLICIT on purpose. An ordinary rule is skipped when the field is absent,
 * and a bot posting the form without a `g-recaptcha-response` key at all would
 * then sail straight past the check — which is exactly what it would do. An
 * implicit rule runs whether the field was sent or not.
 *
 * That is why this uses the older ImplicitRule interface rather than
 * ValidationRule: the new one has no way to say "run me even when absent".
 *
 * Passes silently when reCAPTCHA is not configured, which keeps a fresh clone
 * and the test suite working without keys.
 */
class Recaptcha implements ImplicitRule
{
    public function __construct(private string $action, private ?string $ip = null)
    {
    }

    public function passes($attribute, $value): bool
    {
        $verifier = app(RecaptchaVerifier::class);

        if (! $verifier->enabled()) {
            return true;
        }

        return $verifier->passes(is_string($value) ? $value : null, $this->action, $this->ip);
    }

    /**
     * Worded for a person who has done nothing wrong: a low score is usually a
     * shared IP or a locked-down browser, not a bot.
     */
    public function message(): string
    {
        return 'We could not verify that you are a person. Please reload the page and try again.';
    }
}
