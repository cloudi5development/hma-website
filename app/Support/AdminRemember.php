<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * "Remember me" for the admin panel.
 *
 * The panel authenticates through a session flag rather than Laravel's auth guard
 * (see Backend\AuthController), so it cannot lean on the guard's own remember
 * cookie. This issues an equivalent one: a long-lived cookie holding the user id
 * and a random token, with only the token's SHA-256 kept in users.remember_token.
 * A stolen database row therefore cannot be replayed as a cookie, and the
 * comparison is timing-safe.
 *
 * Laravel encrypts cookies by default (EncryptCookies), so the value is also
 * tamper-proof in transit.
 */
class AdminRemember
{
    public const COOKIE = 'hm_admin_remember';

    /** Days a "remember me" login stays valid. */
    public const DAYS = 30;

    /** Issue a fresh token for this account and queue the cookie. */
    public static function issue(User $user): void
    {
        $token = Str::random(60);

        $user->forceFill(['remember_token' => hash('sha256', $token)])->save();

        Cookie::queue(Cookie::make(
            self::COOKIE,
            $user->id . '|' . $token,
            self::DAYS * 24 * 60,   // minutes
            null,
            null,
            null,
            true,                    // httpOnly — never readable from JS
            false,
            'lax',
        ));
    }

    /**
     * The account a valid remember cookie belongs to, or null.
     *
     * Returns null for a missing/short cookie, an unknown or deactivated account,
     * or a token that does not match the stored hash.
     */
    public static function resolve(Request $request): ?User
    {
        $value = $request->cookie(self::COOKIE);

        if (! is_string($value) || ! str_contains($value, '|')) {
            return null;
        }

        [$id, $token] = explode('|', $value, 2);

        if (! ctype_digit((string) $id) || $token === '') {
            return null;
        }

        $user = User::find((int) $id);

        if (! $user || ! $user->is_active || blank($user->remember_token)) {
            return null;
        }

        return hash_equals($user->remember_token, hash('sha256', $token)) ? $user : null;
    }

    /**
     * Drop the cookie and rotate the stored token, so signing out on one device
     * invalidates any remember cookie issued for this account elsewhere.
     */
    public static function forget(?User $user = null): void
    {
        $user?->forceFill(['remember_token' => null])->save();

        Cookie::queue(Cookie::forget(self::COOKIE));
    }
}
