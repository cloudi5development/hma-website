@extends('backend.template.layouts.template-base')

@php
    $editing = $user->exists;
    $isSelf  = $editing && (int) session('admin_id') === $user->id;

    // Module access. The main admin holds everything and cannot be edited down,
    // so their grid renders ticked and disabled.
    $isMainAdmin = $editing && $user->is_super_admin;
    $checked     = collect(old('modules', $user->moduleKeys()));

    // Only the main admin hands out access. Anyone else who gets here is on their
    // own profile (see EnsureModuleAccess), so the page drops to name / email /
    // password — the fields they are actually allowed to change.
    $canManageAccess = \App\Support\AdminAuth::isSuperAdmin();
    $profileOnly     = ! $canManageAccess;
@endphp

@section('title', $profileOnly ? 'My Profile' : ($editing ? 'Edit User' : 'Add User'))
@section('page_title', $profileOnly ? 'My Profile' : ($editing ? 'Edit User' : 'Add User'))
@section('page_sub', $profileOnly ? 'Your account' : 'Users')

@section('content')

    @unless ($profileOnly)
        <div class="page-head">
            <a href="{{ route('backend.users.index') }}" class="btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Back to Users
            </a>
        </div>
    @endunless

    @include('backend.partials.flash')

    <form method="POST"
          action="{{ $editing ? route('backend.users.update', $user) : route('backend.users.store') }}"
          novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- One container for the whole form --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- Row 1 — name + status --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="name">
                                Full Name <span style="color:var(--danger)">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control-hm @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}" placeholder="e.g. Keerthika K" required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6" @if ($profileOnly) hidden @endif>
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1"
                                           {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}
                                           {{ $isSelf ? 'disabled' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                            @if ($isSelf)
                                {{-- Disabled inputs post nothing; keep the flag so saving your own
                                     profile doesn't read as "deactivate me". --}}
                                <input type="hidden" name="is_active" value="1">
                                <p class="form-hint">You cannot deactivate your own account.</p>
                            @else
                                <p class="form-hint">A disabled account cannot sign in.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Row 2 — email --}}
                <div class="form-row mt-2">
                    <label class="form-label" for="email">
                        Email <span style="color:var(--danger)">*</span>
                    </label>
                    <input type="email" id="email" name="email"
                           class="form-control-hm @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email) }}" placeholder="name@example.com" required>
                    <p class="form-hint">This is the username used to sign in.</p>
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 3 — password --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="password">
                                Password
                                @if ($editing)
                                    <span class="form-hint" style="display:inline">(leave blank to keep the current one)</span>
                                @else
                                    <span style="color:var(--danger)">*</span>
                                @endif
                            </label>
                            <input type="password" id="password" name="password" autocomplete="new-password"
                                   class="form-control-hm @error('password') is-invalid @enderror"
                                   placeholder="At least 8 characters" {{ $editing ? '' : 'required' }}>
                            @error('password') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="password_confirmation">Confirm Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   autocomplete="new-password" class="form-control-hm"
                                   placeholder="Repeat the password" {{ $editing ? '' : 'required' }}>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ---------------------------- Module access ----------------------------
             Which parts of the panel this account can open. Anything not ticked is
             hidden from their sidebar AND refused if they type the URL — the
             admin.module middleware checks every admin route against this list.
             Shown to the main admin only: this is the one place access is handed
             out, and posting the field as anyone else is discarded server-side. --}}
        @if ($canManageAccess)
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <div>
                        <label class="form-label" style="margin-bottom:2px">Module Access</label>
                        <p class="form-hint" style="margin:0">
                            @if ($isMainAdmin)
                                This is the main admin account — it always has access to everything,
                                including Users.
                            @else
                                Tick what this user may open. Everything else stays hidden from them.
                                The Dashboard is always available; Users is the main admin's only.
                            @endif
                        </p>
                    </div>

                    @unless ($isMainAdmin)
                        <label class="check-chip" id="userModulesAll">
                            <input type="checkbox">
                            <span class="check-chip__box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span class="check-chip__label">Select all</span>
                        </label>
                    @endunless
                </div>

                @error('modules') <p class="form-error">{{ $message }}</p> @enderror
                @error('modules.*') <p class="form-error">{{ $message }}</p> @enderror

                @foreach (\App\Support\AdminModules::GROUPS as $group => $modules)
                    <div class="form-row" style="margin-bottom:0" @class(['mt-3' => ! $loop->first])>
                        <label class="form-label" style="font-size:12.5px;text-transform:uppercase;letter-spacing:.04em">
                            {{ $group }}
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($modules as $key => $label)
                                <label class="check-chip">
                                    <input type="checkbox" name="modules[]" value="{{ $key }}"
                                           {{ $isMainAdmin || $checked->contains($key) ? 'checked' : '' }}
                                           {{ $isMainAdmin ? 'disabled' : '' }}
                                           data-module-box>
                                    <span class="check-chip__box">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    </span>
                                    <span class="check-chip__label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
        @endif

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add User' }}
            </button>
            <a href="{{ $profileOnly ? route('backend.dashboard') : route('backend.users.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

    @if ($canManageAccess && ! $isMainAdmin)
        {{-- "Select all" mirrors the boxes both ways: it ticks/unticks every
             module, and it reflects whether they are all already ticked. --}}
        <script>
            (function () {
                'use strict';

                var all   = document.querySelector('#userModulesAll input');
                var boxes = Array.prototype.slice.call(document.querySelectorAll('[data-module-box]'));

                if (!all || !boxes.length) return;

                function syncAll() {
                    all.checked = boxes.every(function (box) { return box.checked; });
                }

                all.addEventListener('change', function () {
                    boxes.forEach(function (box) { box.checked = all.checked; });
                });

                boxes.forEach(function (box) { box.addEventListener('change', syncAll); });

                syncAll();
            })();
        </script>
    @endif

@endsection
