{{-- Admin top navigation bar. Replace the placeholder markup with your real topbar (search, notifications, profile menu). --}}
<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex align-items-center">
            <a href="{{ route('backend.auth.login') }}" class="logo">
                <img src="{{ asset('backend/template/images/logo.png') }}" alt="{{ config('app.name') }}" height="30">
            </a>

            <button type="button" class="btn btn-sidebar-toggle" aria-label="Toggle sidebar">
                <i class="icon-menu"></i>
            </button>
        </div>

        <div class="d-flex align-items-center">
            {{-- Notifications, profile dropdown, etc. --}}
        </div>
    </div>
</header>
