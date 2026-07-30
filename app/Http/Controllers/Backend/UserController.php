<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\UserRequest;
use App\Models\User;
use App\Support\AdminAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/**
 * Admin accounts. These are the logins for the panel itself — AuthController
 * checks credentials against this table.
 *
 * Reachable by the main admin only: "users" is listed in
 * AdminModules::SUPER_ADMIN_ONLY, and the admin.module middleware on the route
 * group turns everyone else away. Creating accounts and choosing which modules
 * each one can open is therefore the main admin's alone.
 */
class UserController extends Controller
{
    use HandlesTableQuery;

    public function index(): View
    {
        $users = $this->applyTableFilters(User::query(), ['name', 'email'])
            ->orderBy('name')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('backend.users.form', ['user' => new User(['is_active' => true])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('backend.users.index')
            ->with('success', 'User added. They can sign in with the email and password you set.');
    }

    public function edit(User $user): View
    {
        return view('backend.users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Blank password field means "keep the current one".
        if (blank($data['password'] ?? null)) {
            $data = Arr::except($data, ['password']);
        }

        // Handing out access is the main admin's alone. Anyone else reaching this
        // method is editing their own profile (the middleware allows only that),
        // so their name/email/password save and nothing else — a crafted
        // "modules[]" or "is_active" post cannot promote or re-enable them.
        if (! AdminAuth::isSuperAdmin()) {
            $data = Arr::except($data, ['modules', 'is_active']);
        }

        // The main admin holds every module by definition, so the permission grid
        // is disabled on their form — don't let an empty post wipe the column.
        if ($user->is_super_admin) {
            $data = Arr::except($data, ['modules']);
        }

        // Don't let admins switch off the account they are signed in with, or
        // deactivate the last account that can still log in. Only checked when the
        // save actually carries the flag — a profile save does not, and must not
        // read as "deactivate me".
        if (array_key_exists('is_active', $data) && ! $data['is_active']) {
            if ($this->isCurrentUser($user)) {
                return back()->withInput()->with('error', 'You cannot deactivate the account you are signed in with.');
            }

            if ($user->is_super_admin) {
                return back()->withInput()->with('error', 'The main admin account cannot be disabled.');
            }

            if ($this->isLastActiveUser($user)) {
                return back()->withInput()->with('error', 'At least one active user must remain.');
            }
        }

        $user->update($data);

        // Keep the topbar in step when admins rename their own account.
        if ($this->isCurrentUser($user)) {
            session(['admin_name' => $user->name, 'admin_email' => $user->email]);
        }

        // Permissions may have just changed — drop the memoised record so the
        // sidebar is rebuilt from what is now in the database.
        AdminAuth::forget();

        return redirect()->route('backend.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($this->isCurrentUser($user)) {
            return redirect()->route('backend.users.index')
                ->with('error', 'You cannot delete the account you are signed in with.');
        }

        if ($user->is_super_admin) {
            return redirect()->route('backend.users.index')
                ->with('error', 'The main admin account cannot be deleted.');
        }

        if ($this->isLastActiveUser($user)) {
            return redirect()->route('backend.users.index')
                ->with('error', 'At least one active user must remain.');
        }

        $user->delete();

        return redirect()->route('backend.users.index')->with('success', 'User deleted.');
    }

    private function isCurrentUser(User $user): bool
    {
        return (int) session('admin_id') === $user->id;
    }

    /** True when this is the only account still able to sign in. */
    private function isLastActiveUser(User $user): bool
    {
        return $user->is_active && User::active()->whereKeyNot($user->id)->doesntExist();
    }
}
