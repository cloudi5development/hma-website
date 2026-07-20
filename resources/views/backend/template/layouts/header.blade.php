{{-- ============================= ADMIN TOPBAR =============================
     Left: menu toggle + page title. Right: notifications bell + profile.
     The page supplies @section('page_title') / @section('page_sub').
--}}
<header class="app-topbar">

    {{-- Menu toggle — collapses the sidebar to icons (rail); opens it on mobile --}}
    <button type="button" class="app-topbar__menu" id="appMenuToggle" aria-label="Toggle sidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <div>
        <h1 class="app-topbar__title">@yield('page_title', 'Dashboard')</h1>
        <p class="app-topbar__sub">@yield('page_sub', 'Welcome back to HireMinds Academy')</p>
    </div>

    <div class="app-topbar__spacer"></div>

    {{-- Notifications --}}
    <button type="button" class="app-topbar__icon" aria-label="Notifications">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M10.3 21a2 2 0 0 0 3.4 0"/>
        </svg>
        <span class="app-topbar__dot" aria-hidden="true"></span>
    </button>

    {{-- Profile --}}
    <div class="app-topbar__profile">
        <img class="app-topbar__avatar" src="{{ asset('backend/template/images/favicon.png') }}" alt="">
        <span class="app-topbar__who">
            <span class="app-topbar__name d-block">{{ session('admin_email', 'Admin') }}</span>
            <span class="app-topbar__role">Super Admin</span>
        </span>
    </div>
</header>
