@extends('frontend.layouts.template-base')

@section('title', 'About Us — Hire Minds Academy')
@section('meta_description', 'Hire Minds Academy empowers talent through industry-ready learning — practical, career-focused training that prepares learners for today\'s competitive job market.')

@push('styles')
    {{-- Poppins — the About hero heading typeface --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    {{-- Shared sections (also used on the home page) --}}
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/partners.css') }}?v={{ filemtime(public_path('assets/css/frontend/partners.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/counters.css') }}?v={{ filemtime(public_path('assets/css/frontend/counters.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/testimonials.css') }}?v={{ filemtime(public_path('assets/css/frontend/testimonials.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/faq.css') }}?v={{ filemtime(public_path('assets/css/frontend/faq.css')) }}">
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
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" fetchpriority="low">
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
        // Admin → Sections → About Us → Our Story. One collection drives both the
        // text column and the image stack, mapped to the exact shape this markup
        // already used — only the data source moved to the database.
        $storySection = $aboutSections['story'] ?? null;
        $storyLabel   = $storySection?->label ?: 'Our Story';

        $stories = collect($storySection?->items ?? [])->map(fn ($item) => [
            'img'   => $item->image,
            'zoom'  => $item->zoom,
            'title' => $item->title,
            'desc'  => $item->text,
        ])->all();
    @endphp
    @if (count($stories))
    <section class="hm-story" id="our-story" aria-labelledby="hmStoryHeading">

        <div class="hm-story__pin" id="hmStoryPin">

            {{-- The background and decorations live INSIDE the pinned element on
                 purpose. GSAP pins this div, so anything outside it keeps
                 scrolling while the pin holds — which made the stars drift up
                 and away during the story. Pinned with it, they stay put. --}}
            <div class="hm-story__bg" aria-hidden="true">
                <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
            </div>

            <div class="hm-story__deco" aria-hidden="true">
                <img class="hm-story__deco-dots"    src="{{ asset('assets/images/about-page/component-1.png') }}" alt="" loading="lazy" decoding="async">
                <img class="hm-story__deco-spiral"  src="{{ asset('assets/images/about-page/component-3.png') }}" alt="" loading="lazy" decoding="async">
                <img class="hm-story__deco-strokes" src="{{ asset('assets/images/about-page/component-2.png') }}" alt="" loading="lazy" decoding="async">
                <img class="hm-story__deco-star-a"  src="{{ asset('assets/images/about-page/element-star.png') }}" alt="" loading="lazy" decoding="async">
                <img class="hm-story__deco-star-b"  src="{{ asset('assets/images/about-page/star.png') }}" alt="" loading="lazy" decoding="async">
            </div>

            <div class="container">
                <h2 class="visually-hidden" id="hmStoryHeading">{{ $storyLabel }}</h2>

                {{-- Each story is one <article> holding BOTH its text and its photo.
                     On desktop the article is `display: contents`, so the text lands
                     in the left column and the photo in the right stack. On mobile it
                     becomes a plain block, giving vertical cards with no duplicate
                     markup. --}}
                <div class="hm-story__grid" id="hmStoryGrid">

                    {{-- "OUR STORY" stays fixed; only title / description / photo / progress change --}}
                    <span class="hm-story__label">
                        <span class="hm-story__label-sq" aria-hidden="true"></span> {{ $storyLabel }}
                    </span>

                    {{-- Blob artwork sits behind the whole photo stack --}}
                    <img class="hm-story__blob" src="{{ asset('assets/images/about-page/story-blob.webp') }}"
                         alt="" aria-hidden="true" loading="lazy" decoding="async">

                    @foreach ($stories as $i => $story)
                        <article class="hm-story__chapter" data-story="{{ $i }}">
                            <div class="hm-story__text" data-story-text="{{ $i }}" @if ($i > 0) aria-hidden="true" @endif>
                                <h3 class="hm-story__title">{{ $story['title'] }}</h3>
                                <p class="hm-story__desc">{{ $story['desc'] }}</p>
                            </div>

                            {{-- The figure is rendered even for a chapter with no
                                 photo: the scroll animation pairs one card with one
                                 block of copy and stands down when the counts differ. --}}
                            <figure @class(['hm-story__card', 'hm-story__card--zoom' => ($story['zoom'] ?? false)])
                                    data-story-card="{{ $i }}">
                                @if ($story['img'])
                                    <img src="{{ asset($story['img']) }}"
                                         alt="{{ $story['title'] }}" loading="lazy">
                                @endif
                            </figure>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ============================ COUNTERS ============================ --}}
    {{-- Shared with the home page — markup in partials/counters.blade.php --}}
    <section class="hm-stats" aria-label="Hire Minds Academy in numbers">
        <div class="container">
            @include('frontend.partials.counters')
        </div>
    </section>

    {{-- ============================ OUR PURPOSE ============================ --}}
    @php
        // Admin → Sections → About Us → Our Purpose. Both cards share one shape;
        // 'tone' picks the colour set in about.css.
        $purposeSection = $aboutSections['purpose'] ?? null;

        $purposeCards = collect($purposeSection?->items ?? [])->map(fn ($item) => [
            'tone'  => $item->tone,
            'title' => $item->title,
            'text'  => $item->text,
        ])->all();
    @endphp
    @if ($purposeSection && count($purposeCards))
    <section class="hm-purpose" id="our-purpose" aria-labelledby="hmPurposeTitle">

        {{-- Decorations — positions follow the reference --}}
        <div class="hm-purpose__deco" aria-hidden="true">
            <img class="hm-purpose__ring hm-purpose__ring--a" src="{{ asset('assets/images/about-page/component-3.png') }}" alt="" loading="lazy" decoding="async">
            <img class="hm-purpose__ring hm-purpose__ring--b" src="{{ asset('assets/images/about-page/component-3.png') }}" alt="" loading="lazy" decoding="async">
            <img class="hm-purpose__ring hm-purpose__ring--c" src="{{ asset('assets/images/about-page/component-3.png') }}" alt="" loading="lazy" decoding="async">
            <img class="hm-purpose__star hm-purpose__star--a" src="{{ asset('assets/images/about-page/star.png') }}" alt="" loading="lazy" decoding="async">
            <img class="hm-purpose__star hm-purpose__star--b" src="{{ asset('assets/images/about-page/element-star.png') }}" alt="" loading="lazy" decoding="async">
            <span class="hm-purpose__dots"></span>
        </div>

        <div class="container hm-purpose__container" data-abt-io>

            {{-- ------------------------------ Header ------------------------------ --}}
            <header class="hm-purpose__head">
                <span class="hm-purpose__label hm-abt-anim">
                    <span class="hm-purpose__label-sq" aria-hidden="true"></span> {{ $purposeSection->label }}
                </span>
                <h2 class="hm-purpose__title hm-abt-anim hm-abt-anim--d1" id="hmPurposeTitle">
                    {{ $purposeSection->title }}
                </h2>
                @if ($purposeSection->lead)
                    <p class="hm-purpose__lead hm-abt-anim hm-abt-anim--d2">
                        {{ $purposeSection->lead }}
                    </p>
                @endif
            </header>

            {{-- ---------------------- Image + Vision / Mission ----------------------
                 The alt text describes the slot rather than the file, so it still
                 reads correctly after the photo is swapped in the panel. --}}
            <div class="row g-4 align-items-stretch hm-purpose__body">
                @if ($purposeSection->image)
                <div class="col-lg-6">
                    <figure class="hm-purpose__figure hm-abt-anim hm-abt-anim--d1">
                        <img src="{{ asset($purposeSection->image) }}"
                             alt="A HireMinds Academy mentor guiding learners through a session in the training centre" loading="lazy" decoding="async">
                    </figure>
                </div>
                @endif

                <div class="{{ $purposeSection->image ? 'col-lg-6' : 'col-12' }}">
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
    @endif

    {{-- ============================ OUR FEATURES ============================ --}}
    @php
        // Admin → Sections → About Us → Our Features. 'tone' picks the colour set
        // in about.css; a card flagged for the eyes illustration carries it in
        // place of a description.
        $featureSection = $aboutSections['features'] ?? null;

        $features = collect($featureSection?->items ?? [])->map(fn ($item) => [
            'tone'  => $item->tone,
            'title' => $item->title,
            'text'  => $item->text,
            'eyes'  => $item->eyes,
        ])->all();
    @endphp
    @if ($featureSection && count($features))
    <section class="hm-feat" id="our-features" aria-labelledby="hmFeatTitle">

        {{-- Decorations --}}
        <div class="hm-feat__deco" aria-hidden="true">
            <img class="hm-feat__ring" src="{{ asset('assets/images/about-page/component-3.png') }}" alt="" loading="lazy" decoding="async">
            <img class="hm-feat__star" src="{{ asset('assets/images/about-page/star.png') }}" alt="" loading="lazy" decoding="async">
            <span class="hm-feat__dots"></span>
        </div>

        <div class="container hm-feat__container" data-abt-io>

            <header class="hm-feat__head">
                <span class="hm-feat__label hm-abt-anim">
                    <span class="hm-feat__label-sq" aria-hidden="true"></span> {{ $featureSection->label }}
                </span>
                <h2 class="hm-feat__title-main hm-abt-anim hm-abt-anim--d1" id="hmFeatTitle">
                    {{ $featureSection->title }}
                </h2>
                @if ($featureSection->lead)
                    <p class="hm-feat__lead hm-abt-anim hm-abt-anim--d2">
                        {{ $featureSection->lead }}
                    </p>
                @endif
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
    @endif

    {{-- ======================== OUR LEARNING APPROACH ======================== --}}
    @php
        // Admin → Sections → About Us → Our Approach. 'pos' places the pill around
        // the circle, 'tone' picks its pastel. Each drifts on its own delay so they
        // never move in lockstep.
        $approachSection = $aboutSections['approach'] ?? null;

        $approachPills = collect($approachSection?->items ?? [])->map(fn ($item) => [
            'pos'   => $item->position,
            'tone'  => $item->tone,
            'label' => $item->title,
        ])->all();
    @endphp
    @if ($approachSection)
    <section class="hm-appr" id="our-approach" aria-labelledby="hmApprTitle">

        {{-- Decorations --}}
        <div class="hm-appr__deco" aria-hidden="true">
            <img class="hm-appr__ring-deco" src="{{ asset('assets/images/about-page/component-3.png') }}" alt="" loading="lazy" decoding="async">
            <img class="hm-appr__star" src="{{ asset('assets/images/about-page/star.png') }}" alt="" loading="lazy" decoding="async">
            <span class="hm-appr__dots"></span>
        </div>

        <div class="container hm-appr__container" data-abt-io>

            <header class="hm-appr__head">
                <span class="hm-appr__label hm-abt-anim">
                    <span class="hm-appr__label-sq" aria-hidden="true"></span> {{ $approachSection->label }}
                </span>
                <h2 class="hm-appr__title hm-abt-anim hm-abt-anim--d1" id="hmApprTitle">
                    {{ $approachSection->title }}
                </h2>
                @if ($approachSection->lead)
                    <p class="hm-appr__lead hm-abt-anim hm-abt-anim--d2">
                        {{ $approachSection->lead }}
                    </p>
                @endif
            </header>

            {{-- Circular composition: concentric rings + centre photo + orbiting pills --}}
            <div class="hm-appr__stage hm-abt-anim hm-abt-anim--d3">

                {{-- Three concentric rings — the "learning ecosystem" --}}
                <span class="hm-appr__ring hm-appr__ring--1" aria-hidden="true"></span>
                <span class="hm-appr__ring hm-appr__ring--2" aria-hidden="true"></span>
                <span class="hm-appr__ring hm-appr__ring--3" aria-hidden="true"></span>

                {{-- our-approach.webp is a 9:16 portrait — a 1:1 circle can only keep
                     56% of its height, which sliced her head off. -square is the same
                     image padded out to 1080->1920 square, with the orange backdrop
                     extended sideways, so the circle crops nothing. --}}
                @if ($approachSection->image)
                <figure class="hm-appr__figure">
                    <img src="{{ asset($approachSection->image) }}"
                         alt="A HireMinds Academy learner working through a course on her laptop"
                         width="420" height="420" loading="lazy">
                </figure>
                @endif

                {{-- Pills — a real list, positioned around the circle --}}
                <ul class="hm-appr__pills">
                    @foreach ($approachPills as $pill)
                        <li @class(['hm-appr__pill', 'hm-appr__pill--'.$pill['pos']])>
                            {{-- Three layers keep the animations apart:
                                 li = placement · drift = float+orbit · chip = hover --}}
                            <span class="hm-appr__drift">
                                <span @class(['hm-appr__chip', 'hm-appr__chip--'.$pill['tone']])
                                      tabindex="0">{{ $pill['label'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
    @endif

    {{-- ============================ TESTIMONIALS ============================ --}}
    {{-- Shared with the home page — markup in partials/testimonials.blade.php --}}
    @include('frontend.partials.testimonials')

    {{-- ================================ FAQ ================================ --}}
    {{-- Shared with the home page — markup in partials/faq.blade.php --}}
    @include('frontend.partials.faq')

@endsection

@push('scripts')
    {{-- Bootstrap bundle — the shared FAQ partial's accordion is built on
         data-bs-toggle="collapse", so it is dead without this. --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>

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

            // Desktop: threshold 0.98 — hold the deck together until the cards
            // are *fully* in view, so the split never starts while they are
            // still half cut off at the bottom of the screen.
            //
            // Phones stack the four cards into a column far taller than the
            // screen, so that ratio is unreachable and the deck would never
            // open. There the pile sits on the first card, so watch for the top
            // of the track crossing into the lower third of the viewport
            // instead — the fan-out then plays exactly as the deck comes level.
            var phone = window.matchMedia('(max-width: 767.98px)').matches;

            new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { spread(); obs.disconnect(); }
                });
            }, phone
                ? { threshold: 0, rootMargin: '0px 0px -35% 0px' }
                : { threshold: 0.98 }
            ).observe(track);

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

            // Reduced motion / no GSAP: every chapter is just a stacked card (CSS
            // handles it) — nothing to drive.
            if (reduce || typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;

            gsap.registerPlugin(ScrollTrigger);

            {{-- Mobile browsers resize the viewport when the address bar slides
                 away, which fires a refresh mid-scroll and makes the pin jump.
                 Orientation changes still refresh. --}}
            ScrollTrigger.config({ ignoreMobileResize: true });

            /* ---- Pinned, scrubbed storytelling (every screen size) ---- */
            function initStory() {
                var small = window.matchMedia('(max-width: 767.98px)').matches;

                grid.classList.add('is-gsap');
                // The pin needs the class too: on phones it switches the section
                // from the stacked-card fallback to the pinned single column.
                pin.classList.add('is-gsap');

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
                        // Phones scroll a shorter distance for the same five
                        // hand-offs, so the story does not overstay its welcome.
                        // Function form re-reads on refresh (orientation change).
                        end: function () {
                            return '+=' + (window.matchMedia('(max-width: 767.98px)').matches ? 2200 : 3200);
                        },
                        pin: pin,
                        scrub: 1.5,
                        // Touch scrolling has no momentum to hide, so the early
                        // pin switch just reads as a jump on phones.
                        anticipatePin: small ? 0 : 1,
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
                    pin.classList.remove('is-gsap');
                    if (tl.scrollTrigger) tl.scrollTrigger.kill();
                    tl.kill();
                    gsap.set(cards.concat(copy), { clearProps: 'all' });
                };
            }

            // Every width — phones get the same storytelling, laid out in one
            // column (see the mobile .is-gsap rules in about.css). matchMedia is
            // kept so the timeline is rebuilt when the layout crosses the
            // breakpoint, which re-measures the pin at the new column widths.
            var mm = gsap.matchMedia();
            mm.add('(min-width: 768px)', initStory);
            mm.add('(max-width: 767.98px)', initStory);
        });
    </script>
@endpush
