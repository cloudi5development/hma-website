@extends('frontend.layouts.template-base')

{{-- The data block sits above @section('title'): Blade runs the file top-down,
     so $course has to exist before the title reads from it. --}}
@php
    // ----------------------------------------------------------------------
    // $course arrives from HomeController@courseDetails as an Eloquent model.
    // It is mapped here into the exact array shape the markup already reads, so
    // the layout is untouched while every value is now database-driven. Fields
    // the design has no slot for (student count, placement label) keep their
    // original copy so the hero/stat cards stay visually identical.
    // ----------------------------------------------------------------------
    $model = $course;

    // The audience reads as chips, so the stored text is broken into
    // its parts: one per line, as the admin form invites. A single line of short
    // comma-separated items ("Freshers, Graduates") splits on the commas too,
    // while a prose sentence is left whole rather than chopped into fragments —
    // a chip is a label of a few words, so a clause longer than that is the
    // tell that the admin wrote a sentence and not a list.
    $audienceFor = collect(preg_split('/\r\n|\r|\n/', (string) $model->audience))
        ->map(fn ($line) => trim($line))
        ->filter()
        ->values();

    if ($audienceFor->count() === 1 && str_contains($audienceFor[0], ',')) {
        $commaParts = collect(explode(',', $audienceFor[0]))
            ->map(fn ($part) => trim($part))
            ->filter()
            ->values();

        if ($commaParts->every(fn ($part) => mb_strlen($part) <= 28 && count(preg_split('/\s+/', $part)) <= 4)) {
            $audienceFor = $commaParts;
        }
    }

    $audienceFor = $audienceFor->all();

    $course = [
        'slug'        => $model->slug,
        'title'       => $model->name,
        'description' => $model->short_description ?: $model->overview,
        'image'       => $model->image_url,
        // The dates and the mode come off the soonest upcoming batch (Admin →
        // Courses → Schedule), not off the course row — the course carried a
        // single batch_start_date and training_mode of its own until 2026-08-19,
        // which routinely disagreed with the batches actually scheduled. Null
        // when nothing is scheduled, and every slot below copes with that.
        'date'        => $model->batch_range_label,
        'datetime'    => $model->batch_start_iso,
        'students'    => '2,250 Students',
        'duration'    => $model->duration,
        'mode'        => $model->training_mode,
        'level'       => $model->skill_level,
        'certificate' => 'Industry Recognized',
        'placement'   => '100% Support',
        // Null when no brochure has been uploaded — the button is then not rendered.
        'brochure_url' => $model->has_brochure ? route('frontend.course-brochure', $model->slug) : null,
        'about'       => $model->overview ?: $model->full_description ?: $model->short_description,
        // Who the course is for (Admin → Courses → Audience) — see $audienceFor
        // above. Empty on every course that predates the field, and the section
        // is then skipped entirely.
        'audience'    => $audienceFor,
    ];
@endphp

{{-- Block sections, not the one-line @section('title', $value) form. That form
     stores e($value) and the layout escapes again when it yields, so an "&" in
     a course name reaches the browser as "&amp;"; and it only skips ob_start()
     when the value is not null, so a course with no meta description and no
     description left an output buffer open on every request. A block stores the
     raw text and the layout escapes it exactly once; the guard keeps a blank
     field falling through to Settings → SEO Defaults instead of overriding them
     with an empty string. --}}
@php $metaDescription = $model->meta_description ?: $course['description']; @endphp

@section('title'){!! $model->meta_title ?: $course['title'] . ' — Hire Minds Academy' !!}@endsection

@if (filled($metaDescription))
    @section('meta_description'){!! $metaDescription !!}@endsection
@endif

@push('styles')
    {{-- Poppins — the heading typeface used across the site's hero/banner blocks --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">

    {{-- Reused components. course-details.css goes LAST: it fits the FAQ and the
         contact section to this page by overriding rules of equal specificity,
         which only works on load order. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/courses.css') }}?v={{ filemtime(public_path('assets/css/frontend/courses.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/faq.css') }}?v={{ filemtime(public_path('assets/css/frontend/faq.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/contact-form.css') }}?v={{ filemtime(public_path('assets/css/frontend/contact-form.css')) }}">
    {{-- The enquiry modal, shared with the schedules table on the home page and
         /schedules. Its rules used to live at the bottom of course-details.css. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/enquiry-modal.css') }}?v={{ filemtime(public_path('assets/css/frontend/enquiry-modal.css')) }}">
    {{-- "Upcoming Batches" is the same table as the home page's schedules
         section, in its compact five-column form. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/schedules.css') }}?v={{ filemtime(public_path('assets/css/frontend/schedules.css')) }}">
    {{-- ?v=<file mtime> busts the browser cache whenever course-details.css
         changes, so edits are never masked by a stale copy. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/course-details.css') }}?v={{ filemtime(public_path('assets/css/frontend/course-details.css')) }}">
@endpush

@php
    // Icons for this page come from assets/images/icons-details/ — white outline
    // glyphs sized for the 34px coloured tiles. 'icon' is the path under
    // assets/images/; 'fa' stays supported as a fallback for any row that has no
    // asset. The folder holds 11 distinct glyphs for these 13 slots, so the crown
    // (icon-12) covers both "Skill Level" and "Deployment & Portfolio Building",
    // and icon-3 / icon-10 are the same handshake file.
    $features = [
        ['value' => $course['duration'],    'label' => 'Duration',    'tone' => 'pink',  'icon' => 'icons-details/icon-1.png'],   // clock
        ['value' => $course['mode'],        'label' => 'Mode',        'tone' => 'blue',  'icon' => 'icons-details/icon-2.png'],   // campus
        ['value' => $course['level'],       'label' => 'Skill Level', 'tone' => 'red',   'icon' => 'icons-details/icon-12.png'],  // crown
        ['value' => $course['certificate'], 'label' => 'Certificate', 'tone' => 'green', 'icon' => 'icons-details/icon-4.png'],   // rosette + tick
        ['value' => $course['placement'],   'label' => 'Placement',   'tone' => 'gold',  'icon' => 'icons-details/icon-5.png'],   // briefcase
    ];

    // Mode is the batch's now, so a course with nothing scheduled has none. A
    // tile with a label and no value looks broken, so it is left out and the
    // strip closes up around it.
    $features = array_values(array_filter($features, fn ($f) => filled($f['value'])));

    // Highlight cards — the tone drives both the pastel card and its icon tile.
    $highlights = [
        ['title' => 'Expert-Led Training',             'tone' => 'red',    'icon' => 'icons-details/icon-6.png'],   // person + star
        ['title' => 'Hands-On Live Projects',          'tone' => 'purple', 'icon' => 'icons-details/icon-7.png'],   // project folder
        ['title' => 'Industry-Focused Curriculum',     'tone' => 'teal',   'icon' => 'icons-details/icon-8.png'],   // telescope
        ['title' => 'Certification',                   'tone' => 'pink',   'icon' => 'icons-details/icon-9.png'],   // shield + tick
        ['title' => 'Placement Assistance',            'tone' => 'blue',   'icon' => 'icons-details/icon-3.png'],   // handshake
        ['title' => 'Resume & Interview Support',      'tone' => 'gold',   'icon' => 'icons-details/icon-11.png'],  // speech bubbles
        ['title' => 'Dedicated Mentor Support',        'tone' => 'peach',  'icon' => 'icons-details/icon-10.png'],  // handshake
        ['title' => 'Deployment & Portfolio Building', 'tone' => 'green',  'icon' => 'icons-details/icon-12.png'],  // crown
    ];

    $skills = [
        ['title' => 'Frontend Mastery',       'desc' => 'Advanced HTML5, CSS3 Grid/Flexbox, and modern JavaScript (ES6+) for responsive, accessible interfaces.'],
        ['title' => 'Backend Systems',        'desc' => 'Build robust APIs and server-side logic using Node.js and Express with secure authentication.'],
        ['title' => 'Database Design',        'desc' => 'Master both SQL (PostgreSQL) and NoSQL (MongoDB) for efficient data storage and retrieval.'],
        ['title' => 'Cloud & DevOps',         'desc' => 'Deploy apps using Docker, AWS, and CI/CD pipelines to ensure 99.9% uptime and scalability.'],
        ['title' => 'System Architecture',    'desc' => 'Learn Microservices vs Monoliths, Caching strategies, and Design Patterns for large scale apps.'],
        ['title' => 'Security Best Practices','desc' => 'Implement JWT, OAuth2, and CORS to protect user data and prevent common vulnerabilities.'],
    ];

    // "Continue Your Learning Journey" — the flagged courses from the controller,
    // mapped to the shape the shared card partial reads.
    $relatedCourses = collect($continueLearning ?? [])->map(fn ($c) => [
        'img_url'     => $c->image_url,
        'badge'       => $c->badge,
        'title'       => $c->name,
        'rating'      => $c->rating,
        'duration'    => $c->duration,
        'mode'        => $c->training_mode,
        'certificate' => 'Industry Certificate',
        'url'         => route('frontend.course-details', $c->slug),
    ])->all();
@endphp

@section('content')

    <div class="hm-cd">
        <div class="container">

            {{-- ============================ BREADCRUMB ============================ --}}
            <nav aria-label="Breadcrumb">
                <ol class="hm-cd__crumbs">
                    <li><a href="{{ route('frontend.index') }}">Home</a></li>
                    <li class="hm-cd__crumb-sep" aria-hidden="true">&rsaquo;</li>
                    <li><a href="{{ route('frontend.courses') }}">Courses</a></li>
                    <li class="hm-cd__crumb-sep" aria-hidden="true">&rsaquo;</li>
                    <li><a href="{{ route('frontend.courses', ['category' => $model->category?->slug]) }}">{{ $model->category?->name }}</a></li>
                    <li class="hm-cd__crumb-sep" aria-hidden="true">&rsaquo;</li>
                    <li aria-current="page">{{ $course['title'] }}</li>
                </ol>
            </nav>

            {{-- =============================== HERO =============================== --}}
            <section class="row hm-cd-hero" aria-labelledby="hmCdTitle">

                <div class="col-12 col-lg-6">
                    <span class="hm-cd-hero__label">
                        <span class="hm-cd-hero__label-icon" aria-hidden="true"></span>
                        Top Rated Course
                    </span>

                    <h1 class="hm-cd-hero__title" id="hmCdTitle">{{ $course['title'] }}</h1>

                    <p class="hm-cd-hero__desc">{{ $course['description'] }}</p>

                    <ul class="hm-cd-hero__meta">
                        {{-- The next batch's dates. Dropped entirely when the course
                             has no upcoming batch — an empty <time> next to a
                             calendar icon reads as a page that failed to load. --}}
                        @if ($course['date'])
                            <li class="hm-cd-hero__meta-item">
                                {{-- No calendar icon ships in courses/, so this reuses the
                                     blog one rather than inventing a placeholder. --}}
                                <img src="{{ asset('assets/images/blog/calendar.png') }}" alt="" aria-hidden="true">
                                <time datetime="{{ $course['datetime'] }}">{{ $course['date'] }}</time>
                            </li>
                        @endif
                        <li class="hm-cd-hero__meta-item">
                            <img src="{{ asset('assets/images/courses/school.png') }}" alt="" aria-hidden="true">
                            {{ $course['students'] }}
                        </li>
                    </ul>

                    <div class="hm-cd-hero__actions">
                        {{-- Opens the enquiry modal at the bottom of this file. A real
                             <button> so it is keyboard-operable and announced correctly;
                             Bootstrap's JS bundle is already loaded for the FAQ. --}}
                        <button class="hm-cd-btn hm-cd-btn--primary"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#hmEnquireModal">
                            <span>Enroll Now</span>
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </button>
                        {{-- Brochure. The button is always part of this pair, so it
                             renders either way: as a download link once a PDF has
                             been uploaded (Admin → Courses → Course Brochure), and
                             otherwise inert — dimmed and non-clickable rather than a
                             link that would 404. --}}
                        @if ($course['brochure_url'])
                            <a class="hm-cd-btn hm-cd-btn--ghost"
                               href="{{ $course['brochure_url'] }}"
                               download
                               aria-label="Download the {{ $course['title'] }} brochure (PDF)">
                                <span>Brochure</span>
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                            </a>
                        @else
                            <span class="hm-cd-btn hm-cd-btn--ghost is-disabled"
                                  role="link"
                                  aria-disabled="true"
                                  title="The brochure for this course is not available yet.">
                                <span>Brochure</span>
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                            </span>
                        @endif
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    {{-- The orange composition, its icon tile, the pills and the figure
                         are all baked into this one asset. --}}
                    <figure class="hm-cd-hero__figure">
                        <img src="{{ $course['image'] }}"
                             alt="{{ $course['title'] }}" loading="lazy">
                    </figure>
                </div>
            </section>

            {{-- ============================= FEATURES ============================= --}}
            <ul class="hm-cd-feats" aria-label="Course at a glance">
                @foreach ($features as $feature)
                    <li class="hm-cd-feat hm-cd-feat--{{ $feature['tone'] }}">
                        <span class="hm-cd-feat__icon" aria-hidden="true">
                            @isset($feature['icon'])
                                <img src="{{ asset('assets/images/' . $feature['icon']) }}"
                                     alt="" width="17" height="17" loading="lazy" decoding="async">
                            @else
                                <i class="{{ $feature['fa'] }}"></i>
                            @endisset
                        </span>
                        <div>
                            <p class="hm-cd-feat__title">{{ $feature['value'] }}</p>
                            <p class="hm-cd-feat__sub">{{ $feature['label'] }}</p>
                        </div>
                    </li>
                @endforeach   
            </ul>

            {{-- ============================== ABOUT ============================== --}}
            <section class="hm-cd-sec" aria-labelledby="hmCdAbout">
                <h2 class="hm-cd-sec__title" id="hmCdAbout">About the Course</h2>
                <p class="hm-cd-sec__desc hm-cd-sec__desc--about">{{ $course['about'] }}</p>
            </section>

            {{-- ============================= AUDIENCE =============================
                 Its own body section, in the page's section rhythm. The value is
                 read as chips rather than a paragraph so a short list scans at a
                 glance. Left out entirely when the course has no audience set, so
                 a course that predates the field reads exactly as before. --}}
            @if ($course['audience'])
                <section class="hm-cd-sec hm-cd-aud" aria-labelledby="hmCdAudience">
                    <h2 class="hm-cd-sec__title" id="hmCdAudience">Who This Course Is For</h2>
                    <ul class="hm-cd-aud__list">
                        @foreach ($course['audience'] as $who)
                            <li class="hm-cd-aud__chip">{{ $who }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- ========================= UPCOMING BATCHES =========================
                 The batches listed for this course in Admin → Courses →
                 Schedule, eager-loaded already filtered to the active,
                 not-yet-started ones. The whole block is left out when the
                 course has none, so a course that is not scheduled reads exactly
                 as it did before. --}}
            @php $batches = $model->schedules; @endphp
            @if ($batches->count())
                <section class="hm-cd-sec hm-cd-batches" aria-labelledby="hmCdBatches">
                    <h2 class="hm-cd-sec__title" id="hmCdBatches">Upcoming Batches</h2>
                    <p class="hm-cd-sec__desc">
                        Pick the batch that fits your plans — dates, duration and fees for every
                        upcoming intake of this course.
                    </p>

                    {{-- The same table the home page and /schedules use (classes
                         and CSS from schedules.css), minus the Course and Category
                         columns — on this page both are already the subject. It
                         restyles into stacked cards under 992px the same way. --}}
                    <div class="hm-sched__panel">
                        <table class="hm-sched__table hm-sched__table--compact">
                            <caption class="visually-hidden">Upcoming batches of {{ $course['title'] }}, soonest first</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Start Date</th>
                                    <th scope="col">End Date</th>
                                    <th scope="col">Duration</th>
                                    <th scope="col">Fee</th>
                                    <th scope="col"><span class="visually-hidden">Action</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($batches as $batch)
                                    @php
                                        // What the enquiry modal shows and records.
                                        $batchLabel = $batch->end_date_label
                                            ? $batch->start_date_label . ' – ' . $batch->end_date_label
                                            : $batch->start_date_label;

                                        // The batch's own duration, falling back to the
                                        // course's when the batch left it blank.
                                        $batchDuration = $batch->duration ?: $model->duration;
                                    @endphp
                                    <tr class="hm-sched__row">
                                        <td data-label="Start Date">
                                            <span class="hm-sched__stack">
                                                <span class="hm-sched__stack-main">
                                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                                    {{ $batch->start_date_label }}
                                                </span>
                                                <span class="hm-sched__stack-sub">{{ $batch->start_day_label }}</span>
                                                {{-- Daily timing, when the batch has one. --}}
                                                @if ($batch->time_range_label)
                                                    <span class="hm-sched__stack-time">
                                                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                                        {{ $batch->time_range_label }}
                                                    </span>
                                                @endif
                                            </span>
                                        </td>

                                        <td data-label="End Date">
                                            @if ($batch->end_date_label)
                                                <span class="hm-sched__stack">
                                                    <span class="hm-sched__stack-main">
                                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                                        {{ $batch->end_date_label }}
                                                    </span>
                                                    <span class="hm-sched__stack-sub">{{ $batch->end_day_label }}</span>
                                                </span>
                                            @else
                                                <span class="hm-sched__none">—</span>
                                            @endif
                                        </td>

                                        {{-- Mode under the duration, as on the wide
                                             table — see partials/schedule-table. --}}
                                        <td data-label="Duration">
                                            @if ($batchDuration || $batch->training_mode)
                                                <span class="hm-sched__stack">
                                                    @if ($batchDuration)
                                                        <span class="hm-sched__stack-main">
                                                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                                            {{ $batchDuration }}
                                                        </span>
                                                    @endif
                                                    @if ($batch->training_mode)
                                                        <span class="hm-sched__stack-sub">{{ $batch->training_mode }}</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="hm-sched__none">—</span>
                                            @endif
                                        </td>

                                        {{-- "Show Fee" off, or no fee entered, reads as
                                             an invitation to ask — never ₹0 or a gap. --}}
                                        <td data-label="Fee">
                                            @if ($batch->fee_label)
                                                <span class="hm-sched__fee">{{ $batch->fee_label }}</span>
                                            @else
                                                <span class="hm-sched__fee hm-sched__fee--ask">Contact for Fee</span>
                                            @endif
                                        </td>

                                        <td class="hm-sched__action">
                                            <span class="hm-sched__actions">
                                                <button class="hm-course__btn hm-sched__btn" type="button"
                                                        data-bs-toggle="modal" data-bs-target="#hmEnquireModal"
                                                        data-enq-course="{{ $model->id }}"
                                                        data-enq-batch="{{ $batchLabel }}"
                                                        aria-label="Apply for the batch starting {{ $batch->start_date_label }}">
                                                    <span>Apply</span>
                                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                                </button>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            {{-- ============================ HIGHLIGHTS ============================ --}}
            <section class="hm-cd-sec" aria-labelledby="hmCdHighlights">
                <h2 class="hm-cd-sec__title" id="hmCdHighlights">Course Highlight</h2>
                <p class="hm-cd-sec__desc">
                    A comprehensive roadmap covering the most in-demand technologies in the modern web ecosystem.
                </p>

                <div class="row hm-cd-hls">
                    @foreach ($highlights as $highlight)
                        {{-- col-6 from the smallest screen up: two per row on phones. --}}
                        <div class="col-6 col-xl-3">
                            <div class="hm-cd-hl hm-cd-hl--{{ $highlight['tone'] }}">
                                <h3 class="hm-cd-hl__title">{{ $highlight['title'] }}</h3>
                                <span class="hm-cd-hl__icon" aria-hidden="true">
                                    @isset($highlight['icon'])
                                        <img src="{{ asset('assets/images/' . $highlight['icon']) }}"
                                             alt="" width="17" height="17" loading="lazy" decoding="async">
                                    @else
                                        <i class="{{ $highlight['fa'] }}"></i>
                                    @endisset
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            <div class="hm-cd-enquire">
                <button class="hm-cd-btn hm-cd-btn--primary" type="button" data-bs-toggle="modal" data-bs-target="#hmEnquireModal">
                    <span>Enquire Now</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
            {{-- ============================== SKILLS ============================== --}}
            {{-- <section class="hm-cd-sec" aria-labelledby="hmCdSkills">
                <h2 class="hm-cd-sec__title" id="hmCdSkills">Skills You'll Gain</h2>
                <p class="hm-cd-sec__desc">
                    A comprehensive roadmap covering the most in-demand technologies in the modern web ecosystem.
                </p>

                <div class="row hm-cd-skills">
                    @foreach ($skills as $i => $skill)
                        <div class="col-12 col-lg-6">
                            <div class="hm-cd-skill">
                              
                                <span class="hm-cd-skill__num" aria-hidden="true">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <div>
                                    <h3 class="hm-cd-skill__title">{{ $skill['title'] }}</h3>
                                    <p class="hm-cd-skill__desc">{{ $skill['desc'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section> --}}

            {{-- ========================= CONTINUE LEARNING =========================
                 The shared course card — same markup, styling and hover as the home
                 and courses pages. --}}
            <section class="hm-cd-sec hm-cd-related" aria-labelledby="hmCdRelated">
                <h2 class="hm-cd-sec__title" id="hmCdRelated">Continue Your Learning Journey</h2>
                <p class="hm-cd-sec__desc">
                    Explore more industry-focused courses designed to expand your expertise, strengthen your skills, and accelerate your career growth.
                </p>

                <div class="row hm-crs-grid">
                    @foreach ($relatedCourses as $i => $related)
                        {{-- col-6 from the smallest screen up. The row carries
                             .hm-crs-grid, so the card gets the same compact phone
                             treatment as the courses listing (courses.css). --}}
                        <div class="col-6 col-xl-3">
                            @include('frontend.partials.course-card', ['course' => $related, 'i' => $i])
                        </div>
                    @endforeach
                </div>
            </section>

        </div>
    </div>

    {{-- ================================ FAQ ================================
         Shared accordion, but fed THIS course's own FAQs (passing $faqs makes the
         partial's composer stand down — see AppServiceProvider). --}}
    @include('frontend.partials.faq', ['faqs' => $model->faqs])

    {{-- ============================ CONTACT FORM ============================
         Shared component — identical to the home page, validation JS included. --}}
    @include('frontend.partials.contact-form')

    {{-- ============================ ENQUIRE MODAL ============================
         Shared component — also opened by the Apply buttons in the Upcoming
         Course Schedules table on the home page and /schedules. Bootstrap 5
         supplies the show/hide, the backdrop, the focus trap, ESC and
         click-outside; every surface is restyled in enquiry-modal.css. --}}
    @include('frontend.partials.course-enquiry-modal', ['selectedCourseId' => $model->id])

@endsection

@push('scripts')
    {{-- Bootstrap bundle — drives the FAQ accordion (data-bs-toggle="collapse")
         AND the enquiry modal, including its backdrop, focus trap, ESC and
         click-outside. Both are dead without this. --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>

@endpush
