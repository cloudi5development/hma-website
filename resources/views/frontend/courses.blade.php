@extends('frontend.layouts.template-base')

@section('title', 'Courses — Hire Minds Academy')
@section('meta_description', 'Explore career-focused technical training programs at Hire Minds Academy — built to help you gain practical skills, confidence, and an edge in a competitive job market.')

@push('styles')
    {{-- Poppins — the heading typeface used across the site's hero/banner blocks --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    {{-- courses.css carries BOTH the shared course card and this page's own
         styles. ?v=<file mtime> busts the browser cache whenever it changes, so
         edits are never masked by a stale copy. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/courses.css') }}?v={{ filemtime(public_path('assets/css/frontend/courses.css')) }}">
@endpush

@section('content')

    @php
        // ------------------------------------------------------------------
        // Listing data. Swap for the paginator once the Course model lands —
        // the card partial only reads these keys, so nothing else changes.
        // Thumbnails are composed images in assets/images/courses/.
        // ------------------------------------------------------------------
        // 'category' + 'group' + 'top' drive the category filter (see the toolbar
        // below and the script at the foot). They map straight onto the Course
        // model's category when the admin lands.
        $thumbs = [
            ['img' => 'course-1.webp', 'badge' => 'Development',      'slug' => 'learning-javascript-development', 'category' => 'it-software',   'group' => 'technical',     'top' => true],
            ['img' => 'course-4.webp', 'badge' => 'Career Readiness', 'slug' => 'learning-javascript-career',      'category' => 'business',      'group' => 'non-technical', 'top' => false],
            ['img' => 'course-3.webp', 'badge' => 'Team Leadership',  'slug' => 'learning-javascript-leadership',  'category' => 'engineering',   'group' => 'technical',     'top' => false],
            ['img' => 'course-2.webp', 'badge' => 'Corporate',        'slug' => 'learning-javascript-corporate',   'category' => 'cloud-devops',  'group' => 'technical',     'top' => true],
        ];

        $courses = [];

        for ($i = 0; $i < 12; $i++) {
            $thumb = $thumbs[$i % count($thumbs)];

            $courses[] = [
                'img'         => $thumb['img'],
                'badge'       => $thumb['badge'],
                'title'       => 'Learning JavaScript With Imagination',
                'rating'      => '4.5',
                'duration'    => '3 months',
                'mode'        => 'On-Campus Learning',
                'certificate' => 'Industry Certificate',
                'url'         => route('frontend.course-details', $thumb['slug']),
                'category'    => $thumb['category'],
                'group'       => $thumb['group'],
                'top'         => $thumb['top'],
            ];
        }

        // Category filter options, laid out in the Figma's two columns. 'bold'
        // marks the group headers; 'value' is what a card's data-category /
        // data-group / data-top is matched against.
        $catColumns = [
            [
                ['label' => 'All Categories', 'value' => 'all',           'bold' => false],
                ['label' => 'Technical',      'value' => 'technical',     'bold' => true],
                ['label' => 'IT & Software',  'value' => 'it-software',   'bold' => false],
                ['label' => 'Cloud & DevOps', 'value' => 'cloud-devops',  'bold' => false],
                ['label' => 'Data & AI',      'value' => 'data-ai',       'bold' => false],
                ['label' => 'Top Courses',    'value' => 'top-courses',   'bold' => true],
            ],
            [
                ['label' => 'Cyber Security', 'value' => 'cyber-security', 'bold' => false],
                ['label' => 'Engineering',    'value' => 'engineering',    'bold' => false],
                ['label' => 'Non - Technical','value' => 'non-technical',  'bold' => true],
                ['label' => 'Business',       'value' => 'business',       'bold' => false],
                ['label' => 'Industry',       'value' => 'industry',       'bold' => false],
            ],
        ];

        // Result counter — derived, so it stays honest if the array changes.
        $total = 48;
        $from  = 1;
        $to    = count($courses);
    @endphp

    {{-- .hm-crs-page is also the gate the page-shell CSS keys off (see the
         :has() note in courses.css) — renaming it would strip the page back to
         the layout's default centred column. --}}
    <div class="hm-crs-page">
        <div class="container">

            {{-- ============================== HERO ============================== --}}
            <section class="hm-crs-hero" aria-labelledby="hmCrsHeroTitle">

                {{-- Decorations — the dotted panel and the floating circles. --}}
                <img class="hm-crs-hero__deco hm-crs-hero__deco--dots" aria-hidden="true"
                     src="{{ asset('assets/images/courses/dots.png') }}" alt="" loading="lazy">
                <img class="hm-crs-hero__deco hm-crs-hero__deco--c1" aria-hidden="true"
                     src="{{ asset('assets/images/courses/circle.png') }}" alt="" loading="lazy">
                <img class="hm-crs-hero__deco hm-crs-hero__deco--c2" aria-hidden="true"
                     src="{{ asset('assets/images/courses/circle.png') }}" alt="" loading="lazy">
                <img class="hm-crs-hero__deco hm-crs-hero__deco--c3" aria-hidden="true"
                     src="{{ asset('assets/images/courses/circle.png') }}" alt="" loading="lazy">

                <div class="hm-crs-hero__body">
                    <nav aria-label="Breadcrumb">
                        <ol class="hm-crs-hero__crumbs">
                            <li><a href="{{ route('frontend.index') }}">Home</a></li>
                            <li class="hm-crs-hero__crumb-sep" aria-hidden="true">&rsaquo;</li>
                            <li aria-current="page">IT &amp; Software</li>
                        </ol>
                    </nav>

                    <h1 class="hm-crs-hero__title" id="hmCrsHeroTitle">Explore Technical Training Programs</h1>

                    <p class="hm-crs-hero__desc">
                        Choose from career-focused technical courses designed to help you build
                        practical skills, gain confidence, and stay ahead in a competitive job market.
                    </p>

                    <a class="hm-crs-hero__cta" href="{{ route('frontend.contact-us') }}">
                        <span>Let's Connect</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>

                {{-- Transparent cut-out, bottom-aligned against the banner edge. --}}
                <figure class="hm-crs-hero__figure">
                    <img src="{{ asset('assets/images/courses/hero-course.webp') }}"
                         alt="" role="presentation" loading="lazy">
                </figure>
            </section>

            {{-- ============================= TOOLBAR ============================= --}}
            <div class="hm-crs-bar">
                <div class="hm-crs-bar__filters">
                    <div class="hm-crs-bar__search">
                        <label class="visually-hidden" for="hmCrsSearch">Search courses</label>
                        <input class="hm-crs-bar__input"
                               id="hmCrsSearch"
                               type="search"
                               name="q"
                               placeholder='Search "Design"'
                               autocomplete="off">
                        <img class="hm-crs-bar__icon"
                             src="{{ asset('assets/images/blog/search.png') }}"
                             alt="" aria-hidden="true">
                    </div>

                    {{-- Categories filter. The label swaps to the chosen category
                         (or "N Categories"); the panel filters the grid live. --}}
                    <div class="hm-crs-cat" data-hm-cat>
                        <button class="hm-crs-cat__btn"
                                type="button"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-controls="hmCrsCatMenu">
                            <img class="hm-crs-cat__ico"
                                 src="{{ asset('assets/images/blog/menu.png') }}"
                                 alt="" aria-hidden="true">
                            <span class="hm-crs-cat__label">Categories</span>
                            <i class="fa-solid fa-chevron-down hm-crs-cat__caret" aria-hidden="true"></i>
                        </button>

                        <div class="hm-crs-cat__menu" id="hmCrsCatMenu" role="group" aria-label="Filter by category">
                            @foreach ($catColumns as $column)
                                <div class="hm-crs-cat__col">
                                    @foreach ($column as $opt)
                                        <label @class(['hm-crs-cat__opt', 'hm-crs-cat__opt--bold' => $opt['bold']])>
                                            <input type="checkbox" class="hm-crs-cat__cb"
                                                   value="{{ $opt['value'] }}"
                                                   @if ($opt['value'] === 'all') data-hm-cat-all @endif>
                                            <span>{{ $opt['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <p class="hm-crs-bar__count" id="hmCrsCount">Showing {{ $from }}&ndash;{{ $to }} of {{ $total }} Results</p>
            </div>

            {{-- =============================== GRID =============================== --}}
            <section aria-label="Courses">
                <div class="row hm-crs-grid">
                    @foreach ($courses as $i => $course)
                        {{-- data-* are what the category filter matches against —
                             on the col wrapper so the shared card partial stays
                             untouched. --}}
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3 hm-crs-item"
                             data-category="{{ $course['category'] }}"
                             data-group="{{ $course['group'] }}"
                             @if ($course['top']) data-top="1" @endif>
                            {{-- Shared with the home page's Popular Courses grid —
                                 markup in partials/course-card.blade.php. --}}
                            @include('frontend.partials.course-card', ['course' => $course, 'i' => $i])
                        </div>
                    @endforeach
                </div>
            </section>

        </div>
    </div>
@endsection

@push('scripts')
    {{-- Categories filter — open/close the panel, swap the button label to the
         chosen category, and filter the grid live. No dependency. --}}
    <script>
        (function () {
            'use strict';

            var root = document.querySelector('[data-hm-cat]');
            if (!root) return;

            var btn    = root.querySelector('.hm-crs-cat__btn');
            var label  = root.querySelector('.hm-crs-cat__label');
            var boxes  = Array.prototype.slice.call(root.querySelectorAll('.hm-crs-cat__cb'));
            var allBox = root.querySelector('[data-hm-cat-all]');
            var cards  = Array.prototype.slice.call(document.querySelectorAll('.hm-crs-item'));
            var count  = document.getElementById('hmCrsCount');
            var total  = 48;   // keep the design's total; only the shown figure moves

            function close() { root.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); }
            function open()  { root.classList.add('is-open');    btn.setAttribute('aria-expanded', 'true'); }

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                root.classList.contains('is-open') ? close() : open();
            });

            // A card shows when its category, its group, or its "top" flag is in
            // the chosen set. Empty set (or "All Categories") shows everything.
            function apply() {
                var chosen = boxes
                    .filter(function (b) { return b.checked && b.value !== 'all'; })
                    .map(function (b) { return b.value; });

                var shown = 0;

                cards.forEach(function (card) {
                    var match = chosen.length === 0
                        || chosen.indexOf(card.getAttribute('data-category')) !== -1
                        || chosen.indexOf(card.getAttribute('data-group')) !== -1
                        || (chosen.indexOf('top-courses') !== -1 && card.getAttribute('data-top') === '1');

                    card.hidden = !match;
                    if (match) shown++;
                });

                // Button label reflects the selection.
                if (chosen.length === 0) {
                    label.textContent = 'Categories';
                    root.classList.remove('is-filtered');
                } else if (chosen.length === 1) {
                    var one = boxes.filter(function (b) { return b.value === chosen[0]; })[0];
                    label.textContent = one ? one.parentNode.querySelector('span').textContent : 'Categories';
                    root.classList.add('is-filtered');
                } else {
                    label.textContent = chosen.length + ' Categories';
                    root.classList.add('is-filtered');
                }

                if (count) count.innerHTML = 'Showing ' + shown + ' of ' + total + ' Results';
            }

            boxes.forEach(function (box) {
                box.addEventListener('change', function () {
                    if (box === allBox && box.checked) {
                        // "All Categories" clears every other choice.
                        boxes.forEach(function (b) { if (b !== allBox) b.checked = false; });
                    } else if (box.checked && allBox) {
                        allBox.checked = false;   // any real pick releases "All"
                    }
                    apply();
                });
            });

            // Close on outside click / Escape.
            document.addEventListener('click', function (e) { if (!root.contains(e.target)) close(); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && root.classList.contains('is-open')) { close(); btn.focus(); }
            });
        })();
    </script>
@endpush
