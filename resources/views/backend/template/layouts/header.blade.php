{{-- ============================= ADMIN TOPBAR =============================
     Left: menu toggle + search. Right: mail, notifications, profile.
     The menu toggle keeps its existing behaviour (collapse / off-canvas).
--}}
<header class="app-topbar">

    {{-- Menu toggle — collapses the sidebar to icons (rail); opens it on mobile --}}
    <button type="button" class="app-topbar__menu" id="appMenuToggle" aria-label="Toggle sidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    {{-- Search (UI shell — wire to a real search later) --}}
    <div class="app-topbar__search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
        </svg>
        <input type="search" placeholder="Search…" aria-label="Search" autocomplete="off">
        <span class="app-topbar__kbd">&#8984; F</span>
    </div>

    <div class="app-topbar__spacer"></div>

    {{-- Mail --}}
    <button type="button" class="app-topbar__icon" aria-label="Messages">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.6 7 8.4 6 8.4-6"/>
        </svg>
    </button>

    {{-- Notifications --}}
    <div class="app-notif" id="appNotif">
        <button type="button" class="app-topbar__icon" id="appNotifBtn" aria-label="Notifications" aria-expanded="false" aria-haspopup="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M10.3 21a2 2 0 0 0 3.4 0"/>
            </svg>
            @if (($adminNotifUnread ?? 0) > 0)
                <span class="app-notif__badge">{{ $adminNotifUnread > 9 ? '9+' : $adminNotifUnread }}</span>
            @else
                <span class="app-topbar__dot" aria-hidden="true" style="display:none"></span>
            @endif
        </button>

        <div class="app-notif__panel" id="appNotifPanel" role="menu" hidden>
            <div class="app-notif__head">
                <span>Notifications</span>
                @if (($adminNotifUnread ?? 0) > 0)
                    <form method="POST" action="{{ route('backend.notifications.read-all') }}" style="margin:0">
                        @csrf
                        <button type="submit" class="app-notif__mark">Mark all read</button>
                    </form>
                @endif
            </div>
            <ul class="app-notif__list">
                @forelse (($adminNotifications ?? []) as $n)
                    <li class="app-notif__item {{ $n->is_read ? '' : 'is-unread' }}">
                        <a href="{{ route('backend.notifications.open', $n) }}">
                            <span class="app-notif__title">{{ $n->title }}</span>
                            @if ($n->body) <span class="app-notif__body">{{ $n->body }}</span> @endif
                            <span class="app-notif__time">{{ $n->created_at->diffForHumans() }}</span>
                        </a>
                    </li>
                @empty
                    <li class="app-notif__empty">You're all caught up.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Profile --}}
    <div class="app-topbar__profile">
        <img class="app-topbar__avatar" src="{{ asset('backend/template/images/favicon.png') }}" alt="">
        <span class="app-topbar__who">
            <span class="app-topbar__name d-block">HireMinds Admin</span>
            <span class="app-topbar__role">{{ session('admin_email', 'admin@gmail.com') }}</span>
        </span>
        <span class="app-topbar__chev">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
        </span>
    </div>
</header>

{{-- Notification bell — open/close, click-outside, Escape. --}}
<script>
    (function () {
        'use strict';
        var root  = document.getElementById('appNotif');
        var btn   = document.getElementById('appNotifBtn');
        var panel = document.getElementById('appNotifPanel');
        if (!root || !btn || !panel) return;

        function close() { panel.hidden = true; btn.setAttribute('aria-expanded', 'false'); }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = panel.hidden;
            panel.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function (e) { if (!root.contains(e.target)) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    })();
</script>
