<?php

namespace App\Support;

use App\Models\User;

/**
 * The admin who is signed in, and what they are allowed to open.
 *
 * The panel authenticates through a session flag rather than Laravel's auth
 * guard (see Backend\AuthController), so this is the one place that turns
 * session('admin_id') back into a User. The record is memoised because the
 * sidebar asks "can I show this?" a couple of dozen times per render; the cache
 * is dropped at the start of every request by the AdminAuthenticate middleware,
 * so it never outlives the request it was built for.
 */
class AdminAuth
{
    protected static ?User $user = null;

    protected static bool $resolved = false;

    /** The signed-in admin, or null when nobody is. */
    public static function user(): ?User
    {
        if (! static::$resolved) {
            static::$resolved = true;

            $id = session('admin_id');
            static::$user = $id ? User::find($id) : null;
        }

        return static::$user;
    }

    /** True only for the main admin — the account that owns the panel. */
    public static function isSuperAdmin(): bool
    {
        return (bool) static::user()?->is_super_admin;
    }

    /** True when the signed-in admin may open this module. */
    public static function can(string $module): bool
    {
        return (bool) static::user()?->canAccessModule($module);
    }

    /** True when they may open at least one of these modules. */
    public static function canAny(string ...$modules): bool
    {
        foreach ($modules as $module) {
            if (static::can($module)) {
                return true;
            }
        }

        return false;
    }

    /** Drop the memoised record — called per request, and after a user is saved. */
    public static function forget(): void
    {
        static::$user = null;
        static::$resolved = false;
    }
}
