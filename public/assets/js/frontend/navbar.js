/* ==========================================================================
   Hire Minds Academy — Global navbar behaviour
   Markup: resources/views/frontend/layouts/header.blade.php
   Loaded site-wide via layouts/common-js. Vanilla JS, no dependencies.
   ========================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initNavbarScroll();
        initNavToggle();
        initMegaMenu();
    });

    /* Solidify the floating navbar once the page scrolls */
    function initNavbarScroll() {
        var navbar = document.getElementById('hmNavbar');
        if (!navbar) return;

        var onScroll = function () {
            navbar.classList.toggle('is-scrolled', window.scrollY > 30);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* Accessible mobile navigation toggle */
    function initNavToggle() {
        var navbar = document.getElementById('hmNavbar');
        var toggle = document.getElementById('hmNavToggle');
        var menu = document.getElementById('hmNavMenu');
        if (!navbar || !toggle || !menu) return;

        var setOpen = function (open) {
            navbar.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', String(open));
        };

        toggle.addEventListener('click', function () {
            setOpen(!navbar.classList.contains('is-open'));
        });

        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () { setOpen(false); });
        });

        document.addEventListener('click', function (e) {
            if (navbar.classList.contains('is-open') && !navbar.contains(e.target)) {
                setOpen(false);
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });
    }

    /* Courses mega-dropdown — opens on click, closes on outside click / Escape */
    function initMegaMenu() {
        var toggle = document.getElementById('hmCoursesToggle');
        var menu = document.getElementById('hmCoursesMenu');
        if (!toggle || !menu) return;

        var item = toggle.closest('.hm-nav-item--mega');
        if (!item) return;

        var setOpen = function (open) {
            item.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', String(open));
        };

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            setOpen(!item.classList.contains('is-open'));
        });

        // Clicks inside the panel shouldn't close it
        menu.addEventListener('click', function (e) { e.stopPropagation(); });

        // Choosing a category closes the panel (and the mobile menu)
        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () { setOpen(false); });
        });

        // Close on outside click / Escape
        document.addEventListener('click', function (e) {
            if (item.classList.contains('is-open') && !item.contains(e.target)) {
                setOpen(false);
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });
    }
})();
