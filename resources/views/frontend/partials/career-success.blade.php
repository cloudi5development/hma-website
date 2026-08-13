{{--
|--------------------------------------------------------------------------
| Career Success / reel slider (shared section)
|--------------------------------------------------------------------------
|
| Used by the home page ("Our Journey") and the testimonials page ("Career
| Success"):
|
|   @include('frontend.partials.career-success')
|
| The two pages word the header differently, so the copy is optional @include
| data — omit it and you get the home page's wording:
|
|   @include('frontend.partials.career-success', [
|       'csLabel' => 'Career Success',
|       'csTitle' => 'Turning Learning Into Career Success',
|       'csDesc'  => 'Discover how our learners ...',
|   ])
|
| Push its stylesheet from the page (the head is already rendered by the time
| an @include runs, so @push('styles') cannot work from in here):
|
|   <link rel="stylesheet" href="{{ asset('assets/css/frontend/career-success.css') }}?v={{ filemtime(public_path('assets/css/frontend/career-success.css')) }}">
|
| The reels are admin-managed (Sections → Our Journey) and fed to BOTH pages by
| AppServiceProvider's career-success view composer. The Swiper init + the
| in-page reel player ship with this partial, so it carries its own behaviour.
--}}
    @php
        // Fed by the career-success composer (active reels, in order). Each card
        // is an uploaded clip that autoplays (muted, looped) right in the card,
        // and shows a still frame of itself until then — no cover image, nothing
        // to upload. 'url' is optional: if set, clicking the card opens the full
        // reel on Instagram.
        $reels = collect($reels ?? [])->map(fn ($r) => [
            'video' => $r->video_url,
            'url'   => $r->instagram_url,
            'title' => $r->title,
        ])->all();
    @endphp
    @if (count($reels))
    <section class="hm-reels" id="our-journey" data-io aria-labelledby="hmReelsTitle">

        {{-- Same slow-rotating hero background --}}
        <div class="hm-reels__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
        </div>
        <img class="hm-reels__deco hm-reels__deco--star1" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">
        <img class="hm-reels__deco hm-reels__deco--star2" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">

        <div class="container hm-reels__container">

            {{-- Header --}}
            <div class="hm-reels__head">
                <span class="hm-reels__label hm-anim hm-anim--up">
                   <span class="hm-cats__label-icon" aria-hidden="true"></span>
                    <span class="hm-reels__label-text">{{ $csLabel ?? 'Our Journey' }}</span>
                </span>
                <h2 class="hm-reels__title hm-anim hm-anim--up hm-anim--d1" id="hmReelsTitle">{{ $csTitle ?? 'Watch Our Learning Journey' }}</h2>
                <p class="hm-reels__desc hm-anim hm-anim--up hm-anim--d2">
                    @isset($csDesc)
                        {{ $csDesc }}
                    @else
                        Catch the latest classroom moments, workshops, and success stories.<br>
                        Follow <a class="hm-reels__handle" href="https://www.instagram.com/hireminds_academy/" target="_blank" rel="noopener"><i class="fa-brands fa-instagram" aria-hidden="true"></i> @hireminds_academy</a> for more exclusive updates.
                    @endisset
                </p>
            </div>

            {{-- Swiper slider --}}
            <div class="swiper hm-reels__swiper hm-anim hm-anim--up hm-anim--d3">
                <div class="swiper-wrapper">
                    {{-- Rendered twice so there are more slides than are visible — this
                         gives the arrows / loop somewhere to advance to. --}}
                    @foreach (array_merge($reels, $reels) as $reel)
                        <div class="swiper-slide hm-reels__slide">
                            <div class="hm-reel-float">
                                <div class="hm-reel" aria-label="{{ $reel['title'] }}">
                                    {{-- Muted + looped, but nothing is fetched until the section is
                                         on screen: the src lives in data-src and preload is off, so
                                         opening the page no longer pulls every reel down at once.

                                         Once the section scrolls into view each card loads just its
                                         first frame and sits paused on it (see attach() below) —
                                         that still IS the video, so there is no cover image to
                                         upload and nothing to keep in sync.

                                         disablepictureinpicture + controlslist keep the browser's
                                         own overlay chrome out of the way — the bar below is the
                                         only control surface. --}}
                                    <video class="hm-reel__video" data-src="{{ $reel['video'] }}"
                                           muted loop playsinline preload="none"
                                           disablepictureinpicture
                                           controlslist="nodownload noplaybackrate noremoteplayback"></video>

                                    <span class="hm-reel__overlay" aria-hidden="true"></span>

                                    {{-- Big centre button, like a video player's. Doubles as the
                                         "this one is paused" cue on the off-centre cards. --}}
                                    <button type="button" class="hm-reel__big" data-reel-toggle
                                            aria-label="Play {{ $reel['title'] }}">
                                        <i class="fa-solid fa-play" aria-hidden="true"></i>
                                    </button>

                                    @if ($reel['url'])
                                        {{-- Its own button now. It used to be the whole card, which
                                             would fight the play/pause tap. --}}
                                        <a class="hm-reel__badge" href="{{ $reel['url'] }}"
                                           target="_blank" rel="noopener"
                                           aria-label="Watch {{ $reel['title'] }} on Instagram">
                                            <i class="fa-brands fa-instagram" aria-hidden="true"></i> Instagram Reel
                                        </a>
                                    @endif

                                    {{-- Control bar --}}
                                    <div class="hm-reel__controls">
                                        <button type="button" class="hm-reel__ctrl" data-reel-toggle aria-label="Play">
                                            <i class="fa-solid fa-play" aria-hidden="true"></i>
                                        </button>

                                        <div class="hm-reel__scrub" data-reel-seek
                                             role="slider" tabindex="0"
                                             aria-label="Seek" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                            <span class="hm-reel__scrub-fill"></span>
                                        </div>

                                        <span class="hm-reel__time" data-reel-time>0:00</span>

                                        <button type="button" class="hm-reel__ctrl" data-reel-mute aria-label="Unmute">
                                            <i class="fa-solid fa-volume-xmark" aria-hidden="true"></i>
                                        </button>

                                        <button type="button" class="hm-reel__ctrl" data-reel-full aria-label="Full screen">
                                            <i class="fa-solid fa-expand" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Navigation --}}
            <div class="hm-reels__nav hm-anim hm-anim--up hm-anim--d4">
                <button type="button" class="hm-reels__navbtn hm-reels__navbtn--prev" id="hmReelsPrev" aria-label="Previous reels">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </button>
                <button type="button" class="hm-reels__navbtn hm-reels__navbtn--next" id="hmReelsNext" aria-label="Next reels">
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </section>

    @endif

@push('scripts')
    {{-- Reel slider — Swiper + the in-card player. Ships with the partial so the
         component carries its own behaviour wherever it is included. --}}
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" crossorigin="anonymous" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var swiperEl = document.querySelector('.hm-reels__swiper');
            if (!swiperEl || typeof Swiper === 'undefined') return;

            var swiper = new Swiper(swiperEl, {
                slidesPerView: 1.2,
                spaceBetween: 20,
                loop: true,
                speed: 700,
                grabCursor: true,
                // No autoplay. One clip plays at a time and it is the one in the
                // middle — a deck that advanced itself every few seconds would
                // restart that clip before anybody could watch it.
                navigation: { prevEl: '#hmReelsPrev', nextEl: '#hmReelsNext' },
                breakpoints: {
                    768:  { slidesPerView: 3, spaceBetween: 20 },
                    1200: { slidesPerView: 5, spaceBetween: 20 }
                }
            });

            /* ================================================================
               One clip at a time: whichever card is nearest the middle of the
               deck plays, everything else pauses. Worked out from geometry
               rather than from Swiper's activeIndex, because with loop + five
               slides per view "active" is the leftmost visible one, not the
               middle — and the count changes per breakpoint.
               ================================================================ */

            var reels = Array.prototype.slice.call(swiperEl.querySelectorAll('.hm-reel'));
            var sectionVisible = false;   // is the section on screen at all?
            var soundOn = false;          // sticks as the centre moves between cards
            var centre = null;

            function videoOf(reel) { return reel.querySelector('.hm-reel__video'); }

            /**
             * Give a card its still, which is a frame of its own clip.
             *
             * The src is pointed at a media fragment a fraction of a second in,
             * so the browser fetches the metadata plus that one frame and paints
             * it — the card then looks paused on the video rather than showing
             * the player's empty box. #t=0.1 rather than #t=0 on purpose: a lot
             * of clips open on a black fade-in frame, and Safari will not paint
             * frame zero from metadata alone.
             *
             * preload stays at metadata, so this is a header and a frame, not
             * the clip. Only the card in the middle ever downloads the rest.
             */
            function attach(video) {
                if (!video || video.src || !video.dataset.src) return;
                video.preload = 'metadata';
                video.src = video.dataset.src + '#t=0.1';
            }

            function setPlayIcon(reel, playing) {
                reel.classList.toggle('is-playing', playing);
                reel.querySelectorAll('[data-reel-toggle] i').forEach(function (icon) {
                    icon.className = playing ? 'fa-solid fa-pause' : 'fa-solid fa-play';
                });
                reel.querySelectorAll('[data-reel-toggle]').forEach(function (btn) {
                    btn.setAttribute('aria-label', playing ? 'Pause' : 'Play');
                });
            }

            function setMuteIcon(reel, muted) {
                var btn = reel.querySelector('[data-reel-mute]');
                if (!btn) return;
                btn.querySelector('i').className = muted ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high';
                btn.setAttribute('aria-label', muted ? 'Unmute' : 'Mute');
                reel.classList.toggle('is-muted', muted);
            }

            function pause(reel) {
                var v = videoOf(reel);
                if (!v) return;
                if (!v.paused) v.pause();
                // Off-centre cards are always silent, whatever the sound setting is.
                v.muted = true;
                setPlayIcon(reel, false);
                setMuteIcon(reel, true);
            }

            function play(reel) {
                var v = videoOf(reel);
                if (!v) return;
                attach(v);
                v.muted = !soundOn;
                setMuteIcon(reel, v.muted);

                var started = v.play();

                if (started && started.catch) {
                    started.catch(function () {
                        // Unmuted playback is refused until the visitor has
                        // interacted with the page. Fall back to silent rather
                        // than leaving a stalled card.
                        v.muted = true;
                        soundOn = false;
                        setMuteIcon(reel, true);
                        var retry = v.play();
                        if (retry && retry.catch) retry.catch(function () { setPlayIcon(reel, false); });
                    });
                }

                setPlayIcon(reel, true);
            }

            /** The card whose centre is nearest the deck's centre. */
            function centreReel() {
                var box = swiperEl.getBoundingClientRect();
                var mid = box.left + box.width / 2;
                var best = null;
                var bestDistance = Infinity;

                reels.forEach(function (reel) {
                    var r = reel.getBoundingClientRect();
                    if (!r.width) return;   // a cloned slide parked off-layout
                    var d = Math.abs((r.left + r.width / 2) - mid);
                    if (d < bestDistance) { bestDistance = d; best = reel; }
                });

                return best;
            }

            function refresh() {
                var next = centreReel();

                if (next !== centre) {
                    if (centre) { centre.classList.remove('is-centre'); pause(centre); }
                    centre = next;
                    if (centre) centre.classList.add('is-centre');
                }

                reels.forEach(function (reel) { if (reel !== centre) pause(reel); });

                if (!centre) return;

                // Only roll while the section is actually on screen — a clip
                // playing three screens away is decoding for nobody. Every card
                // gets its still at that point, not just the middle one, so the
                // deck reads as a row of paused videos; the middle one then
                // goes on to play.
                if (sectionVisible) {
                    reels.forEach(function (reel) { attach(videoOf(reel)); });
                    if (!centre.dataset.userPaused) play(centre);
                } else {
                    pause(centre);
                }
            }

            swiper.on('slideChangeTransitionEnd', refresh);
            swiper.on('transitionEnd', refresh);
            swiper.on('resize', refresh);

            /* ---- Is the section on screen at all? ---- */
            var section = swiperEl.closest('.hm-reels');

            if ('IntersectionObserver' in window && section) {
                new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        sectionVisible = entry.isIntersecting;
                        refresh();
                    });
                }, { threshold: 0.25 }).observe(section);
            } else {
                sectionVisible = true;
            }

            /* ---- Stills, fetched a screen early ----
               Separate from the observer above on purpose. That one fires at 25%
               visible, which is already too late — the visitor would watch the
               cards fill in. This one starts the frames 600px out, so the deck is
               sitting on its stills by the time it is looked at. One-shot. ---- */
            if ('IntersectionObserver' in window && section) {
                var frameLoader = new IntersectionObserver(function (entries) {
                    if (!entries.some(function (entry) { return entry.isIntersecting; })) return;
                    reels.forEach(function (reel) { attach(videoOf(reel)); });
                    frameLoader.disconnect();
                }, { rootMargin: '600px 0px' });

                frameLoader.observe(section);
            } else {
                reels.forEach(function (reel) { attach(videoOf(reel)); });
            }

            /* ---- Controls ---- */
            swiperEl.addEventListener('click', function (e) {
                var reel = e.target.closest('.hm-reel');
                if (!reel) return;

                // The Instagram badge is a real link — leave it alone.
                if (e.target.closest('.hm-reel__badge')) return;

                var video = videoOf(reel);
                if (!video) return;

                if (e.target.closest('[data-reel-toggle]')) {
                    if (video.paused) {
                        delete reel.dataset.userPaused;
                        // Tapping a card that is not the centre one brings it
                        // there, so the deck follows what was clicked.
                        if (reel !== centre) {
                            var slide = reel.closest('.swiper-slide');
                            var index = slide && slide.getAttribute('data-swiper-slide-index');
                            if (index !== null && swiper.slideToLoop) swiper.slideToLoop(Number(index));
                        }
                        play(reel);
                    } else {
                        reel.dataset.userPaused = '1';
                        video.pause();
                        setPlayIcon(reel, false);
                    }
                    return;
                }

                if (e.target.closest('[data-reel-mute]')) {
                    soundOn = video.muted;          // about to flip
                    video.muted = !video.muted;
                    setMuteIcon(reel, video.muted);

                    // Sound belongs to one card at a time.
                    if (!video.muted) {
                        reels.forEach(function (other) {
                            if (other === reel) return;
                            var v = videoOf(other);
                            if (v) v.muted = true;
                            setMuteIcon(other, true);
                        });
                    }
                    return;
                }

                if (e.target.closest('[data-reel-full]')) {
                    var request = video.requestFullscreen || video.webkitRequestFullscreen
                        || video.webkitEnterFullscreen;   // iOS Safari exposes only this one
                    if (request) request.call(video);
                }
            });

            /* ---- Progress + seeking ---- */
            function label(seconds) {
                if (!isFinite(seconds)) return '0:00';
                var m = Math.floor(seconds / 60), s = Math.floor(seconds % 60);
                return m + ':' + (s < 10 ? '0' : '') + s;
            }

            reels.forEach(function (reel) {
                var video = videoOf(reel);
                var scrub = reel.querySelector('[data-reel-seek]');
                var fill  = reel.querySelector('.hm-reel__scrub-fill');
                var time  = reel.querySelector('[data-reel-time]');
                if (!video) return;

                video.addEventListener('timeupdate', function () {
                    if (!video.duration) return;
                    var pct = (video.currentTime / video.duration) * 100;
                    if (fill) fill.style.width = pct + '%';
                    if (time) time.textContent = label(video.currentTime);
                    if (scrub) scrub.setAttribute('aria-valuenow', Math.round(pct));
                });

                video.addEventListener('ended', function () { setPlayIcon(reel, false); });

                if (!scrub) return;

                function seekTo(clientX) {
                    if (!video.duration) return;
                    var r = scrub.getBoundingClientRect();
                    var pct = Math.min(1, Math.max(0, (clientX - r.left) / r.width));
                    video.currentTime = pct * video.duration;
                }

                scrub.addEventListener('click', function (e) { e.stopPropagation(); seekTo(e.clientX); });
                scrub.addEventListener('keydown', function (e) {
                    if (!video.duration) return;
                    if (e.key === 'ArrowRight') { e.preventDefault(); video.currentTime = Math.min(video.duration, video.currentTime + 5); }
                    if (e.key === 'ArrowLeft')  { e.preventDefault(); video.currentTime = Math.max(0, video.currentTime - 5); }
                });
            });

            refresh();
        });
    </script>
@endpush
