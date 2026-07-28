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

    {{-- Profile + account menu --}}
    <div class="app-profile" id="appProfile">
        <button type="button" class="app-topbar__profile" id="appProfileBtn"
                aria-haspopup="menu" aria-expanded="false" aria-controls="appProfileMenu">
            <img class="app-topbar__avatar" src="{{ asset('backend/template/images/favicon.png') }}" alt="">
            <span class="app-topbar__who">
                <span class="app-topbar__name d-block">{{ session('admin_name', 'HireMinds Admin') }}</span>
                <span class="app-topbar__role">{{ session('admin_email', 'admin@gmail.com') }}</span>
            </span>
            <span class="app-topbar__chev">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
            </span>
        </button>

        <div class="app-menu" id="appProfileMenu" role="menu" hidden>
            <div class="app-menu__head">
                <span class="app-menu__name">{{ session('admin_name', 'HireMinds Admin') }}</span>
                <span class="app-menu__mail">{{ session('admin_email', 'admin@gmail.com') }}</span>
            </div>

            @if (session('admin_id'))
                <a class="app-menu__item" role="menuitem" href="{{ route('backend.users.edit', session('admin_id')) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.6"/><path d="M4.5 20c0-3.6 3.4-6 7.5-6s7.5 2.4 7.5 6"/></svg>
                    My Profile
                </a>
            @endif

            <a class="app-menu__item" role="menuitem" href="{{ route('backend.users.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.4"/><path d="M3 20c0-3.3 2.7-5.4 6-5.4s6 2.1 6 5.4"/><path d="M16 4.6a3.4 3.4 0 0 1 0 6.8M21 20c0-2.6-1.5-4.3-4-5"/></svg>
                Users
            </a>

            <a class="app-menu__item" role="menuitem" href="{{ route('backend.settings.general') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3.2"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg>
                Settings
            </a>

            <div class="app-menu__sep"></div>

            <form method="POST" action="{{ route('backend.auth.logout') }}"
                  data-confirm="You will be signed out of the admin panel."
                  data-confirm-title="Sign out?"
                  data-confirm-label="Sign out"
                  data-confirm-icon="logout">
                @csrf
                <button type="submit" class="app-menu__item app-menu__item--danger" role="menuitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17l5-5-5-5"/><path d="M20 12H9M12 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6"/></svg>
                    Sign Out
                </button>
            </form>
        </div>
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

    // Profile / account menu — same open-close behaviour as the bell.
    (function () {
        'use strict';
        var root  = document.getElementById('appProfile');
        var btn   = document.getElementById('appProfileBtn');
        var menu  = document.getElementById('appProfileMenu');
        if (!root || !btn || !menu) return;

        function close() {
            menu.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
            root.classList.remove('is-open');
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = menu.hidden;
            menu.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            root.classList.toggle('is-open', open);
        });

        document.addEventListener('click', function (e) { if (!root.contains(e.target)) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    })();
</script>
