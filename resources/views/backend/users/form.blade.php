@extends('backend.template.layouts.template-base')

@php
    $editing = $user->exists;
    $isSelf  = $editing && (int) session('admin_id') === $user->id;
@endphp

@section('title', $editing ? 'Edit User' : 'Add User')
@section('page_title', $editing ? 'Edit User' : 'Add User')
@section('page_sub', 'Users')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.users.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Users
        </a>
    </div>

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
                    <div class="col-12 col-md-6">
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

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add User' }}
            </button>
            <a href="{{ route('backend.users.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection
