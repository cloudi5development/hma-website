@extends('backend.auth.layout')

@section('page_title', 'Forgot Password')
@section('heading', 'Forgot your password?')

@section('intro')
    Enter the email address of your admin account and<br>
    we will send you a link to set a new one.
@endsection

@section('form')
    <form method="POST" action="{{ route('backend.auth.password.email') }}" novalidate>
        @csrf

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
                       value="{{ old('email') }}"
                       autocomplete="email"
                       required>
            </div>
            @error('email')
                <p class="hm-login__error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="hm-login__btn">Send reset link</button>

        <a href="{{ route('backend.auth.login') }}" class="hm-login__back">&larr; Back to sign in</a>
    </form>
@endsection
