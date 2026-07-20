{{--
    Admin panel scripts. Page-specific JS → @push('scripts').
--}}
<script>
    (function () {
        'use strict';

        var root     = document.documentElement;   // <html> carries the layout state
        var toggle   = document.getElementById('appMenuToggle');
        var backdrop = document.getElementById('appBackdrop');
        var desktop  = function () { return window.matchMedia('(min-width: 992px)').matches; };

        // Menu button: rail-collapse on desktop, off-canvas open/close on mobile.
        if (toggle) {
            toggle.addEventListener('click', function () {
                if (desktop()) {
                    root.classList.toggle('is-collapsed');
                } else {
                    root.classList.toggle('is-sidebar-open');
                }
            });
        }

        // Backdrop closes the mobile sidebar.
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                root.classList.remove('is-sidebar-open');
            });
        }

        // Group parents expand/collapse their submenu (inline, when not railed).
        document.querySelectorAll('[data-group] > .app-nav__link').forEach(function (parent) {
            parent.addEventListener('click', function (e) {
                e.preventDefault();
                var item = parent.closest('[data-group]');
                // In rail mode the flyout handles it; don't toggle inline.
                if (root.classList.contains('is-collapsed') && desktop()) return;
                item.classList.toggle('is-open');
            });
            parent.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); parent.click(); }
            });
        });

        // Reset transient mobile state when crossing back to desktop.
        window.addEventListener('resize', function () {
            if (desktop()) root.classList.remove('is-sidebar-open');
        });
    })();
</script>
@stack('inline-scripts')
