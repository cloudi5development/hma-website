{{-- Admin side navigation menu. Replace the placeholder markup with your real menu tree. --}}
<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        <a href="{{ route('backend.auth.login') }}" class="logo">
            <img src="{{ asset('backend/template/images/logo.png') }}" alt="{{ config('app.name') }}" height="30">
        </a>
    </div>

    <nav class="sidebar-menu">
        <ul class="navbar-nav">
            {{-- Example:
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="icon-dashboard"></i> Dashboard</a>
            </li>
            --}}
        </ul>
    </nav>
</div>
