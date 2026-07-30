@extends('backend.template.layouts.template-base')

@section('title', 'Users')
@section('page_title', 'Users')
@section('page_sub', 'Accounts that can sign in to this admin panel')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Users</h1>
            <p class="page-head__sub">{{ $users->total() }} user{{ $users->total() === 1 ? '' : 's' }}</p>
        </div>
        <a href="{{ route('backend.users.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add User
        </a>
    </div>

    @include('backend.partials.flash')

    <div class="hm-card">
        @include('backend.partials.table-toolbar', ['placeholder' => 'Search name or email'])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Name</th><th>Email</th><th>Module Access</th><th>Last Login</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php $isSelf = (int) session('admin_id') === $user->id; @endphp
                        <tr>
                            <td class="hm-table__name">
                                {{ $user->name }}
                                @if ($user->is_super_admin)
                                    <span class="hm-table__sub">Main admin{{ $isSelf ? " — that's you" : '' }}</span>
                                @elseif ($isSelf)
                                    <span class="hm-table__sub">that's you</span>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @php $granted = $user->moduleKeys(); @endphp
                                @if ($user->is_super_admin)
                                    <span class="pill pill--tiny pill--active">Full access</span>
                                @elseif (! $granted)
                                    <span class="hm-table__sub">Dashboard only</span>
                                @else
                                    {{ count($granted) }} of {{ count(\App\Support\AdminModules::keys()) }} modules
                                    <span class="hm-table__sub">
                                        {{ collect($granted)->take(3)->map(fn ($k) => \App\Support\AdminModules::label($k))->join(', ') }}{{ count($granted) > 3 ? ' +' . (count($granted) - 3) . ' more' : '' }}
                                    </span>
                                @endif
                            </td>
                            <td class="hm-table__date">
                                @if ($user->last_login_at)
                                    {{ $user->last_login_at->format('d M Y') }}
                                    <span class="hm-table__sub">{{ $user->last_login_at->format('g:i a') }}</span>
                                @else
                                    <span class="hm-table__sub">Never</span>
                                @endif
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $user->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $user->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.users.edit', $user) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    @unless ($isSelf || $user->is_super_admin)
                                        <form method="POST" action="{{ route('backend.users.destroy', $user) }}"
                                              data-confirm="Delete this user? They will no longer be able to sign in." class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                                <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 hm-table__sub">No users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $users])

@endsection
