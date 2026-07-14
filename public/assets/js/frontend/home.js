/* ==========================================================================
   Hire Minds Academy — Home / Hero interactions (hero only)
   Vanilla JS, no dependencies. Global navbar behaviour lives in
   assets/js/frontend/navbar.js.
   ========================================================================== */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.addEventListener('DOMContentLoaded', function () {
        initReveal();
        initScrollDown();
    });

    /* ---- Entrance animations via IntersectionObserver -------------------- */
    function initReveal() {
        var items = document.querySelectorAll('.hm-reveal');
        if (!items.length) return;

        if (reduceMotion || !('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

        items.forEach(function (el) { observer.observe(el); });
    }

    /* ---- Scroll-down badge --------------------------------------------- */
    function initScrollDown() {
        var btn = document.getElementById('hmScroll');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var hero = document.getElementById('hero');
            var top = hero ? hero.getBoundingClientRect().bottom + window.scrollY : window.innerHeight;
            window.scrollTo({ top: top, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
    }
})();
