{{--
|--------------------------------------------------------------------------
| Page loader (site-wide)
|--------------------------------------------------------------------------
|
| Included as the first thing inside <body> by layouts/template-base. Styling
| is assets/css/frontend/loader.css, which layouts/common-css loads first so
| the overlay paints before anything behind it can flash.
|
| The emblem is the real logo — favicon.png split losslessly into four layers
| (see the header of loader.css). Only the wings and the badge shimmer move.
--}}

<div class="hm-loader" id="hmLoader" role="status" aria-live="polite">
    <span class="visually-hidden">Loading Hire Minds Academy…</span>

    {{-- Decorative: the status text above is what gets announced. --}}
    <div class="hm-loader__mark" aria-hidden="true">
        <img class="hm-loader__layer hm-loader__wing hm-loader__wing--l"
             src="{{ asset('assets/images/branding/loader/wing-left.png') }}" alt="">
        <img class="hm-loader__layer hm-loader__wing hm-loader__wing--r"
             src="{{ asset('assets/images/branding/loader/wing-right.png') }}" alt="">
        <img class="hm-loader__layer"
             src="{{ asset('assets/images/branding/loader/body.png') }}" alt="">

        {{-- Masked to the shield+star silhouette so the shine cannot spill past
             the metal. The two layers inside reveal in sequence. --}}
        <div class="hm-loader__layer hm-loader__badge">
            <img class="hm-loader__layer hm-loader__shield"
                 src="{{ asset('assets/images/branding/loader/shield.png') }}" alt="">
            <img class="hm-loader__layer hm-loader__star"
                 src="{{ asset('assets/images/branding/loader/star.png') }}" alt="">
            <span class="hm-loader__shine"></span>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        var loader = document.getElementById('hmLoader');
        if (!loader) return;

        document.body.classList.add('hm-loading');

        var shownAt = Date.now();

        // The emblem's reveal completes at 1.2s, so this is the shortest hold
        // that never tears the logo away half-built. It is a floor, not a wait.
        var MIN  = 1250;
        var MAX  = 5000;   // never hold the page hostage if a signal never fires
        var done = false;

        function dismiss() {
            if (done) return;
            done = true;

            loader.classList.add('is-done');
            document.body.classList.remove('hm-loading');

            // Drop it from the DOM once the fade has run, so its animations stop
            // burning frames for the rest of the visit.
            loader.addEventListener('transitionend', function () {
                if (loader.parentNode) loader.parentNode.removeChild(loader);
            }, { once: true });

            setTimeout(function () {                    // belt and braces if the
                if (loader.parentNode) loader.parentNode.removeChild(loader);
            }, 600);                                    // transition never fires
        }

        function finish() {
            setTimeout(dismiss, Math.max(0, MIN - (Date.now() - shownAt)));
        }

        // Deliberately NOT window.load: that waits for every image on the page,
        // including everything far below the fold, which is what made the loader
        // outstay the content. Ready + the hero image decoded is the real signal
        // that what the visitor is about to look at is actually there.
        function ready() {
            var hero = document.querySelector('[data-hm-hero-img]');

            if (!hero) { finish(); return; }
            if (hero.complete) { finish(); return; }

            // Resolve either way — a hero that 404s must not strand the loader.
            hero.addEventListener('load',  finish, { once: true });
            hero.addEventListener('error', finish, { once: true });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', ready);
        } else {
            ready();
        }

        setTimeout(dismiss, MAX);
    })();
</script>
