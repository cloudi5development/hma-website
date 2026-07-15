@extends('frontend.layouts.template-base')

@section('title', 'About Us — Hire Minds Academy')
@section('meta_description', 'Hire Minds Academy empowers talent through industry-ready learning — practical, career-focused training that prepares learners for today\'s competitive job market.')

@push('styles')
    {{-- Poppins — the About hero heading typeface --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    {{-- Shared sections (also used on the home page) --}}
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/partners.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/counters.css') }}">
    {{-- ?v=<file mtime> busts the browser cache whenever about.css changes,
         so edits are never masked by a stale copy. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/about.css') }}?v={{ filemtime(public_path('assets/css/frontend/about.css')) }}">
@endpush

@section('content')

    {{-- ============================== ABOUT HERO ============================== --}}
    @php
        // Heading is split word-by-word so each one animates in on its own.
        $aboutTitle = [
            ['Empowering', 'Talent'],
            ['Through', 'Industry-Ready'],
            ['Learning.'],
        ];

        // Floating tech icons pinned to the section (assets/images/about-page/).
        // The CSS icon is rendered inside the heading block instead, so it stays
        // glued beside "Talent" — see .hm-abt-float--css in about.css.
        $aboutIcons = [
            ['file' => 'component-7.png', 'mod' => 'html'],
            ['file' => 'component-8.png', 'mod' => 'js'],
        ];

        // Two explicit rows so the pills wrap exactly like the reference (2 + 3).
        $aboutPillRows = [
            ['Industry Experts', 'Practical Training'],
            ['Placement Support', 'Skill Development', 'Career Growth'],
        ];
    @endphp

    <section class="hm-abt-hero" aria-labelledby="hmAboutTitle">

        {{-- Same slow-rotating background image as the home hero --}}
        <div class="hm-abt-hero__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" fetchpriority="low">
        </div>

        {{-- Decorative layer, behind the content --}}
        <div class="hm-abt-deco" aria-hidden="true">
            <img class="hm-abt-deco__dots"    src="{{ asset('assets/images/about-page/component-1.png') }}" alt="">
            <img class="hm-abt-deco__spiral"  src="{{ asset('assets/images/about-page/component-3.png') }}" alt="">
            <img class="hm-abt-deco__strokes" src="{{ asset('assets/images/about-page/component-2.png') }}" alt="">
            <span class="hm-abt-deco__circle"></span>
            <img class="hm-abt-deco__emblem"  src="{{ asset('assets/images/about-page/component-5.png') }}" alt="">
        </div>

        {{-- Floating tech icons — placement / entrance / float are separate layers --}}
        @foreach ($aboutIcons as $icon)
            <span class="hm-abt-float hm-abt-float--{{ $icon['mod'] }}" aria-hidden="true" data-abt-io>
                <span class="hm-abt-anim">
                    <img class="hm-abt-bob" src="{{ asset('assets/images/about-page/'.$icon['file']) }}" alt="">
                </span>
            </span>
        @endforeach

        <div class="container hm-abt-hero__container">

            {{-- ---------------------------- Top badges ---------------------------- --}}
            <div class="hm-abt-hero__top" data-abt-io>
                <div class="hm-abt-stat hm-abt-stat--left hm-abt-anim hm-abt-anim--d1">
                    <p class="hm-abt-stat__text"><strong>100+</strong> Hiring Partners</p>
                    <img class="hm-abt-stat__row"
                         src="{{ asset('assets/images/about-page/component-6.png') }}"
                         alt="Logos of companies that hire Hire Minds Academy graduates">
                </div>

                <div class="hm-abt-stat hm-abt-stat--right hm-abt-anim hm-abt-anim--d2">
                    <p class="hm-abt-stat__text"><strong>2.5k+</strong> Students Trained</p>
                    <img class="hm-abt-stat__row"
                         src="{{ asset('assets/images/about-page/component-4.png') }}"
                         alt="Photos of students trained at Hire Minds Academy">
                </div>
            </div>

            {{-- ------------------------- Label + heading ------------------------- --}}
            <div class="hm-abt-hero__main" data-abt-io>

                {{-- CSS icon sits beside "Talent", so it lives with the heading --}}
                <span class="hm-abt-float hm-abt-float--css" aria-hidden="true">
                    <span class="hm-abt-anim">
                        <img class="hm-abt-bob" src="{{ asset('assets/images/about-page/component-9.png') }}" alt="">
                    </span>
                </span>

                <span class="hm-abt-label hm-abt-anim">
                    <span class="hm-abt-label__sq" aria-hidden="true"></span> About HireMinds Academy
                </span>

                @php $w = 0; @endphp
                <h1 class="hm-abt-title" id="hmAboutTitle">
                    @foreach ($aboutTitle as $line)
                        <span class="hm-abt-line">
                            @foreach ($line as $word)
                                @php $w++; @endphp
                                <span @class([
                                    'hm-abt-word',
                                    'hm-abt-word--d'.$w,
                                    'hm-abt-mark' => $word === 'Learning.',
                                ])>{{ $word }}</span>
                            @endforeach
                        </span>
                    @endforeach
                </h1>
            </div>

            {{-- --------------------------- Review badge --------------------------- --}}
            <div class="hm-abt-review" data-abt-io>
                <img src="{{ asset('assets/images/about-page/star.png') }}" alt="" aria-hidden="true">
                <strong>4.5</strong> <span>Reviews</span>
            </div>

            {{-- -------------------- Pills + description (bottom) ------------------- --}}
            <div class="hm-abt-hero__foot" data-abt-io>
                <div class="hm-abt-pills">
                    @php $p = 0; @endphp
                    @foreach ($aboutPillRows as $row)
                        <ul class="hm-abt-pills__row">
                            @foreach ($row as $pill)
                                @php $p++; @endphp
                                {{-- --c{n} drives both the lit colour and its turn in the loop --}}
                                <li @class([
                                    'hm-abt-pill',
                                    'hm-abt-pill--c'.$p,
                                    'hm-abt-anim',
                                    'hm-abt-anim--d'.min($p, 5),
                                ])>{{ $pill }}</li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>

                <p class="hm-abt-desc hm-abt-anim hm-abt-anim--d2">
                    From fresh graduates to career switchers, we've helped learners develop in-demand skills
                    through practical, career-focused training that prepares them for today's competitive job market.
                </p>
            </div>
        </div>
    </section>

    {{-- ========================= TRUSTED PARTNERS ========================= --}}
    {{-- Shared with the home page — markup in partials/partners.blade.php --}}
    @include('frontend.partials.partners')

    {{-- ============================ OUR STORY ============================ --}}
    @php
        // One array drives both the text column and the image stack.
        $stories = [
            [
                'img'   => 'people-1.webp',
                'title' => 'It All Started with a Simple Mission',
                'desc'  => 'HireMinds Academy was founded with a clear purpose—to bridge the gap between traditional education and real industry expectations. We recognized that many learners possessed academic knowledge but lacked the practical skills needed to build successful careers. This vision inspired us to create training programs that focus on hands-on learning, expert mentorship, and career readiness from day one.',
            ],
            [
                'img'   => 'people-2.webp',
                'title' => 'Learning That Matches Industry Needs',
                'desc'  => 'Every course at HireMinds is carefully designed around current industry requirements rather than outdated academic models. Our learners gain practical experience through real-world projects, interactive classroom sessions, case studies, and expert guidance. By focusing on skills that employers actively seek, we help students build confidence while preparing them for professional challenges and workplace expectations.',
            ],
            [
                'img'   => 'people-3.webp',
                'title' => 'Guiding Every Step of the Career Journey',
                'desc'  => 'Our responsibility goes beyond delivering quality training. We support learners throughout their career journey with personalized mentorship, resume building, interview preparation, communication skills, and placement assistance. Every student receives the guidance needed to confidently transition from learning to employment, ensuring they are prepared for opportunities in today\'s competitive job market.',
            ],
            [
                // people-4.webp ships with a baked-in shadow border, so its photo
                // only fills ~79% of the canvas. 'zoom' crops past that frame so it
                // matches the edge-to-edge framing of the other four.
                'img'   => 'people-4.webp',
                'zoom'  => true,
                'title' => 'Building Careers, Creating Impact',
                'desc'  => 'Today, HireMinds Academy continues to empower students, graduates, career switchers, and working professionals through industry-focused education. Every successful placement, completed project, and learner achievement reflects our commitment to creating meaningful career opportunities. As industries continue to evolve, we remain dedicated to helping learners develop future-ready skills that support long-term professional growth and success.',
            ],
            [
                'img'   => 'people-5.webp',
                'title' => 'Shaping the Future of Professional Learning',
                'desc'  => 'As technology and industries continue to evolve, HireMinds Academy remains committed to delivering future-ready education that adapts to changing workforce demands. We continuously update our programs, strengthen industry partnerships, and introduce innovative learning experiences that help learners stay competitive. Our journey doesn\'t end with a certificate—it begins with building confident professionals ready to make a lasting impact in their careers.',
            ],
        ];
    @endphp

    <section class="hm-story" id="our-story" aria-labelledby="hmStoryHeading">

        {{-- Same slow-rotating hero background (reused, not recreated) --}}
        <div class="hm-story__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
        </div>

        {{-- Decorations --}}
        <div class="hm-story__deco" aria-hidden="true">
            <img class="hm-story__deco-dots"    src="{{ asset('assets/images/about-page/component-1.png') }}" alt="">
            <img class="hm-story__deco-spiral"  src="{{ asset('assets/images/about-page/component-3.png') }}" alt="">
            <img class="hm-story__deco-strokes" src="{{ asset('assets/images/about-page/component-2.png') }}" alt="">
            <img class="hm-story__deco-star-a"  src="{{ asset('assets/images/about-page/element-star.png') }}" alt="">
            <img class="hm-story__deco-star-b"  src="{{ asset('assets/images/about-page/star.png') }}" alt="">
        </div>

        <div class="hm-story__pin" id="hmStoryPin">
            <div class="container">
                <h2 class="visually-hidden" id="hmStoryHeading">Our Story</h2>

                {{-- Each story is one <article> holding BOTH its text and its photo.
                     On desktop the article is `display: contents`, so the text lands
                     in the left column and the photo in the right stack. On mobile it
                     becomes a plain block, giving vertical cards with no duplicate
                     markup. --}}
                <div class="hm-story__grid" id="hmStoryGrid">

                    {{-- "OUR STORY" stays fixed; only title / description / photo / progress change --}}
                    <span class="hm-story__label">
                        <span class="hm-story__label-sq" aria-hidden="true"></span> Our Story
                    </span>

                    {{-- Blob artwork sits behind the whole photo stack --}}
                    <img class="hm-story__blob" src="{{ asset('assets/images/about-page/story-blob.png') }}"
                         alt="" aria-hidden="true">

                    @foreach ($stories as $i => $story)
                        <article class="hm-story__chapter" data-story="{{ $i }}">
                            <div class="hm-story__text" data-story-text="{{ $i }}" @if ($i > 0) aria-hidden="true" @endif>
                                <h3 class="hm-story__title">{{ $story['title'] }}</h3>
                                <p class="hm-story__desc">{{ $story['desc'] }}</p>
                            </div>

                            <figure @class(['hm-story__card', 'hm-story__card--zoom' => ($story['zoom'] ?? false)])
                                    data-story-card="{{ $i }}">
                                <img src="{{ asset('assets/images/about-page/'.$story['img']) }}"
                                     alt="{{ $story['title'] }}" loading="lazy">
                            </figure>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ COUNTERS ============================ --}}
    {{-- Shared with the home page — markup in partials/counters.blade.php --}}
    <section class="hm-stats" aria-label="Hire Minds Academy in numbers">
        <div class="container">
            @include('frontend.partials.counters')
        </div>
    </section>

    {{-- ============================ OUR PURPOSE ============================ --}}
    @php
        // Both cards share one shape; 'tone' picks the colour set in about.css.
        $purposeCards = [
            [
                'tone'  => 'vision',
                'title' => 'Our Vision !',
                'text'  => 'To deliver practical, industry-focused training that empowers individuals with job-ready skills, builds confidence through hands-on learning, and prepares them for long-term career success. We are committed to bridging the gap between academic knowledge and industry expectations by providing expert-led instruction, real-world projects, continuous mentorship, and career guidance. At the same time, today\'s fast-changing business environment.',
            ],
            [
                'tone'  => 'mission',
                'title' => 'Our Mission !',
                'text'  => 'To empower individuals and organizations by building skilled, confident, and future-ready professionals through practical, industry-focused learning experiences. We envision creating a workforce that embraces innovation, adapts to emerging technologies, and thrives in an evolving job market. By fostering continuous learning, professional excellence, and career growth, we aim to strengthening organizations across industries.',
            ],
        ];
    @endphp

    <section class="hm-purpose" id="our-purpose" aria-labelledby="hmPurposeTitle">

        {{-- Decorations — positions follow the reference --}}
        <div class="hm-purpose__deco" aria-hidden="true">
            <img class="hm-purpose__ring hm-purpose__ring--a" src="{{ asset('assets/images/about-page/component-3.png') }}" alt="">
            <img class="hm-purpose__ring hm-purpose__ring--b" src="{{ asset('assets/images/about-page/component-3.png') }}" alt="">
            <img class="hm-purpose__ring hm-purpose__ring--c" src="{{ asset('assets/images/about-page/component-3.png') }}" alt="">
            <img class="hm-purpose__star hm-purpose__star--a" src="{{ asset('assets/images/about-page/star.png') }}" alt="">
            <img class="hm-purpose__star hm-purpose__star--b" src="{{ asset('assets/images/about-page/element-star.png') }}" alt="">
            <span class="hm-purpose__dots"></span>
        </div>

        <div class="container hm-purpose__container" data-abt-io>

            {{-- ------------------------------ Header ------------------------------ --}}
            <header class="hm-purpose__head">
                <span class="hm-purpose__label hm-abt-anim">
                    <span class="hm-purpose__label-sq" aria-hidden="true"></span> Our Purpose
                </span>
                <h2 class="hm-purpose__title hm-abt-anim hm-abt-anim--d1" id="hmPurposeTitle">
                    Driven by Purpose. Guided by Vision.
                </h2>
                <p class="hm-purpose__lead hm-abt-anim hm-abt-anim--d2">
                    Everything we do is built around one goal—to equip learners with practical skills, inspire
                    confidence, and create opportunities that lead to meaningful careers and long-term success.
                </p>
            </header>

            {{-- ---------------------- Image + Vision / Mission ---------------------- --}}
            <div class="row g-4 align-items-stretch hm-purpose__body">
                <div class="col-lg-6">
                    <figure class="hm-purpose__figure hm-abt-anim hm-abt-anim--d1">
                        <img src="{{ asset('assets/images/about-page/our-purpose.webp') }}"
                             alt="A HireMinds Academy mentor guiding learners through a session in the training centre">
                    </figure>
                </div>

                <div class="col-lg-6">
                    <div class="hm-purpose__cards">
                        @foreach ($purposeCards as $i => $card)
                            <article @class([
                                'hm-purpose__card',
                                'hm-purpose__card--'.$card['tone'],
                                'hm-abt-anim',
                                'hm-abt-anim--d'.($i + 2),
                            ])>
                                <h3 class="hm-purpose__card-title">{{ $card['title'] }}</h3>
                                <p class="hm-purpose__card-text">{{ $card['text'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ OUR FEATURES ============================ --}}
    @php
        // 'tone' picks the colour set in about.css. Card 1 carries the eyes
        // illustration instead of a description.
        $features = [
            [
                'tone'  => 'purple',
                'title' => 'Who We Are',
                'text'  => '',
                'eyes'  => true,
            ],
            [
                'tone'  => 'red',
                'title' => 'Industry-Focused Training',
                'text'  => 'Learn with a curriculum designed around real industry requirements.',
            ],
            [
                'tone'  => 'peach',
                'title' => 'Hands-On Learning',
                'text'  => 'Learn by doing with projects and practical exercises.',
            ],
            [
                'tone'  => 'yellow',
                'title' => 'Career Support',
                'text'  => 'Get guidance, interview preparation, and placement assistance.',
            ],
        ];
    @endphp

    <section class="hm-feat" id="our-features" aria-labelledby="hmFeatTitle">

        {{-- Decorations --}}
        <div class="hm-feat__deco" aria-hidden="true">
            <img class="hm-feat__ring" src="{{ asset('assets/images/about-page/component-3.png') }}" alt="">
            <img class="hm-feat__star" src="{{ asset('assets/images/about-page/star.png') }}" alt="">
            <span class="hm-feat__dots"></span>
        </div>

        <div class="container hm-feat__container" data-abt-io>

            <header class="hm-feat__head">
                <span class="hm-feat__label hm-abt-anim">
                    <span class="hm-feat__label-sq" aria-hidden="true"></span> Our Features
                </span>
                <h2 class="hm-feat__title-main hm-abt-anim hm-abt-anim--d1" id="hmFeatTitle">
                    Shaping Future-Ready Professionals
                </h2>
                <p class="hm-feat__lead hm-abt-anim hm-abt-anim--d2">
                    At HireMinds Academy, we combine expert guidance, practical training, and career support
                    to help learners achieve their professional goals.
                </p>
            </header>

            {{-- Expanding cards. Hover is pure CSS; JS only adds tap/keyboard. --}}
            <div class="hm-feat__track hm-abt-anim hm-abt-anim--d3" id="hmFeatTrack">
                @foreach ($features as $feature)
                    <article @class(['hm-feat__card', 'hm-feat__card--'.$feature['tone']])>
                        {{-- Title sits at the top, description is pushed to the
                             bottom of the card (margin-top:auto). --}}
                        <div class="hm-feat__body">
                            <h3 class="hm-feat__card-title">{{ $feature['title'] }}</h3>

                            @if (!empty($feature['text']))
                                <p class="hm-feat__text">{{ $feature['text'] }}</p>
                            @endif
                        </div>

                        @if (!empty($feature['eyes']))
                            <span class="hm-feat__eyes" aria-hidden="true">
                                <span class="hm-feat__eye"></span>
                                <span class="hm-feat__eye"></span>
                            </span>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    {{-- About hero — entrance animations via IntersectionObserver (no libraries) --}}
    <script>
        (function () {
            'use strict';

            var targets = document.querySelectorAll('[data-abt-io]');
            if (!targets.length) return;

            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Reduced motion / no IO support → show everything straight away.
            if (reduce || !('IntersectionObserver' in window)) {
                targets.forEach(function (el) { el.classList.add('is-in'); });
                return;
            }

            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-in');
                    io.unobserve(entry.target);        // animate once
                });
            }, { threshold: 0.2, rootMargin: '0px 0px -8% 0px' });

            targets.forEach(function (el) { io.observe(el); });
        })();
    </script>

    {{-- Our Features — splits the stacked deck into the row once the cards are
         fully on screen. Everything else is CSS. --}}
    <script>
        (function () {
            'use strict';

            var track = document.getElementById('hmFeatTrack');
            if (!track) return;

            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function spread() {
                if (track.classList.contains('is-spread')) return;
                track.classList.add('is-spread');
                // Drop the fan-out stagger once it has played, or every later
                // hover would inherit the delay and feel laggy.
                window.setTimeout(function () { track.classList.add('is-settled'); }, 900);
            }

            if (reduce || !('IntersectionObserver' in window)) {
                spread();
                return;
            }

            // threshold 0.98 — hold the deck together until the cards are
            // *fully* in view, so the split never starts while they are still
            // half cut off at the bottom of the screen.
            new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { spread(); obs.disconnect(); }
                });
            }, { threshold: 0.98 }).observe(track);

            // Hovering the deck splits it early.
            track.addEventListener('mouseenter', spread);
        })();
    </script>

    {{-- Our Story — pinned scroll storytelling (GSAP ScrollTrigger) --}}
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" crossorigin="anonymous" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" crossorigin="anonymous" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var pin  = document.getElementById('hmStoryPin');
            var grid = document.getElementById('hmStoryGrid');
            if (!pin || !grid) return;

            var cards = Array.prototype.slice.call(grid.querySelectorAll('.hm-story__card'));
            var copy  = Array.prototype.slice.call(grid.querySelectorAll('.hm-story__text'));
            if (cards.length < 2 || cards.length !== copy.length) return;

            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Mobile / reduced-motion: every chapter is just a stacked card (CSS
            // handles it) — nothing to drive.
            if (reduce || typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;

            gsap.registerPlugin(ScrollTrigger);

            /* ---- Desktop / tablet: pinned, scrubbed storytelling ---- */
            function initStory() {
                grid.classList.add('is-gsap');

                // Depth layers. Index 0 is the sharp photo on top; the four behind
                // are scattered at alternating, deliberately uneven angles so their
                // corners break out all the way around the front photo — a circle
                // of prints rather than a one-sided fan. Angles sit near ±45° (mod
                // 90) on purpose: that is where a square's corners clear an
                // unrotated square by the most. Clustered near 0° they stay hidden.
                var layers = [
                    { rot: 0,   scale: 1,   blur: 0, op: 1 },
                    { rot: 14,  scale: .97, blur: 2, op: .88 },
                    { rot: -27, scale: .94, blur: 3, op: .78 },
                    { rot: 38,  scale: .91, blur: 4, op: .68 },
                    { rot: -52, scale: .88, blur: 5, op: .58 }
                ];
                function layerAt(depth) { return layers[Math.min(depth, layers.length - 1)]; }

                function layerVars(l, extra) {
                    var v = {
                        rotate: l.rot,
                        scale: l.scale,
                        filter: 'blur(' + l.blur + 'px)',
                        autoAlpha: l.op
                    };
                    if (extra) { for (var k in extra) v[k] = extra[k]; }
                    return v;
                }

                // Initial stack: photo 1 in front, 2–5 fanned out behind it.
                cards.forEach(function (card, i) {
                    gsap.set(card, layerVars(layerAt(i), {
                        y: 0,
                        yPercent: 0,
                        transformOrigin: '50% 50%',
                        zIndex: cards.length - i
                    }));
                });
                gsap.set(copy, { autoAlpha: 0, yPercent: 0 });
                gsap.set(copy[0], { autoAlpha: 1 });
                copy.forEach(function (t, n) { t.setAttribute('aria-hidden', n === 0 ? 'false' : 'true'); });

                var n = cards.length;

                // ONE master timeline. Every story owns one equal segment (1 unit),
                // so 5 stories across "+=3200" = 640px of scroll each. The front
                // half of a segment rests; the hand-off runs in the back half.
                var tl = gsap.timeline({
                    defaults: { ease: 'power3.out' },
                    scrollTrigger: {
                        trigger: pin,
                        start: 'top top',
                        end: '+=3200',
                        pin: pin,
                        scrub: 1.5,
                        anticipatePin: 1,
                        invalidateOnRefresh: true,
                        onUpdate: function (self) {
                            // Keep the screen-reader view on the story being told.
                            var active = Math.min(n - 1, Math.floor(self.progress * n));
                            copy.forEach(function (t, k) {
                                t.setAttribute('aria-hidden', k === active ? 'false' : 'true');
                            });
                        }
                    }
                });

                // Four hand-offs (1→2 … 4→5). After story 5 nothing else animates —
                // the timeline simply runs out and the section unpins.
                for (var i = 0; i < n - 1; i++) {
                    var at = i + 0.5;

                    // Top photo is lifted away: tilts right, slides up, fades out.
                    tl.to(cards[i], {
                        yPercent: -180, rotate: 10, scale: .92,
                        autoAlpha: 0, duration: .5
                    }, at);

                    // Everything underneath shifts forward exactly one position, so
                    // 3 becomes 2nd, 4 becomes 3rd, 5 becomes 4th — no jumps.
                    for (var j = i + 1; j < n; j++) {
                        var vars = layerVars(layerAt(j - i - 1), { duration: .5 });
                        if (j === i + 1) {
                            // Incoming photo rises from +40px as it squares up to 0°.
                            vars.y = 0;
                            tl.fromTo(cards[j], { y: 40 }, vars, at);
                        } else {
                            tl.to(cards[j], vars, at);
                        }
                    }

                    // Copy hands off with the photo. The outgoing line clears out
                    // before the incoming one arrives — both share one grid cell,
                    // so overlapping fades would render the two texts on top of
                    // each other.
                    tl.to(copy[i], { yPercent: -30, autoAlpha: 0, duration: .26 }, at);
                    tl.fromTo(copy[i + 1],
                        { yPercent: 30, autoAlpha: 0 },
                        { yPercent: 0, autoAlpha: 1, duration: .3 }, at + .26);
                }

                // Let story 5 hold for its full segment before the pin releases.
                tl.to({}, { duration: .5 });

                return function () {
                    grid.classList.remove('is-gsap');
                    if (tl.scrollTrigger) tl.scrollTrigger.kill();
                    tl.kill();
                    gsap.set(cards.concat(copy), { clearProps: 'all' });
                };
            }

            gsap.matchMedia().add('(min-width: 768px)', initStory);
        });
    </script>
@endpush
