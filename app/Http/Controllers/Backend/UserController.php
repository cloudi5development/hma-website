<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/**
 * Admin accounts. These are the logins for the panel itself — AuthController
 * checks credentials against this table.
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

        return redirect()->route('backend.users.index')->with('success', 'User added.');
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

        // Don't let admins switch off the account they are signed in with, or
        // deactivate the last account that can still log in.
        if (! ($data['is_active'] ?? false)) {
            if ($this->isCurrentUser($user)) {
                return back()->withInput()->with('error', 'You cannot deactivate the account you are signed in with.');
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

        return redirect()->route('backend.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($this->isCurrentUser($user)) {
            return redirect()->route('backend.users.index')
                ->with('error', 'You cannot delete the account you are signed in with.');
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
