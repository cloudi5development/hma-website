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

    /* Solidify the floating navbar once the page scrolls, and hide it on
       scroll-down / reveal it on scroll-up (once past the hero). */
    function initNavbarScroll() {
        var navbar = document.getElementById('hmNavbar');
        if (!navbar) return;

        var hero = document.getElementById('hero');
        var lastY = window.scrollY || 0;
        var ticking = false;

        var update = function () {
            ticking = false;
            var y = window.scrollY || 0;

            navbar.classList.toggle('is-scrolled', y > 30);

            // Where the hide/show behaviour starts — below the hero on the home
            // page, or a small offset on inner pages that have no hero.
            var threshold = hero ? (hero.offsetTop + hero.offsetHeight - 60) : 120;

            // Always show near the top, or while a menu / dropdown is open.
            if (y < threshold || navbar.classList.contains('is-open') || navbar.querySelector('.is-open')) {
                navbar.classList.remove('hm-navbar--hidden');
                lastY = y;
                return;
            }

            var delta = y - lastY;
            if (delta > 6) {
                navbar.classList.add('hm-navbar--hidden');      // scrolling down → hide
            } else if (delta < -6) {
                navbar.classList.remove('hm-navbar--hidden');   // scrolling up → show
            }
            lastY = y;
        };

        var onScroll = function () {
            if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
        };
        update();
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
