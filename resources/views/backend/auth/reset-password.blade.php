@extends('backend.auth.layout')

@section('page_title', 'Reset Password')
@section('heading', 'Set a new password')

@section('intro')
    Choose a password you have not used before.<br>
    At least 8 characters.
@endsection

@section('form')
    <form method="POST" action="{{ route('backend.auth.password.update') }}" novalidate>
        @csrf

        {{-- The token comes from the emailed link; the address is shown so it is
             obvious which account is being changed. --}}
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="hm-login__field">
            <label class="visually-hidden" for="email">Email</label>
            <div class="hm-login__control">
                <span class="hm-login__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="5" width="18" height="14" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                        <path d="m3.6 7 8.4 6 8.4-6" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </span>
                <input type="email"
                       id="email"
                       name="email"
                       class="hm-login__input @error('email') is-invalid @enderror"
                       placeholder="name@example.com"
                       value="{{ old('email', $email) }}"
                       autocomplete="email"
                       required>
            </div>
            @error('email')
                <p class="hm-login__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="hm-login__field">
            <label class="visually-hidden" for="password">New password</label>
            <div class="hm-login__control">
                <span class="hm-login__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <rect x="4" y="10" width="16" height="10" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M8 10V7a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </span>
                <input type="password"
                       id="password"
                       name="password"
                       class="hm-login__input hm-login__input--password @error('password') is-invalid @enderror"
                       placeholder="New password"
                       autocomplete="new-password"
                       required>
                <button type="button" class="hm-login__eye" id="hmTogglePw"
                        aria-label="Show password" aria-pressed="false">
                    <svg id="hmEyeOff" width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8M9.4 5.3A9.6 9.6 0 0 1 12 5c5 0 8.5 4 9.5 7-.3.9-.9 2-1.8 3M6.2 6.2C4 7.6 2.6 9.7 2 12c1 3 4.5 7 10 7 1.4 0 2.6-.3 3.7-.7"
                              stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <svg id="hmEyeOn" width="20" height="20" viewBox="0 0 24 24" fill="none" style="display:none">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="hm-login__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="hm-login__field">
            <label class="visually-hidden" for="password_confirmation">Confirm password</label>
            <div class="hm-login__control">
                <span class="hm-login__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <rect x="4" y="10" width="16" height="10" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M8 10V7a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </span>
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       class="hm-login__input"
                       placeholder="Repeat the password"
                       autocomplete="new-password"
                       required>
            </div>
        </div>

        <button type="submit" class="hm-login__btn">Update password</button>

        <a href="{{ route('backend.auth.login') }}" class="hm-login__back">&larr; Back to sign in</a>
    </form>

    <script>
        (function () {
            'use strict';

            var toggle = document.getElementById('hmTogglePw');
            var input  = document.getElementById('password');
            var eyeOn  = document.getElementById('hmEyeOn');
            var eyeOff = document.getElementById('hmEyeOff');

            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                eyeOn.style.display  = show ? 'inline' : 'none';
                eyeOff.style.display = show ? 'none' : 'inline';
                toggle.setAttribute('aria-pressed', String(show));
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        })();
    </script>
@endsection
