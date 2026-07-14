/* ==========================================================================
   Hire Minds Academy — Global footer behaviour
   Markup: resources/views/frontend/layouts/footer.blade.php
   Loaded site-wide via layouts/common-js. Vanilla JS, no dependencies.
   ========================================================================== */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.addEventListener('DOMContentLoaded', function () {
        initReveal();
        initScrollTop();
    });

    /* Fade the columns up when the footer scrolls into view (staggered via CSS).
       Progressive enhancement: without JS the footer stays fully visible. */
    function initReveal() {
        var footer = document.getElementById('hmFooter');
        if (!footer) return;
        if (reduceMotion || !('IntersectionObserver' in window)) return;

        footer.classList.add('hm-footer--reveal');

        var observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    footer.classList.add('is-visible');
                    obs.disconnect();
                }
            });
        }, { threshold: 0.12 });

        observer.observe(footer);
    }

    /* Floating scroll-to-top button */
    function initScrollTop() {
        var btn = document.getElementById('hmScrollTop');
        if (!btn) return;

        var onScroll = function () {
            btn.classList.toggle('is-visible', window.scrollY > 400);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
    }
})();
