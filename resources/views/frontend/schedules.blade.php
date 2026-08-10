@extends('frontend.layouts.template-base')

@section('title', 'Course Schedules — Hire Minds Academy')
@section('meta_description', 'Upcoming batch schedules at Hire Minds Academy — start dates, duration and fees for every course, so you can pick the batch that fits your plans.')

@push('styles')
    {{-- Poppins — the heading typeface used across the site's hero/banner blocks --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    {{-- courses.css carries the page shell and banner this page reuses, plus the
         .hm-course__badge and .hm-course__btn the table borrows. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/courses.css') }}?v={{ filemtime(public_path('assets/css/frontend/courses.css')) }}">
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/schedules.css') }}?v={{ filemtime(public_path('assets/css/frontend/schedules.css')) }}">
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/enquiry-modal.css') }}?v={{ filemtime(public_path('assets/css/frontend/enquiry-modal.css')) }}">
@endpush

@section('content')

    {{-- .hm-crs-page is the gate the page-shell CSS keys off (see the :has() note
         in courses.css) — the banner below needs it to sit in the same frame the
         courses page uses. --}}
    <div class="hm-crs-page">
        <div class="container">

            {{-- ============================== BANNER ==============================
                 The courses page's banner, same decorations and cut-out. --}}
            <section class="hm-crs-hero" aria-labelledby="hmSchedHeroTitle">

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
                            <li aria-current="page">Schedules</li>
                        </ol>
                    </nav>

                    <h1 class="hm-crs-hero__title" id="hmSchedHeroTitle">Upcoming Course Schedules</h1>

                    <p class="hm-crs-hero__desc">
                        Explore upcoming batches and choose the right course schedule for your
                        learning journey.
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

            {{-- ============================== TABLE ============================== --}}
            <section class="hm-sched hm-sched--page" aria-labelledby="hmSchedListTitle">
                <h2 class="visually-hidden" id="hmSchedListTitle">All upcoming batches</h2>

                @if ($schedules->count())
                    @php
                        // Filter options built from the batches actually listed, so
                        // neither dropdown can offer a choice that matches nothing.
                        $categoryOptions = $schedules
                            ->map(fn ($s) => $s->course->category)
                            ->filter()
                            ->unique('id')
                            ->sortBy('name')
                            ->values();

                        $monthOptions = $schedules
                            ->map(fn ($s) => [
                                'value' => $s->start_date->format('Y-m'),
                                'label' => $s->start_date->format('F Y'),
                            ])
                            ->unique('value')
                            ->sortBy('value')
                            ->values();
                    @endphp

                    {{-- ============================ TOOLBAR ============================
                         The courses page's search + filter bar, same components and
                         behaviour — every batch is already on the page, so filtering
                         happens here rather than round-tripping to the server. --}}
                    <div class="hm-crs-bar hm-sched__bar">
                        <div class="hm-crs-bar__filters">
                            <div class="hm-crs-bar__search">
                                <label class="visually-hidden" for="hmSchedSearch">Search batches by course or category</label>
                                <input class="hm-crs-bar__input"
                                       id="hmSchedSearch"
                                       type="search"
                                       name="q"
                                       placeholder='Search "Ethical Hacking"'
                                       autocomplete="off">
                                <img class="hm-crs-bar__icon"
                                     src="{{ asset('assets/images/blog/search.png') }}"
                                     alt="" aria-hidden="true">
                            </div>

                            {{-- Category --}}
                            <div class="hm-crs-cat" data-hm-filter="category">
                                <button class="hm-crs-cat__btn" type="button"
                                        aria-expanded="false" aria-haspopup="true" aria-controls="hmSchedCatMenu">
                                    <img class="hm-crs-cat__ico" src="{{ asset('assets/images/blog/menu.png') }}" alt="" aria-hidden="true">
                                    <span class="hm-crs-cat__label">Categories</span>
                                    <i class="fa-solid fa-chevron-down hm-crs-cat__caret" aria-hidden="true"></i>
                                </button>

                                <div class="hm-crs-cat__menu" id="hmSchedCatMenu" role="group" aria-label="Filter by category">
                                    <div class="hm-crs-cat__col">
                                        <label class="hm-crs-cat__opt hm-crs-cat__opt--bold">
                                            <input type="checkbox" class="hm-crs-cat__cb" value="all" data-hm-filter-all>
                                            <span>All Categories</span>
                                        </label>
                                        @foreach ($categoryOptions as $category)
                                            <label class="hm-crs-cat__opt">
                                                <input type="checkbox" class="hm-crs-cat__cb" value="{{ $category->slug }}">
                                                <span>{{ $category->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- Starting month — the dimension a schedule listing is
                                 actually browsed by. --}}
                            <div class="hm-crs-cat" data-hm-filter="month">
                                <button class="hm-crs-cat__btn" type="button"
                                        aria-expanded="false" aria-haspopup="true" aria-controls="hmSchedMonthMenu">
                                    <i class="fa-regular fa-calendar hm-crs-cat__ico" aria-hidden="true"></i>
                                    <span class="hm-crs-cat__label">Starting</span>
                                    <i class="fa-solid fa-chevron-down hm-crs-cat__caret" aria-hidden="true"></i>
                                </button>

                                <div class="hm-crs-cat__menu" id="hmSchedMonthMenu" role="group" aria-label="Filter by starting month">
                                    <div class="hm-crs-cat__col">
                                        <label class="hm-crs-cat__opt hm-crs-cat__opt--bold">
                                            <input type="checkbox" class="hm-crs-cat__cb" value="all" data-hm-filter-all>
                                            <span>Any Month</span>
                                        </label>
                                        @foreach ($monthOptions as $month)
                                            <label class="hm-crs-cat__opt">
                                                <input type="checkbox" class="hm-crs-cat__cb" value="{{ $month['value'] }}">
                                                <span>{{ $month['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="hm-crs-bar__count" id="hmSchedCount" role="status">
                            {{ $schedules->count() }} upcoming {{ Str::plural('batch', $schedules->count()) }}
                        </p>
                    </div>

                    {{-- Shared with the home page — markup in
                         partials/schedule-table.blade.php, CSS in schedules.css. --}}
                    @include('frontend.partials.schedule-table', ['schedules' => $schedules])

                    {{-- Shown instead of the table when nothing matches the filters. --}}
                    <p class="hm-sched__noresults" id="hmSchedNoResults" hidden>
                        No batches match your search. Try a different course name, or clear the filters.
                    </p>
                @else
                    <div class="hm-sched__empty">
                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                        <h2>No batches scheduled just yet</h2>
                        <p>
                            New batches are announced regularly. Browse the courses in the meantime,
                            or get in touch and we will let you know as soon as one opens.
                        </p>
                        <a class="hm-btn hm-btn--primary" href="{{ route('frontend.courses') }}">
                            <span class="hm-btn__label">Explore Courses <i class="fa-solid fa-chevron-right"></i></span>
                        </a>
                    </div>
                @endif
            </section>
        </div>
    </div>

    {{-- The Apply buttons open this — the same enquiry modal the course-details
         page uses, with the batch named on the form. --}}
    @include('frontend.partials.course-enquiry-modal')

@endsection

@push('scripts')
    {{-- Bootstrap bundle — drives the enquiry modal, including its backdrop,
         focus trap, ESC and click-outside. The modal is dead without this. --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>

    {{-- Search + filters. Same behaviour as the courses page's toolbar, over the
         table rows instead of the card grid: a row shows when it matches the
         search text AND every dropdown that has a selection. No dependency. --}}
    <script>
        (function () {
            'use strict';

            var search  = document.getElementById('hmSchedSearch');
            var rows    = Array.prototype.slice.call(document.querySelectorAll('.hm-sched__row'));
            var count   = document.getElementById('hmSchedCount');
            var panel   = document.querySelector('.hm-sched__panel');
            var none    = document.getElementById('hmSchedNoResults');
            var total   = rows.length;

            if (!rows.length) return;

            // One entry per dropdown: its root, its label, its boxes and which
            // data-* attribute on a row it matches against.
            var filters = Array.prototype.slice.call(document.querySelectorAll('[data-hm-filter]'))
                .map(function (root) {
                    return {
                        root:    root,
                        key:     root.getAttribute('data-hm-filter'),
                        attr:    'data-' + root.getAttribute('data-hm-filter'),
                        btn:     root.querySelector('.hm-crs-cat__btn'),
                        label:   root.querySelector('.hm-crs-cat__label'),
                        title:   root.querySelector('.hm-crs-cat__label').textContent,
                        boxes:   Array.prototype.slice.call(root.querySelectorAll('.hm-crs-cat__cb')),
                        allBox:  root.querySelector('[data-hm-filter-all]')
                    };
                });

            function closeAll(except) {
                filters.forEach(function (f) {
                    if (f.root === except) return;
                    f.root.classList.remove('is-open');
                    f.btn.setAttribute('aria-expanded', 'false');
                });
            }

            /** The values ticked in one dropdown, ignoring its "all" option. */
            function chosenIn(filter) {
                return filter.boxes
                    .filter(function (b) { return b.checked && b.value !== 'all'; })
                    .map(function (b) { return b.value; });
            }

            function apply() {
                var term  = (search && search.value ? search.value : '').trim().toLowerCase();
                var picks = filters.map(function (f) { return { f: f, values: chosenIn(f) }; });
                var shown = 0;

                rows.forEach(function (row) {
                    // Search runs over what the row actually prints — the course
                    // name and its category chip — so it matches what is on screen.
                    var name = row.querySelector('.hm-sched__course-name');
                    var chip = row.querySelector('.hm-course__badge');
                    var text = ((name ? name.textContent : '') + ' ' + (chip ? chip.textContent : '')).toLowerCase();

                    var match = (term === '' || text.indexOf(term) !== -1)
                        && picks.every(function (p) {
                            return p.values.length === 0
                                || p.values.indexOf(row.getAttribute(p.f.attr)) !== -1;
                        });

                    row.hidden = !match;
                    if (match) shown++;
                });

                // Each button names its own selection.
                picks.forEach(function (p) {
                    if (p.values.length === 0) {
                        p.f.label.textContent = p.f.title;
                        p.f.root.classList.remove('is-filtered');
                    } else if (p.values.length === 1) {
                        var one = p.f.boxes.filter(function (b) { return b.value === p.values[0]; })[0];
                        p.f.label.textContent = one ? one.parentNode.querySelector('span').textContent : p.f.title;
                        p.f.root.classList.add('is-filtered');
                    } else {
                        p.f.label.textContent = p.values.length + ' selected';
                        p.f.root.classList.add('is-filtered');
                    }
                });

                if (count) {
                    count.textContent = shown === total
                        ? total + ' upcoming batch' + (total === 1 ? '' : 'es')
                        : 'Showing ' + shown + ' of ' + total + ' batches';
                }

                // An empty table with a header row reads as broken, so the whole
                // panel steps aside for the message.
                if (panel) panel.hidden = shown === 0;
                if (none)  none.hidden  = shown !== 0;
            }

            filters.forEach(function (f) {
                f.btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var isOpen = f.root.classList.contains('is-open');
                    closeAll(f.root);
                    f.root.classList.toggle('is-open', !isOpen);
                    f.btn.setAttribute('aria-expanded', String(!isOpen));
                });

                f.boxes.forEach(function (box) {
                    box.addEventListener('change', function () {
                        if (box === f.allBox && box.checked) {
                            // "All" / "Any" clears every other choice in this dropdown.
                            f.boxes.forEach(function (b) { if (b !== f.allBox) b.checked = false; });
                        } else if (box.checked && f.allBox) {
                            f.allBox.checked = false;   // any real pick releases it
                        }
                        apply();
                    });
                });
            });

            if (search) search.addEventListener('input', apply);

            // Close on outside click / Escape.
            document.addEventListener('click', function (e) {
                var inside = filters.some(function (f) { return f.root.contains(e.target); });
                if (!inside) closeAll(null);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                var open = filters.filter(function (f) { return f.root.classList.contains('is-open'); })[0];
                if (open) { closeAll(null); open.btn.focus(); }
            });

            apply();
        })();
    </script>
@endpush
