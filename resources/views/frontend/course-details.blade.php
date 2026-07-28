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
    $course = [
        'slug'        => $model->slug,
        'title'       => $model->name,
        'description' => $model->short_description ?: $model->overview,
        'image'       => $model->image_url,
        'date'        => optional($model->batch_start_date)->format('d/m/Y'),
        'datetime'    => optional($model->batch_start_date)->toDateString(),
        'students'    => '2,250 Students',
        'duration'    => $model->duration,
        'mode'        => $model->training_mode,
        'level'       => $model->skill_level,
        'certificate' => 'Industry Recognized',
        'placement'   => '100% Support',
        'about'       => $model->overview ?: $model->full_description ?: $model->short_description,
    ];
@endphp

@section('title', $model->meta_title ?: $course['title'] . ' — Hire Minds Academy')
@section('meta_description', $model->meta_description ?: $course['description'])

@push('styles')
    {{-- Poppins — the heading typeface used across the site's hero/banner blocks --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">

    {{-- Reused components. course-details.css goes LAST: it fits the FAQ and the
         contact section to this page by overriding rules of equal specificity,
         which only works on load order. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/courses.css') }}?v={{ filemtime(public_path('assets/css/frontend/courses.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/faq.css') }}?v={{ filemtime(public_path('assets/css/frontend/faq.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/contact-form.css') }}?v={{ filemtime(public_path('assets/css/frontend/contact-form.css')) }}">
    {{-- ?v=<file mtime> busts the browser cache whenever course-details.css
         changes, so edits are never masked by a stale copy. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/course-details.css') }}?v={{ filemtime(public_path('assets/css/frontend/course-details.css')) }}">
@endpush

@php
    // The five stat cards under the hero. 'icon' is an asset in
    // assets/images/courses/; 'fa' is the icon-font fallback where no asset
    // exists for that concept.
    $features = [
        ['value' => $course['duration'],    'label' => 'Duration',    'tone' => 'pink',  'icon' => 'iconamoon_clock-light.png'],
        ['value' => $course['mode'],        'label' => 'Mode',        'tone' => 'blue',  'icon' => 'school.png'],
        ['value' => $course['level'],       'label' => 'Skill Level', 'tone' => 'red',   'fa'   => 'fa-solid fa-arrow-trend-up'],
        ['value' => $course['certificate'], 'label' => 'Certificate', 'tone' => 'green', 'fa'   => 'fa-regular fa-circle-check'],
        ['value' => $course['placement'],   'label' => 'Placement',   'tone' => 'gold',  'fa'   => 'fa-solid fa-briefcase'],
    ];

    // Highlight cards — the tone drives both the pastel card and its icon tile.
    $highlights = [
        ['title' => 'Expert-Led Training',          'tone' => 'red',    'fa' => 'fa-solid fa-chalkboard-user'],
        ['title' => 'Hands-On Live Projects',       'tone' => 'purple', 'fa' => 'fa-solid fa-laptop-code'],
        ['title' => 'Industry-Focused Curriculum',  'tone' => 'teal',   'fa' => 'fa-solid fa-diagram-project'],
        ['title' => 'Certification',                'tone' => 'pink',   'fa' => 'fa-solid fa-certificate'],
        ['title' => 'Placement Assistance',         'tone' => 'blue',   'fa' => 'fa-solid fa-handshake'],
        ['title' => 'Resume & Interview Support',   'tone' => 'gold',   'fa' => 'fa-regular fa-comments'],
        ['title' => 'Dedicated Mentor Support',     'tone' => 'peach',  'fa' => 'fa-solid fa-user-tie'],
        ['title' => 'Deployment & Portfolio Building', 'tone' => 'green', 'fa' => 'fa-solid fa-rocket'],
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
                        <li class="hm-cd-hero__meta-item">
                            {{-- No calendar icon ships in courses/, so this reuses the
                                 blog one rather than inventing a placeholder. --}}
                            <img src="{{ asset('assets/images/blog/calendar.png') }}" alt="" aria-hidden="true">
                            <time datetime="{{ $course['datetime'] }}">{{ $course['date'] }}</time>
                        </li>
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
                        <a class="hm-cd-btn hm-cd-btn--ghost" href="#">
                            <span>Brochure</span>
                            <i class="fa-solid fa-download" aria-hidden="true"></i>
                        </a>
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
                                <img src="{{ asset('assets/images/courses/' . $feature['icon']) }}" alt="">
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

            {{-- ============================ HIGHLIGHTS ============================ --}}
            <section class="hm-cd-sec" aria-labelledby="hmCdHighlights">
                <h2 class="hm-cd-sec__title" id="hmCdHighlights">Course Highlight</h2>
                <p class="hm-cd-sec__desc">
                    A comprehensive roadmap covering the most in-demand technologies in the modern web ecosystem.
                </p>

                <div class="row hm-cd-hls">
                    @foreach ($highlights as $highlight)
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="hm-cd-hl hm-cd-hl--{{ $highlight['tone'] }}">
                                <h3 class="hm-cd-hl__title">{{ $highlight['title'] }}</h3>
                                <span class="hm-cd-hl__icon" aria-hidden="true">
                                    <i class="{{ $highlight['fa'] }}"></i>
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
                        <div class="col-12 col-md-6 col-xl-3">
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
         Opened by the hero's "Enroll Now" button. Bootstrap 5 supplies the show
         /hide, the backdrop, the focus trap, ESC and click-outside; every
         surface is restyled in course-details.css. --}}
    @php
        // Every active course (id + name), so the "Course" select is complete and
        // submits a real course_id; the current course is preselected below.
        $enquiryCourses = \App\Models\Course::active()->orderBy('name')->get(['id', 'name']);
        $selectedCourseId = $model->id;

        $careerGoals = [
            'Career Switch',
            'Looking for Course Completion',
            'Looking for Job',
            'Looking for Career Upgrade',
        ];

        // Transparent cut-out of the support executive — sits on the cream panel.
        $enquiryFigure = 'assets/images/courses/enquiry-modal.webp';
    @endphp

    <div class="modal fade hm-enq" id="hmEnquireModal" tabindex="-1"
         aria-labelledby="hmEnquireTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <button class="hm-enq__close" type="button" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

                <div class="row hm-enq__row">

                    {{-- ---------------------------- LEFT ---------------------------- --}}
                    <div class="col-12 col-lg-5 hm-enq__aside">
                        <img class="hm-enq__logo" src="{{ \App\Models\Setting::image('site_logo', 'assets/images/branding/logo.png') }}"
                             alt="Hire Minds Academy">

                        <h2 class="hm-enq__title" id="hmEnquireTitle">Take the First Step Toward Your Dream Career</h2>

                        <p class="hm-enq__desc">
                            Share your details and our experts will help you choose the best
                            program based on your career goals and interests.
                        </p>

                        {{-- Decorations --}}
                        <img class="hm-enq__deco hm-enq__deco--dots" aria-hidden="true"
                             src="{{ asset('assets/images/courses/dots.png') }}" alt="" loading="lazy">
                        <img class="hm-enq__deco hm-enq__deco--lines" aria-hidden="true"
                             src="{{ asset('assets/images/courses/pattern.png') }}" alt="" loading="lazy">
                        <img class="hm-enq__deco hm-enq__deco--star-a" aria-hidden="true"
                             src="{{ asset('assets/images/faq/star.png') }}" alt="" loading="lazy">
                        <img class="hm-enq__deco hm-enq__deco--star-b" aria-hidden="true"
                             src="{{ asset('assets/images/faq/star.png') }}" alt="" loading="lazy">

                        <figure class="hm-enq__figure">
                            <img src="{{ asset($enquiryFigure) }}" alt="" role="presentation" loading="lazy">
                        </figure>
                    </div>

                    {{-- ---------------------------- RIGHT ---------------------------- --}}
                    <div class="col-12 col-lg-7 hm-enq__main">
                        {{-- novalidate: the browser's own bubbles are replaced by the
                             inline messages below each field. action/@csrf are already
                             in place, so wiring a real POST is a route change only. --}}
                        <form class="hm-enq__form" id="hmEnquireForm" method="POST" action="{{ route('frontend.course-enquiry.store') }}" novalidate>
                            @csrf

                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="hm-enq__field" data-hm-field>
                                        <label class="hm-enq__label" for="enqName">Full Name</label>
                                        <input class="hm-enq__input" id="enqName" name="name" type="text"
                                               placeholder="Alex Johnson" required>
                                        <p class="hm-enq__error" data-hm-error>Please enter your full name.</p>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="hm-enq__field" data-hm-field>
                                        <label class="hm-enq__label" for="enqEmail">Email</label>
                                        <input class="hm-enq__input" id="enqEmail" name="email" type="email"
                                               placeholder="example@gmail.com" required>
                                        <p class="hm-enq__error" data-hm-error>Please enter a valid email address.</p>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="hm-enq__field" data-hm-field>
                                        <label class="hm-enq__label" for="enqPhone">Phone Number</label>
                                        <div class="hm-enq__phone">
                                            <span class="hm-enq__phone-code">+91</span>
                                            <input class="hm-enq__input" id="enqPhone" name="phone" type="tel"
                                                   placeholder="Mobile Number" inputmode="numeric"
                                                   pattern="[0-9]{10}" required>
                                        </div>
                                        <p class="hm-enq__error" data-hm-error>Enter a valid 10-digit mobile number.</p>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="hm-enq__field" data-hm-field>
                                        <label class="hm-enq__label" for="enqCity">City/Location</label>
                                        <input class="hm-enq__input" id="enqCity" name="city" type="text"
                                               placeholder="Enter Your Place">
                                        <p class="hm-enq__error" data-hm-error>Please enter your city.</p>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="hm-enq__field" data-hm-field>
                                        <label class="hm-enq__label" for="enqCourse">Course</label>
                                        <select class="hm-enq__input hm-enq__select" id="enqCourse" name="course_id" required>
                                            <option value="" disabled {{ $selectedCourseId ? '' : 'selected' }} hidden>Select a course</option>
                                            @foreach ($enquiryCourses as $option)
                                                <option value="{{ $option->id }}" {{ $option->id === $selectedCourseId ? 'selected' : '' }}>{{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="hm-enq__error" data-hm-error>Please choose a course.</p>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="hm-enq__field" data-hm-field>
                                        <label class="hm-enq__label" for="enqCareerGoal">Current Career Goal</label>
                                        <select class="hm-enq__input hm-enq__select" id="enqCareerGoal" name="career_goal" required>
                                            <option value="" disabled selected hidden>Select your career goal</option>
                                            @foreach ($careerGoals as $option)
                                                <option>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        <p class="hm-enq__error" data-hm-error>Please choose your career goal.</p>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="hm-enq__field" data-hm-field>
                                        <label class="hm-enq__label" for="enqMessage">Message</label>
                                        <textarea class="hm-enq__input hm-enq__textarea" id="enqMessage" name="message"
                                                  placeholder="Tell us how we can help you..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <button class="hm-enq__submit" type="submit">
                                <span>Send Enquiry</span>
                                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                            </button>

                            <p class="hm-enq__note" id="hmEnquireNote" role="status" hidden>
                                Thanks! Your enquiry has been received — our team will get back to you within 24 hours.
                            </p>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    {{-- Bootstrap bundle — drives the FAQ accordion (data-bs-toggle="collapse")
         AND the enquiry modal, including its backdrop, focus trap, ESC and
         click-outside. Both are dead without this. --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>

    {{-- Enquiry modal — inline validation, no reload, no alert(). --}}
    <script>
        (function () {
            'use strict';

            var form = document.getElementById('hmEnquireForm');
            if (!form) return;

            var modalEl = document.getElementById('hmEnquireModal');
            var note    = document.getElementById('hmEnquireNote');

            // Field -> its own rule. Anything not listed is optional and always
            // passes, so adding a field to the markup cannot silently block submit.
            var rules = {
                enqName:     function (v) { return v.trim().length > 0; },
                enqEmail:    function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); },
                enqPhone:    function (v) { return /^[0-9]{10}$/.test(v.trim()); },
                enqCourse:      function (v) { return v !== ''; },
                enqCareerGoal:  function (v) { return v !== ''; }
            };

            function fieldOf(el) { return el.closest('[data-hm-field]'); }

            function validate(el) {
                var rule = rules[el.id];
                if (!rule) return true;

                var ok    = rule(el.value);
                var field = fieldOf(el);
                if (field) field.classList.toggle('is-invalid', !ok);
                return ok;
            }

            // Re-check as the user fixes a field, but only once it has been marked
            // — validating on first keystroke would flag an empty field instantly.
            Object.keys(rules).forEach(function (id) {
                var el = document.getElementById(id);
                if (!el) return;

                el.addEventListener('blur', function () { validate(el); });
                el.addEventListener('input', function () {
                    var field = fieldOf(el);
                    if (field && field.classList.contains('is-invalid')) validate(el);
                });
                el.addEventListener('change', function () { validate(el); });
            });

            var submitBtn = form.querySelector('.hm-enq__submit');

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var firstInvalid = null;

                Object.keys(rules).forEach(function (id) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    if (!validate(el) && !firstInvalid) firstInvalid = el;
                });

                if (firstInvalid) { firstInvalid.focus(); return; }

                // Duplicate-submit guard — disable while the request is in flight.
                if (submitBtn.disabled) return;
                submitBtn.disabled = true;
                var btnText = submitBtn.querySelector('span');
                var original = btnText ? btnText.textContent : '';
                if (btnText) btnText.textContent = 'Sending…';

                fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: new FormData(form)
                })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                .then(function (data) {
                    if (data && data.success) {
                        form.reset();
                        if (note) {
                            note.hidden = false;
                            clearTimeout(note._t);
                            note._t = setTimeout(function () { note.hidden = true; }, 6000);
                        }
                    }
                })
                .catch(function () {
                    if (note) { note.textContent = 'Sorry, something went wrong. Please try again.'; note.hidden = false; }
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    if (btnText) btnText.textContent = original;
                });
            });

            // Leave the modal as it was found: clear the values, the messages and
            // the note, so reopening never shows the last visit's state.
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', function () {
                    form.reset();
                    form.querySelectorAll('[data-hm-field]').forEach(function (f) {
                        f.classList.remove('is-invalid');
                    });
                    if (note) { note.hidden = true; clearTimeout(note._t); }
                });
            }
        })();
    </script>
@endpush
