<!doctype html>
<html lang="en">
<head>
    @include('backend.template.layouts.meta-tags')

    <title>Admin Login — HireMinds Academy</title>

    {{-- Poppins --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">

    {{-- Bootstrap 5 (reset + utilities only; the look is login.css) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">

    {{-- ?v=<mtime> so a CSS edit is never masked by a cached copy. --}}
    <link rel="stylesheet"
          href="{{ asset('backend/assets/css/login.css') }}?v={{ filemtime(public_path('backend/assets/css/login.css')) }}">
</head>
<body>

    <main class="hm-login">

        {{-- ============================ LEFT — form ============================ --}}
        <section class="hm-login__left">
            <div class="hm-login__inner">

                <img class="hm-login__logo"
                     src="{{ asset('backend/template/images/logo.png') }}"
                     alt="HireMinds Academy">
                <p class="hm-login__tagline">Learn &bull; Practice &bull; Get Hired</p>

                <h1 class="hm-login__title">Welcome back!</h1>
                <p class="hm-login__desc">
                    Simple, powerful and secure admin dashboard<br>
                    to manage your academy easily.
                </p>

                {{-- Confirmation coming back from the reset flow --}}
                @if (session('status_message'))
                    <div class="hm-login__note" role="status">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                            <path d="m8.5 12.5 2.2 2.2 4.8-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ session('status_message') }}
                    </div>
                @endif

                {{-- Wrong username / password --}}
                @if (session('login_error'))
                    <div class="hm-login__alert" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 7v6M12 16.5v.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        {{ session('login_error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('backend.auth.authenticate') }}" novalidate autocomplete="off">
                    @csrf

                    {{-- Username --}}
                    <div class="hm-login__field">
                        <label class="visually-hidden" for="username">Username</label>
                        <div class="hm-login__control">
                            <span class="hm-login__icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input type="text"
                                   id="username"
                                   name="username"
                                   class="hm-login__input @error('username') is-invalid @enderror"
                                   placeholder="Username"
                                   value="{{ old('username') }}"
                                   autocomplete="off"
                                   required>
                        </div>
                        @error('username')
                            <p class="hm-login__error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="hm-login__field">
                        <label class="visually-hidden" for="password">Password</label>
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
                                   placeholder="Password"
                                   value=""
                                   autocomplete="new-password"
                                   required>
                            <button type="button" class="hm-login__eye" id="hmTogglePw"
                                    aria-label="Show password" aria-pressed="false">
                                {{-- eye-off (shown while the password is hidden) --}}
                                <svg id="hmEyeOff" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8M9.4 5.3A9.6 9.6 0 0 1 12 5c5 0 8.5 4 9.5 7-.3.9-.9 2-1.8 3M6.2 6.2C4 7.6 2.6 9.7 2 12c1 3 4.5 7 10 7 1.4 0 2.6-.3 3.7-.7"
                                          stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{-- eye (shown while the password is visible) --}}
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

                    {{-- Remember me keeps the session alive for 30 days via a
                         signed cookie; unticked, the login ends with the browser
                         session. --}}
                    <div class="hm-login__row">
                        <label class="hm-login__remember">
                            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            <span class="hm-login__box" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span>Remember me</span>
                        </label>

                        <a href="{{ route('backend.auth.password.request') }}" class="hm-login__forgot">Forgot Password?</a>
                    </div>

                    <button type="submit" class="hm-login__btn">Login</button>
                </form>
            </div>
        </section>

        {{-- ========================= RIGHT — illustration ========================= --}}
        <section class="hm-login__right">
            {{-- Faint emblem watermark --}}
            <img class="hm-login__watermark"
                 src="{{ asset('backend/template/images/favicon.png') }}"
                 alt="" aria-hidden="true">

            <img class="hm-login__illustration"
                 src="{{ asset('backend/template/images/login-img.png') }}"
                 alt="Admin managing the HireMinds Academy dashboard">
{{-- 
            <div class="hm-login__dots" aria-hidden="true">
                <span class="hm-login__dot"></span>
                <span class="hm-login__dot hm-login__dot--active"></span>
                <span class="hm-login__dot"></span>
            </div> --}}

            <h2 class="hm-login__right-title">Manage your academy<br>smarter and faster</h2>
            <p class="hm-login__right-desc">Everything you need, in one powerful dashboard.</p>
        </section>

    </main>

    <script>
        (function () {
            'use strict';

            // Password show / hide.
            var toggle = document.getElementById('hmTogglePw');
            var input  = document.getElementById('password');
            var eyeOn  = document.getElementById('hmEyeOn');
            var eyeOff = document.getElementById('hmEyeOff');

            if (toggle && input) {
                toggle.addEventListener('click', function () {
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    eyeOn.style.display  = show ? 'inline' : 'none';
                    eyeOff.style.display = show ? 'none' : 'inline';
                    toggle.setAttribute('aria-pressed', String(show));
                    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                });
            }
        })();
    </script>
</body>
</html>
