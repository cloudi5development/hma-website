@extends('frontend.layouts.template-base')

@section('title', 'Hire Minds Academy — Learn, Practice, Get Hired')
@section('meta_description', 'Hire Minds Academy turns ambition into a career. Master in-demand tech skills through hands-on projects, real practice and mentorship — from your first line of code to your first job offer.')

@push('styles')
    {{-- Preload the LCP image. Without this the browser cannot discover it until
         it has parsed and applied the CSS above it, so the single largest paint
         on the page starts late. This makes it a parse-time fetch. --}}
    <link rel="preload" as="image" fetchpriority="high"
          href="{{ $hero->image_url }}"
          type="image/webp">

    {{-- Bootstrap 5 + Font Awesome are loaded site-wide via layouts/common-css --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" crossorigin="anonymous">
    {{-- Poppins — used only for the Upcoming Events heading (per its design spec) --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap">
    {{-- Shared sections (also used on the About / Contact pages) --}}
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/partners.css') }}?v={{ filemtime(public_path('assets/css/frontend/partners.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/counters.css') }}?v={{ filemtime(public_path('assets/css/frontend/counters.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/testimonials.css') }}?v={{ filemtime(public_path('assets/css/frontend/testimonials.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/faq.css') }}?v={{ filemtime(public_path('assets/css/frontend/faq.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/contact-form.css') }}?v={{ filemtime(public_path('assets/css/frontend/contact-form.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/career-success.css') }}?v={{ filemtime(public_path('assets/css/frontend/career-success.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/courses.css') }}?v={{ filemtime(public_path('assets/css/frontend/courses.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/home.css') }}?v={{ filemtime(public_path('assets/css/frontend/home.css')) }}">
@endpush

@section('content')

    {{-- Navbar lives in layouts/header.blade.php (global, reused on every page). --}}

    {{-- ================================== HERO ================================== --}}
    <section class="hm-hero" id="hero" aria-labelledby="hmHeroTitle">

        {{-- Blurred four-colour aurora (blue·violet·yellow·pink) rotating clockwise --}}
        <div class="hm-hero__glow" aria-hidden="true"></div>

        {{-- Slow-rotating holographic glow, blurred + low opacity, behind everything --}}
        <div class="hm-hero__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" fetchpriority="low">
        </div>

        <div class="container hm-hero__container">
            <div class="row align-items-center g-5">

                {{-- ---------------------------- LEFT COLUMN ---------------------------- --}}
                {{-- Copy + buttons are admin-editable (Sections → Hero); the layout,
                     classes, reveal timing and the chevron icon stay fixed. --}}
                <div class="col-lg-6 hm-hero__left">
                    <span class="hm-badge hm-reveal" data-delay="100">
                        <span class="hm-badge__icon" aria-hidden="true"></span>
                        <span class="hm-badge__text">{{ $hero->badge_text }}</span>
                    </span>

                    <h1 class="hm-hero__title hm-reveal" data-delay="200" id="hmHeroTitle">
                        {{ $hero->title }}
                    </h1>

                    <p class="hm-hero__text hm-reveal" data-delay="300">
                        {{ $hero->description }}
                    </p>

                    <div class="hm-hero__actions hm-reveal" data-delay="400">
                        <a class="hm-btn hm-btn--primary" href="{{ $hero->btn1_url ?: '#courses' }}">
                            <span class="hm-btn__label">{{ $hero->btn1_text }} <i class="fa-solid fa-chevron-right"></i></span>
                        </a>
                        <a class="hm-btn hm-btn--ghost" href="{{ $hero->btn2_url ?: route('frontend.contact-us') }}">
                            <span class="hm-btn__label apply">{{ $hero->btn2_text }} </span>
                        </a>
                    </div>
                </div>

                {{-- ---------------------------- RIGHT COLUMN ---------------------------- --}}
                <div class="col-lg-6 hm-hero__right">
                    <div class="hm-hero__visual" id="hmVisual">

                        {{-- The LCP element. data-hm-hero-img is what the loader
                             waits on — it dismisses once THIS has decoded rather
                             than on window.load, which would block on every image
                             further down the page. decoding="sync" so it paints
                             with the rest of the hero instead of a frame later. --}}
                        {{-- Right-column image is admin-editable (Sections → Hero);
                             the floating cards + scroll badge below stay static. --}}
                        <img class="hm-hero__student hm-reveal hm-reveal--fade" data-delay="200"
                             data-hm-hero-img
                             src="{{ $hero->image_url }}"
                             alt="Smiling Hire Minds Academy student holding a notebook"
                             width="560" height="548" decoding="sync" fetchpriority="high">

                        {{-- Google rating card --}}
                        <div class="hm-card hm-card--google hm-float-a hm-reveal hm-reveal--fade" data-delay="300">
                            <img class="hm-card__logo" src="{{ asset('assets/images/Hero-section/google.png') }}"
                                 alt="Google" width="20" height="20" loading="lazy">
                            <div class="hm-card__body">
                                <span class="hm-card__title">Hireminds..<span class="hm-caret" aria-hidden="true">|</span></span>
                            </div>
                        </div>

                        {{-- Review / rating card --}}
                        <div class="hm-card hm-card--review hm-float-b hm-reveal hm-reveal--fade" data-delay="400">
                            <img class="hm-card__star" src="{{ asset('assets/images/Hero-section/star.png') }}"
                                 alt="" role="presentation" width="20" height="20" loading="lazy">
                            <div class="hm-card__body">
                                <span class="hm-card__title">4.5 Reviews</span>
                            </div>
                        </div>

                        {{-- Enrollment / avatars card --}}
                        <div class="hm-card hm-card--enroll hm-float-c hm-reveal hm-reveal--fade" data-delay="500">
                            <img class="hm-card__avatars" src="{{ asset('assets/images/Hero-section/group-component.webp') }}"
                                 alt="Recently enrolled students" width="96" height="30" loading="lazy">
                            <div class="hm-card__body">
                                <span class="hm-card__title">800+</span>
                                <span class="hm-card__sub">User Enroll</span>
                            </div>
                        </div>

                        {{-- Scroll-down circular badge (bottom-right) --}}
                        <button class="hm-scroll" id="hmScroll" type="button" aria-label="Scroll to next section">
                            <svg class="hm-scroll__ring" viewBox="0 0 100 100" aria-hidden="true">
                                <defs>
                                    <path id="hmScrollPath" fill="none"
                                          d="M50,50 m-37,0 a37,37 0 1,1 74,0 a37,37 0 1,1 -74,0"></path>
                                </defs>
                                <text class="hm-scroll__text">
                                    <textPath href="#hmScrollPath" startOffset="0" textLength="232" lengthAdjust="spacing">
                                        &bull; SCROLL DOWN &bull; SCROLL DOWN &bull;
                                    </textPath>
                                </text>
                            </svg>
                            <span class="hm-scroll__arrow" aria-hidden="true"><i class="fa-solid fa-arrow-down-long"></i></span>
                        </button>

                    </div>
                </div>

            </div>

            {{-- Floating technology icons — scattered across the hero (positions set in home.css) --}}
            {{-- <span class="hm-tech hm-tech--html hm-float-a hm-a hm-reveal hm-reveal--fade" data-delay="500" aria-hidden="true">
                <img src="{{ asset('assets/images/Hero-section/html.png') }}" alt="HTML5" width="30" height="30" loading="lazy">
            </span> --}}
            <span class="hm-tech hm-tech--js hm-float-b hm-b hm-reveal hm-reveal--fade" data-delay="500" aria-hidden="true">
                <img src="{{ asset('assets/images/Hero-section/javascript.webp') }}" alt="JavaScript" width="30" height="30" loading="lazy">
            </span>

        </div>
    </section>

    {{-- ============================ TRUSTED PARTNERS ============================ --}}
    {{-- Shared with the About page — markup in partials/partners.blade.php --}}
    @include('frontend.partials.partners')

    {{-- ============================ ABOUT US ============================ --}}
    <section class="hm-about" id="about" data-io aria-labelledby="hmAboutTitle">

        {{-- Same slow-rotating premium background as the hero --}}
        <div class="hm-about__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container hm-about__container">
            <div class="row align-items-center g-5">

                {{-- ------------------------- LEFT: IMAGE ------------------------- --}}
                <div class="col-lg-6 hm-about__left">
                    <div class="hm-about__visual hm-anim hm-anim--left">
                        <img class="hm-about__deco hm-about__deco--circle" aria-hidden="true"
                             src="{{ asset('assets/images/about-us/circle-component.png') }}" alt="" loading="lazy">
                        <img class="hm-about__deco hm-about__deco--star" aria-hidden="true"
                             src="{{ asset('assets/images/about-us/star-component.png') }}" alt="" loading="lazy">
                        <img class="hm-about__img"
                             src="{{ asset('assets/images/about-us/about-img.webp') }}"
                             alt="Hire Minds Academy students celebrating success" loading="lazy">
                    </div>
                </div>

                {{-- ------------------------- RIGHT: CONTENT ------------------------- --}}
                <div class="col-lg-6 hm-about__right">
                    <span class="hm-about__label hm-anim hm-anim--right hm-anim--d1">
                        <span class="hm-about__label-icon" aria-hidden="true"></span>
                        <span class="hm-about__label-text">About Us</span>
                    </span>

                    <h2 class="hm-about__title hm-anim hm-anim--right hm-anim--d1" id="hmAboutTitle">
                     Empowering Learners with Skills That Build Successful Careers
                    </h2>

                    <p class="hm-about__text hm-anim hm-anim--right hm-anim--d2">
            At HireMinds Academy, we bridge the gap between learning and employment through practical, industry-focused training. Our programs are designed to equip aspiring professionals with the skills, confidence, and real-world experience needed to thrive in today's competitive job market.
                    </p>

                    <div class="hm-about__cards">
                        <div class="hm-about__card hm-about__card--blue hm-anim hm-anim--up hm-anim--d3">
                            <h3 class="hm-about__card-title">Expert Trainers</h3>
                            <p class="hm-about__card-text">Learn from experienced industry professionals.</p>
                        </div>
                        <div class="hm-about__card hm-about__card--yellow hm-anim hm-anim--up hm-anim--d4">
                            <h3 class="hm-about__card-title">Career Guidance</h3>
                            <p class="hm-about__card-text">Mentorship from learning to placement.</p>
                        </div>
                    </div>

                    <a class="hm-btn hm-btn--primary hm-about__btn hm-anim hm-anim--right hm-anim--d4" href="{{ route('frontend.about-us') }}">
                        <span class="hm-btn__label">Explore Our Journey <i class="fa-solid fa-chevron-right"></i></span>
                    </a>
                </div>
            </div>

            {{-- ------------------------- STATISTICS ------------------------- --}}
            {{-- Shared with the About page — markup in partials/counters.blade.php --}}
            @include('frontend.partials.counters')
        </div>
    </section>

    {{-- ============================ TOP CATEGORIES ============================ --}}
    @php
        // Fed by AppServiceProvider's view composer. Mapped to the exact array
        // shape the markup expects; the course count is now derived live and the
        // pastel tone / iconsax glyph keep the original design intact.
        $categories = collect($homeCategories ?? [])->map(fn ($c) => [
            'name'  => $c->name,
            'count' => $c->course_count_label,
            'icon'  => $c->icon_url,
            'tone'  => $c->tone_value,
            'url'   => route('frontend.courses', ['category' => $c->slug]),
        ])->all();
    @endphp
    @if (count($categories))
    <section class="hm-cats" id="categories" data-io aria-labelledby="hmCatsTitle">

        {{-- Same slow-rotating premium background as the hero --}}
        <div class="hm-cats__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container hm-cats__container">

            {{-- Header --}}
            <div class="hm-cats__head">
                <span class="hm-cats__label hm-anim hm-anim--up">
                    <span class="hm-cats__label-icon" aria-hidden="true"></span>
                    <span class="hm-cats__label-text">Top Categories</span>
                </span>
                <h2 class="hm-cats__title hm-anim hm-anim--up hm-anim--d1" id="hmCatsTitle">Explore courses by field</h2>
                <p class="hm-cats__desc hm-anim hm-anim--up hm-anim--d2">
                    Discover industry-focused programs designed to build practical skills, boost your
                    confidence, and prepare you for successful careers.
                </p>
                <a class="hm-cats__seeall hm-anim hm-anim--up hm-anim--d2" href="#">
                    See all <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>

            {{-- Grid: 4 columns desktop · 2 tablet · 1 mobile --}}
            <div class="row g-4 hm-cats__grid">
                @foreach ($categories as $i => $cat)
                    <div class="col-md-6 col-lg-3">
                        <a class="hm-cat hm-cat--{{ $cat['tone'] }} hm-anim hm-anim--up hm-anim--d{{ ($i % 8) + 1 }}" href="#">
                            <span class="hm-cat__text">
                                <span class="hm-cat__name">{{ $cat['name'] }}</span>
                                <span class="hm-cat__count">{{ $cat['count'] }}</span>
                            </span>
                            <span class="hm-cat__icon">
                                {{-- Coloured blob behind, white glyph in front. Only
                                     the blob spins on hover (see home.css). --}}
                                <img class="hm-cat__icon-bg"
                                     src="{{ asset('assets/images/categories/blob-'.$cat['tone'].'.png') }}"
                                     alt="" aria-hidden="true" width="56" height="56" loading="lazy">
                                <img class="hm-cat__icon-glyph"
                                     src="{{ asset('assets/images/categories/'.$cat['icon']) }}"
                                     alt="{{ $cat['name'] }} icon" width="28" height="28" loading="lazy">
                            </span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ============================ POPULAR COURSES ============================ --}}
    @php
        // Fed by the view composer (max 4). Mapped to the exact card shape the
        // shared partial expects; the thumbnail is a ready-built URL.
        $courses = collect($popularCourses ?? [])->map(fn ($c) => [
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
    @if (count($courses))
    <section class="hm-courses" id="courses" data-io aria-labelledby="hmCoursesTitle">

        {{-- Same slow-rotating premium background as the hero --}}
        <div class="hm-courses__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
        </div>

        {{-- Decorative dotted / striped shapes --}}
        <img class="hm-courses__deco hm-courses__deco--tl" aria-hidden="true"
             src="{{ asset('assets/images/courses/dots.png') }}" alt="" loading="lazy">
        <img class="hm-courses__deco hm-courses__deco--br" aria-hidden="true"
             src="{{ asset('assets/images/courses/pattern.png') }}" alt="" loading="lazy">

        <div class="container hm-courses__container">

            {{-- Header --}}
            <div class="hm-courses__head">
                <span class="hm-courses__label hm-anim hm-anim--up">
                   <span class="hm-cats__label-icon" aria-hidden="true"></span>
                    <span class="hm-courses__label-text">Popular Courses</span>
                </span>
                <h2 class="hm-courses__title hm-anim hm-anim--up hm-anim--d1" id="hmCoursesTitle">Explore Courses That Shape Your Future</h2>
                <p class="hm-courses__desc hm-anim hm-anim--up hm-anim--d2">
                    Choose from industry-focused courses designed to build practical skills, boost
                    confidence, and prepare you for today's most in-demand careers.
                </p>
                <a class="hm-courses__seeall hm-anim hm-anim--up hm-anim--d2" href="#">
                    See all <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>

            {{-- Grid: 4 cards desktop · 2 tablet · 1 mobile --}}
            <div class="row g-4">
                @foreach ($courses as $i => $course)
                    <div class="col-lg-3 col-md-6">
                        {{-- Shared with the courses listing page — markup in
                             partials/course-card.blade.php, CSS in courses.css. --}}
                        @include('frontend.partials.course-card', ['course' => $course, 'i' => $i])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ============================ WHY CHOOSE HIREMINDS ============================ --}}
    @php
        // Assets live in public/assets/images/why-choose/
        //   bg-N.png        → flowing line pattern (transparent, tinted per card)
        //   component-N.png → decorative dots / plus-grid (transparent)
        //   person-N.png    → transparent person cut-out
        // 'tone' drives the colour set (home.css); 'reverse' flips the person to
        // the left, matching the alternating reference layout.
        $whyCards = [
            [
                'title'   => 'Industry Expert Trainers',
                'desc'    => 'Learn directly from professionals who bring real hiring experience into every classroom, helping you understand what companies truly expect.',
                'pills'   => ['HR Professionals', 'Practical Sessions', 'Career Guidance'],
                'tone'    => 'orange',
                'reverse' => false,
                'bg'      => 'bg-1.png', 'component' => 'component-1.png', 'person' => 'person-1.webp', 'fit' => 'tall',
            ],
            [
                'title'   => 'Career Support',
                'desc'    => 'Receive personalized career guidance with resume reviews, mock interviews, and placement assistance to help you confidently enter the job market.',
                'pills'   => ['Resume Building', 'Mock Interviews', 'Placement Support'],
                'tone'    => 'blue',
                'reverse' => true,
                'bg'      => 'bg-2.png', 'component' => 'component-2.png', 'person' => 'person-2.webp', 'fit' => 'wide',
            ],
            [
                'title'   => 'Hands-On Learning',
                'desc'    => 'Learn by doing through live projects, real-world case studies, workshops, and practical assignments that build job-ready skills.',
                'pills'   => ['Live Projects', 'Practical Workshops', 'Case Studies'],
                'tone'    => 'yellow',
                'reverse' => false,
                'bg'      => 'bg-3.png', 'component' => 'component-3.png', 'person' => 'person-3.webp', 'fit' => 'tall',
            ],
            [
                'title'   => 'Industry-Aligned Curriculum',
                'desc'    => 'Our curriculum is continuously updated with the latest tools, technologies, and industry trends to keep your skills relevant.',
                'pills'   => ['Latest Technologies', 'Updated Syllabus', 'In-Demand Skills'],
                'tone'    => 'pink',
                'reverse' => true,
                'bg'      => 'bg-4.png', 'component' => 'component-4.png', 'person' => 'person-4.webp', 'fit' => 'wide',
            ],
        ];
    @endphp
    <section class="hm-why" id="why-choose" aria-labelledby="hm-why-title">
        {{-- Heading scrolls away with the page; only the card area pins below it. --}}
        <div class="hm-why__intro">
            <div class="container">
                <div class="hm-why__head">
                    <span class="hm-why__eyebrow"><span class="hm-cats__label-icon" aria-hidden="true"></span> Why Choose HireMinds</span>
                    <h2 class="hm-why__title" id="hm-why-title">Everything You Need to Launch a Successful Career</h2>
                    <p class="hm-why__lead">Gain practical skills, learn from industry experts, and receive career guidance that prepares you for real-world opportunities.</p>
                </div>
            </div>
        </div>

        <div class="hm-why__pin">
            {{-- Reused hero rotating background — same image / blur / opacity / 90s spin --}}
            <div class="hm-why__bg" aria-hidden="true">
                <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
            </div>

            <div class="container hm-why__container">
                <div class="hm-why__stack" id="hmWhyStack">
                    @foreach ($whyCards as $i => $card)
                        <article @class(['hm-why__card', 'hm-why__card--'.$card['tone'], 'is-reverse' => $card['reverse']])
                                 data-index="{{ $i }}" aria-label="{{ $card['title'] }}">
                            <img class="hm-why__lines" src="{{ asset('assets/images/why-choose/'.$card['bg']) }}" alt="" aria-hidden="true">
                            <span class="hm-why__decor" aria-hidden="true">
                                <img class="hm-why__circle hm-why__circle--a" src="{{ asset('assets/images/why-choose/'.$card['component']) }}" alt="">
                                <img class="hm-why__circle hm-why__circle--b" src="{{ asset('assets/images/why-choose/'.$card['component']) }}" alt="">
                                <span class="hm-why__plus"></span>
                                <span class="hm-why__slash"></span>
                            </span>

                            <div class="hm-why__card-inner">
                                <div class="hm-why__content">
                                    <h3 class="hm-why__card-title">{{ $card['title'] }}</h3>
                                    <p class="hm-why__card-desc">{{ $card['desc'] }}</p>
                                    <ul class="hm-why__pills">
                                        @foreach ($card['pills'] as $pill)
                                            <li class="hm-why__pill"><span class="hm-why__pill-dot"></span>{{ $pill }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="hm-why__figure hm-why__figure--{{ $card['fit'] }}">
                                    <img src="{{ asset('assets/images/why-choose/'.$card['person']) }}" alt="{{ $card['title'] }} — HireMinds mentor" loading="lazy">
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ UPCOMING EVENTS ============================ --}}
    @php
        // Speaker cut-outs live in public/assets/images/events/. Array order is
        // left → centre → right on first paint; the carousel rotates them.
        $events = [
            [
                'speaker' => 'Rochelle Fernandez',
                'title'   => 'Learn about no-code tools',
                'type'    => 'Live Event',
                'price'   => '₹499/-',
                'link'    => '#',
                'person'  => 'person-2.webp',
                'tone'    => 'purple',
            ],
            [
                'speaker' => 'Regina Phalange',
                'title'   => 'Nail your interviews',
                'type'    => 'Live Event',
                'price'   => '₹499/-',
                'link'    => '#',
                'person'  => 'person-3.webp',
                'tone'    => 'teal',
            ],
            [
                'speaker' => 'Rachel Bennett',
                'title'   => 'Sell your first product online',
                'type'    => 'Live Event',
                'price'   => '₹499/-',
                'link'    => '#',
                'person'  => 'person-1.webp',
                'tone'    => 'green',
            ],
        ];
    @endphp
    <section class="hm-events" id="events" aria-labelledby="hmEventsTitle">
        <div class="hm-events__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/events/events-bg.webp') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container hm-events__container">
            <div class="hm-events__head" data-ev-io>

                <span class="hm-events__badge hm-ev-anim"><span class="hm-cats__label-icon" aria-hidden="true"></span> Upcoming Event</span>
                <h2 class="hm-events__title hm-ev-anim" id="hmEventsTitle">Learn, Connect &amp; Grow Through Our Events</h2>
                <p class="hm-events__desc hm-ev-anim">Join workshops, seminars, career guidance sessions, and industry events designed to expand your knowledge, build your network, and prepare for future opportunities.</p>
            </div>

            <div class="hm-ev-carousel" id="hmEvCarousel" data-ev-io
                 role="group" aria-roledescription="carousel" aria-label="Upcoming events" tabindex="0">

                <button class="hm-ev-nav hm-ev-nav--prev" id="hmEvPrev" type="button" aria-label="Previous event">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </button>

                <div class="hm-ev-track" id="hmEvTrack">
                    @foreach ($events as $i => $ev)
                        <article class="hm-ev-card hm-ev-card--{{ $ev['tone'] }}" data-ev-index="{{ $i }}" aria-label="{{ $ev['title'] }} — {{ $ev['speaker'] }}">
                            <div class="hm-ev-card__body">
                                <span class="hm-ev-card__speaker">{{ $ev['speaker'] }}</span>
                                <h3 class="hm-ev-card__title">{{ $ev['title'] }}</h3>
                                <div class="hm-ev-card__foot">
                                    <div class="hm-ev-card__meta">
                                        <span class="hm-ev-card__type">{{ $ev['type'] }}</span>
                                        <span class="hm-ev-card__price">{{ $ev['price'] }}</span>
                                    </div>
                                    <a class="hm-ev-card__btn" href="{{ $ev['link'] }}">
                                        Event Details <i class="fa-solid fa-arrow-up-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                            <img class="hm-ev-card__person" src="{{ asset('assets/images/events/'.$ev['person']) }}" alt="{{ $ev['speaker'] }}" loading="lazy">
                        </article>
                    @endforeach
                </div>

                <button class="hm-ev-nav hm-ev-nav--next" id="hmEvNext" type="button" aria-label="Next event">
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </section>

    {{-- ============================ STUDENT SUCCESS STORIES ============================ --}}
    @php
        // Portraits are transparent cut-outs in public/assets/images/success-story/.
        // 'tone' tints the CSS studio glow behind each student.
        $stories = [
            ['img' => 'person-1.webp', 'salary' => '₹9.0', 'name' => 'Aayushman Pravin', 'role' => 'Software Developer', 'tone' => 'olive'],
            ['img' => 'person-2.webp', 'salary' => '₹9.0', 'name' => 'Aayushman Pravin', 'role' => 'Software Developer', 'tone' => 'teal'],
            ['img' => 'person-3.webp', 'salary' => '₹9.0', 'name' => 'Aayushman Pravin', 'role' => 'Software Developer', 'tone' => 'green'],
            ['img' => 'person-4.webp', 'salary' => '₹9.0', 'name' => 'Aayushman Pravin', 'role' => 'Software Developer', 'tone' => 'violet'],
        ];
    @endphp
    <section class="hm-stories" id="success-stories" data-io aria-labelledby="hmStoriesTitle">

        {{-- Same slow-rotating premium background as the hero --}}
        <div class="hm-stories__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container hm-stories__container">

            {{-- Header --}}
            <div class="hm-stories__head">
                <span class="hm-stories__label hm-anim hm-anim--up">
                    <span class="hm-cats__label-icon" aria-hidden="true"></span>
                    <span class="hm-stories__label-text">Student Success Stories</span>
                </span>
                <h2 class="hm-stories__title hm-anim hm-anim--up hm-anim--d1" id="hmStoriesTitle">Real Career Stories Powered By Hireminds Academy</h2>
                <p class="hm-stories__desc hm-anim hm-anim--up hm-anim--d2">
                    Practical learning experiences that prepare you for real-world career opportunities.
                </p>
            </div>

            {{-- Cards: 4 desktop · 2 tablet · 1 mobile --}}
            <div class="row g-4 hm-stories__grid">
                @foreach ($stories as $i => $story)
                    <div class="col-lg-3 col-md-6">
                        {{-- 3 nested layers keep reveal / float / hover transforms independent --}}
                        <div class="hm-anim hm-anim--up hm-anim--d{{ ($i % 4) + 1 }}">
                            <div class="hm-story-wrap">
                                <article class="hm-story hm-story--{{ $story['tone'] }}" tabindex="0">
                                    <span class="hm-story__bg" aria-hidden="true"></span>
                                    <img class="hm-story__img"
                                         src="{{ asset('assets/images/success-story/'.$story['img']) }}"
                                         alt="{{ $story['name'] }} — {{ $story['role'] }}" loading="lazy">
                                    <span class="hm-story__overlay" aria-hidden="true"></span>
                                    <div class="hm-story__content">
                                        <div class="hm-story__salary">
                                            <span class="hm-story__amount">{{ $story['salary'] }}</span>
                                            <span class="hm-story__lpa">LPA</span>
                                        </div>
                                        <div class="hm-story__name">{{ $story['name'] }}</div>
                                        <span class="hm-story__role">{{ $story['role'] }}</span>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================ LATEST BLOG ============================ --}}
    @php
        // Thumbnails live in public/assets/images/hero-blog/. 'tone' picks the pastel card bg.
        $blogExcerpt = "Explore articles, career advice, interview tips, and industry updates written to keep you ahead in....";
        $blogs = [
            ['img' => 'blog-1.webp', 'tone' => 'cream',  'title' => "How Are Plant Therapy's Essential Oils Extracted?", 'excerpt' => $blogExcerpt, 'date' => '20 July, 2024'],
            ['img' => 'blog-2.webp', 'tone' => 'blue',   'title' => "How Are Plant Therapy's Essential Oils Extracted?", 'excerpt' => $blogExcerpt, 'date' => '20 July, 2024'],
            ['img' => 'blog-3.webp', 'tone' => 'green',  'title' => "How Are Plant Therapy's Essential Oils Extracted?", 'excerpt' => $blogExcerpt, 'date' => '20 July, 2024'],
            ['img' => 'blog-4.webp', 'tone' => 'purple', 'title' => "How Are Plant Therapy's Essential Oils Extracted?", 'excerpt' => $blogExcerpt, 'date' => '20 July, 2024'],
        ];
    @endphp
    <section class="hm-blogs" id="latest-blog" data-io aria-labelledby="hmBlogsTitle">

        {{-- Same slow-rotating premium background as the hero --}}
        <div class="hm-blogs__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container hm-blogs__container">

            {{-- Header --}}
            <div class="hm-blogs__head">
                <span class="hm-blogs__label hm-anim hm-anim--up">
                   <span class="hm-cats__label-icon" aria-hidden="true"></span>
                    <span class="hm-blogs__label-text">Latest Blog</span>
                </span>
                <h2 class="hm-blogs__title hm-anim hm-anim--up hm-anim--d1" id="hmBlogsTitle">Learn Beyond the Classroom</h2>
                <p class="hm-blogs__desc hm-anim hm-anim--up hm-anim--d2">
                    Explore articles, career advice, interview tips, and industry updates written to keep
                    you ahead in today's competitive job market.
                </p>
                <a class="hm-blogs__seeall hm-anim hm-anim--up hm-anim--d2" href="{{ route('frontend.blog') }}">
                    See all <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>

            {{-- 2 × 2 grid (2 columns on tablet & desktop, 1 on mobile) --}}
            <div class="row g-4 hm-blogs__grid">
                @foreach ($blogs as $i => $blog)
                    <div class="col-md-6">
                        <article class="hm-blog hm-blog--{{ $blog['tone'] }} hm-anim hm-anim--up hm-anim--d{{ ($i % 4) + 1 }}">
                            <div class="hm-blog__thumb">
                                <img src="{{ asset('assets/images/hero-blog/'.$blog['img']) }}"
                                     alt="{{ $blog['title'] }}" loading="lazy">
                            </div>
                            <div class="hm-blog__body">
                                <h3 class="hm-blog__title">
                                    <a href="{{ route('frontend.blog-details') }}">{{ $blog['title'] }}</a>
                                </h3>
                                <p class="hm-blog__desc">{{ $blog['excerpt'] }}</p>
                                <div class="hm-blog__meta">
                                    <span class="hm-blog__date">
                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $blog['date'] }}
                                    </span>
                                    <a class="hm-blog__more" href="{{ route('frontend.blog-details') }}">Read More</a>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================ TESTIMONIALS ============================ --}}
    {{-- Shared with the About page — markup in partials/testimonials.blade.php --}}
    @include("frontend.partials.testimonials")

    {{-- ============================ FAQ ============================ --}}
    {{-- Shared with the About page — markup in partials/faq.blade.php --}}
    @include("frontend.partials.faq")

    {{-- ============================ CONTACT / ENQUIRY ============================ --}}
    {{-- Contact — shared with the contact page. Its validation JS ships with
         the partial; its CSS is contact-form.css, linked above. --}}
    @include('frontend.partials.contact-form')

    {{-- ============================ OUR JOURNEY / REELS ============================ --}}
    {{-- Shared with the testimonials page, which passes its own header copy.
         Markup in partials/career-success.blade.php; CSS is career-success.css,
         linked above; the Swiper init ships with the partial. --}}
    @include('frontend.partials.career-success')

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>
    <script src="{{ asset('assets/js/frontend/home.js') }}" defer></script>

    {{-- Entrance reveals — adds .is-in to each [data-io] section as it scrolls in.
         (The stat counters ship with partials/counters.blade.php.) --}}
    <script>
        (function () {
            'use strict';
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var hasIO = 'IntersectionObserver' in window;

            var ioSections = document.querySelectorAll('[data-io]');
            if (!ioSections.length) return;

            if (reduce || !hasIO) {
                ioSections.forEach(function (s) { s.classList.add('is-in'); });
                return;
            }

            var revealIO = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { e.target.classList.add('is-in'); obs.unobserve(e.target); }
                });
            }, { threshold: 0.15 });
            ioSections.forEach(function (s) { revealIO.observe(s); });
        })();
    </script>




    {{-- Why Choose HireMinds — pinned stacked-card storytelling (GSAP ScrollTrigger) --}}
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" crossorigin="anonymous" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" crossorigin="anonymous" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
            gsap.registerPlugin(ScrollTrigger);

            var section = document.querySelector('.hm-why');
            var pin     = section && section.querySelector('.hm-why__pin');
            var stack   = document.getElementById('hmWhyStack');
            if (!section || !pin || !stack) return;

            var cards = gsap.utils.toArray('.hm-why__card', stack);
            if (cards.length < 2) return;

            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Slide the brown pills in from the left, one after another.
            function pillsFromTo(card) {
                return gsap.fromTo(card.querySelectorAll('.hm-why__pill'),
                    { xPercent: -45, autoAlpha: 0 },
                    { xPercent: 0, autoAlpha: 1, duration: .55, ease: 'power3.out', stagger: 0.16, overwrite: 'auto' });
            }

            var mm = gsap.matchMedia();

            /* ---- Desktop: pinned, scrubbed, stacked storytelling ------------- */
            mm.add('(min-width: 992px)', function () {
                if (reduce) { gsap.set(cards, { clearProps: 'all' }); return; }

                stack.classList.add('is-gsap');
                pin.classList.add('is-gsap');   // switches the pin to fixed-viewport sizing

                // Depth slots: 0 = active card (sharp), deeper cards peek out
                // along the bottom edge as blurred, slightly-narrower strips.
                var slots = [
                    { yp: 0,  scale: 1,    op: 1,   blur: 0 },
                    { yp: 4,  scale: .975, op: 1,   blur: 3 },
                    { yp: 8,  scale: .95,  op: .96, blur: 3 },
                    { yp: 12, scale: .925, op: .9,  blur: 3 }
                ];
                var EXIT = { yPercent: -120, scale: .96, autoAlpha: 0, filter: 'blur(0px)' };

                // Initial layered state (card 1 in front, the rest peeking behind).
                cards.forEach(function (card, i) {
                    var s = slots[Math.min(i, slots.length - 1)];
                    gsap.set(card, {
                        yPercent: s.yp, scale: s.scale, autoAlpha: s.op,
                        filter: 'blur(' + s.blur + 'px)',
                        transformOrigin: '50% 50%', zIndex: cards.length - i
                    });
                });
                gsap.set(stack.querySelectorAll('.hm-why__pill'), { xPercent: -45, autoAlpha: 0 });

                var tl = gsap.timeline({
                    defaults: { ease: 'power3.out', duration: 1 },
                    scrollTrigger: {
                        trigger: pin,
                        start: 'top top',
                        {{-- Was +=3000. The timeline lost card 4's exit tween
                             (~1 unit of ~6.2), so the pin no longer needs to hold
                             for it — 2500 keeps the same scroll feel per card and
                             releases right after card 4 rests. --}}
                        end: '+=2500',
                        pin: pin,
                        scrub: 1.5,
                        anticipatePin: 1,
                        invalidateOnRefresh: true
                    }
                });

                function toSlot(card, slotIdx, pos) {
                    var s = slots[slotIdx];
                    tl.to(card, { yPercent: s.yp, scale: s.scale, autoAlpha: s.op, filter: 'blur(' + s.blur + 'px)' }, pos);
                }
                function toExit(card, pos) { tl.to(card, EXIT, pos); }
                function pillsIn(card, pos) {
                    tl.fromTo(card.querySelectorAll('.hm-why__pill'),
                        { xPercent: -45, autoAlpha: 0 },
                        { xPercent: 0, autoAlpha: 1, duration: .5, ease: 'power3.out', stagger: 0.14 }, pos);
                }

                // Timeline uses a moving playhead: every card RESTS fully in view
                // (HOLD) before it slides away (MOVE) and the next rises up.
                var HOLD = 0.55;   // scroll distance a card stays fully shown
                var MOVE = 1;      // the exit / rise transition
                var t = 0;

                pillsIn(cards[0], 0.1);        // card 1 pills reveal while it rests
                t += HOLD;                      // card 1 fully shown

                // Card 1 → out, card 2 → front, deck shifts forward.
                toExit(cards[0], t);
                toSlot(cards[1], 0, t);
                toSlot(cards[2], 1, t);
                toSlot(cards[3], 2, t);
                pillsIn(cards[1], t + 0.35);
                t += MOVE + HOLD;               // card 2 fully shown

                // Card 2 → out, card 3 → front.
                toExit(cards[1], t);
                toSlot(cards[2], 0, t);
                toSlot(cards[3], 1, t);
                pillsIn(cards[2], t + 0.35);
                t += MOVE + HOLD;               // card 3 fully shown

                // Card 3 → out, card 4 → front.
                toExit(cards[2], t);
                toSlot(cards[3], 0, t);
                pillsIn(cards[3], t + 0.35);
                t += MOVE;                      // t = card 4 has arrived at the front

                // Card 4 rests at the front, then the pin releases straight into
                // the next section. It is NOT slid away — exiting it (as the other
                // cards do) left the pinned viewport EMPTY for the rest of the
                // scroll, which was the big blank gap before Upcoming Events.
                tl.to(cards[3], { yPercent: slots[0].yp, duration: HOLD }, t);
                t += HOLD;                      // card 4 held, pin about to release

                return function () {
                    stack.classList.remove('is-gsap');
                    pin.classList.remove('is-gsap');
                    if (tl.scrollTrigger) tl.scrollTrigger.kill();
                    tl.kill();
                    gsap.set(cards, { clearProps: 'all' });
                    gsap.set(stack.querySelectorAll('.hm-why__pill'), { clearProps: 'all' });
                };
            });

            /* ---- Mobile: native scroll-snap slider, pills reveal per card ---- */
            mm.add('(max-width: 991.98px)', function () {
                gsap.set(cards, { clearProps: 'all' });
                if (reduce || !('IntersectionObserver' in window)) return;

                gsap.set(stack.querySelectorAll('.hm-why__pill'), { xPercent: -45, autoAlpha: 0 });
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) { pillsFromTo(e.target); io.unobserve(e.target); }
                    });
                }, { threshold: 0.5 });
                cards.forEach(function (c) { io.observe(c); });

                return function () {
                    io.disconnect();
                    gsap.set(stack.querySelectorAll('.hm-why__pill'), { clearProps: 'all' });
                };
            });
        });
    </script>

    {{-- Upcoming Events — Cover-Flow rotational carousel (autoplay · swipe · keys) --}}
    <script>
        (function () {
            'use strict';

            var carousel = document.getElementById('hmEvCarousel');
            var track    = document.getElementById('hmEvTrack');
            if (!carousel || !track) return;

            var cards = Array.prototype.slice.call(track.querySelectorAll('.hm-ev-card'));
            if (cards.length < 3) return;

            var prevBtn = document.getElementById('hmEvPrev');
            var nextBtn = document.getElementById('hmEvNext');
            var section = document.querySelector('.hm-events');
            var reduce  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // order = [leftIndex, centerIndex, rightIndex, ...hidden]
            var order = cards.map(function (_, i) { return i; });   // [0,1,2,...]
            // Start with the middle card centred: order -> [left, center, right].
            order = [0, 1, 2].concat(order.slice(3));

            function paint() {
                cards.forEach(function (c) {
                    c.classList.remove('is-left', 'is-center', 'is-right', 'is-hidden');
                    c.setAttribute('aria-hidden', 'true');
                });
                cards[order[0]].classList.add('is-left');
                cards[order[1]].classList.add('is-center');
                cards[order[2]].classList.add('is-right');
                cards[order[1]].setAttribute('aria-hidden', 'false');
                for (var i = 3; i < order.length; i++) cards[order[i]].classList.add('is-hidden');
            }

            // Right arrow: centre→right, right→left, left→centre.
            function next() { order = [order[order.length - 1]].concat(order.slice(0, order.length - 1)); paint(); }
            // Left arrow: centre→left, left→right, right→centre.
            function prev() { order = order.slice(1).concat(order[0]); paint(); }

            /* ---- Autoplay (5s) — pauses on hover / when tab hidden ---- */
            var timer = null;
            function play() { stop(); if (!reduce) timer = window.setInterval(next, 5000); }
            function stop() { if (timer) { window.clearInterval(timer); timer = null; } }

            if (nextBtn) nextBtn.addEventListener('click', function () { next(); play(); });
            if (prevBtn) prevBtn.addEventListener('click', function () { prev(); play(); });

            (section || carousel).addEventListener('mouseenter', stop);
            (section || carousel).addEventListener('mouseleave', function () { if (started) play(); });
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) stop(); else if (started) play();
            });

            /* ---- Click a side card to bring it to the centre ---- */
            cards.forEach(function (card) {
                card.addEventListener('click', function (e) {
                    if (card.classList.contains('is-center')) return;   // let its button work
                    e.preventDefault();
                    if (card.classList.contains('is-left')) next();
                    else if (card.classList.contains('is-right')) prev();
                    play();
                });
            });

            /* ---- Keyboard (arrow keys) ---- */
            carousel.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowLeft') { e.preventDefault(); prev(); play(); }
                else if (e.key === 'ArrowRight') { e.preventDefault(); next(); play(); }
            });

            /* ---- Touch swipe (mobile) ---- */
            var startX = null;
            track.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
            track.addEventListener('touchend', function (e) {
                if (startX === null) return;
                var dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) > 40) { if (dx < 0) next(); else prev(); play(); }
                startX = null;
            }, { passive: true });

            /* ---- Entrance — heading fades down, cards reveal centre → sides ---- */
            var started = false;
            function reveal() {
                if (reduce) { cards.forEach(function (c) { c.classList.add('ev-revealed'); }); started = true; play(); return; }
                var seq = [order[1], order[0], order[2]];   // centre first, then left, right
                seq.forEach(function (idx, i) {
                    window.setTimeout(function () { cards[idx].classList.add('ev-revealed'); }, i * 150);
                });
                started = true;
                play();
            }

            paint();   // set initial positions (kept collapsed until revealed)

            if ('IntersectionObserver' in window) {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) return;
                        entry.target.classList.add('is-in');
                        if (entry.target === carousel) reveal();
                        io.unobserve(entry.target);
                    });
                }, { threshold: 0.2 });
                document.querySelectorAll('[data-ev-io]').forEach(function (el) { io.observe(el); });
            } else {
                document.querySelectorAll('[data-ev-io]').forEach(function (el) { el.classList.add('is-in'); });
                reveal();
            }
        })();
    </script>
@endpush
