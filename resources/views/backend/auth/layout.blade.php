{{-- Layout shared by the auth screens (forgot password, reset password).
     It is the login page's own two-column shell and stylesheet, so every auth
     screen looks like the login screen apart from the form in the middle.

     Sections: page_title, heading, intro, form. --}}
<!doctype html>
<html lang="en">
<head>
    @include('backend.template.layouts.meta-tags')

    <title>@yield('page_title', 'Admin') — HireMinds Academy</title>

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

                <h1 class="hm-login__title">@yield('heading')</h1>
                <p class="hm-login__desc">@yield('intro')</p>

                @if (session('status_message'))
                    <div class="hm-login__note" role="status">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                            <path d="m8.5 12.5 2.2 2.2 4.8-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ session('status_message') }}
                    </div>
                @endif

                @if (session('login_error'))
                    <div class="hm-login__alert" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 7v6M12 16.5v.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        {{ session('login_error') }}
                    </div>
                @endif

                @yield('form')
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

            <h2 class="hm-login__right-title">Manage your academy<br>smarter and faster</h2>
            <p class="hm-login__right-desc">Everything you need, in one powerful dashboard.</p>
        </section>

    </main>
</body>
</html>
