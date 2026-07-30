<?php

namespace App\Http\Middleware;

use App\Support\AdminAuth;
use App\Support\AdminModules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces per-module access across the whole admin panel.
 *
 * The module is derived from the route name, so no route needs its own
 * permission wiring — "backend.blogs.edit" is guarded by module "blogs" purely
 * because it is named that way. Routes outside the registry (dashboard,
 * notification bell) stay open to every signed-in admin.
 *
 * Runs after AdminAuthenticate, which has already dealt with "not signed in".
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $module = AdminModules::forRoute($request->route()?->getName());

        if ($module === null) {
            return $next($request);
        }

        $user = AdminAuth::user();

        // Signed-in flag but no account behind it (deleted mid-session).
        if (! $user) {
            return redirect()->route('backend.auth.login');
        }

        // "My Profile" in the topbar is a users.* route pointed at the signed-in
        // account. Everyone gets to change their own name, email and password, so
        // editing yourself is allowed even without the module. UserController
        // strips access/status fields unless the main admin is the one saving.
        if ($this->isOwnProfile($request, $module, $user->id)) {
            return $next($request);
        }

        if (! $user->canAccessModule($module)) {
            return $this->deny($module);
        }

        return $next($request);
    }

    private function isOwnProfile(Request $request, string $module, int $userId): bool
    {
        if ($module !== 'users' || ! in_array($request->route()->getName(), [
            'backend.users.edit', 'backend.users.update',
        ], true)) {
            return false;
        }

        // Depending on middleware order this is either the bound model or the raw id.
        $target = $request->route('user');

        return (int) (is_object($target) ? $target->id : $target) === $userId;
    }

    /**
     * They are signed in, just not permitted — so keep them inside the panel
     * with an explanation instead of dropping them on a bare 403 page. The
     * message is a toast on the dashboard (backend/partials/feedback).
     */
    private function deny(string $module): Response
    {
        $what = AdminModules::isSuperAdminOnly($module)
            ? 'Only the main admin can manage ' . AdminModules::label($module) . '.'
            : 'You do not have access to ' . AdminModules::label($module) . '. Ask the main admin to enable it.';

        return redirect()->route('backend.dashboard')->with('error', $what);
    }
}
