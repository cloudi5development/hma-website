@extends('frontend.layouts.template-base')

@section('title', 'Hire Minds Academy — Learn, Practice, Get Hired')
@section('meta_description', 'Hire Minds Academy turns ambition into a career. Master in-demand tech skills through hands-on projects, real practice and mentorship — from your first line of code to your first job offer.')

@push('styles')
    {{-- Bootstrap 5 + Font Awesome are loaded site-wide via layouts/common-css --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" crossorigin="anonymous">
    {{-- Poppins — used only for the Upcoming Events heading (per its design spec) --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/home.css') }}">
@endpush

@section('content')

    {{-- Navbar lives in layouts/header.blade.php (global, reused on every page). --}}

    {{-- ================================== HERO ================================== --}}
    <section class="hm-hero" id="hero" aria-labelledby="hmHeroTitle">

        {{-- Blurred four-colour aurora (blue·violet·yellow·pink) rotating clockwise --}}
        <div class="hm-hero__glow" aria-hidden="true"></div>

        {{-- Slow-rotating holographic glow, blurred + low opacity, behind everything --}}
        <div class="hm-hero__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" fetchpriority="low">
        </div>

        <div class="container hm-hero__container">
            <div class="row align-items-center g-5">

                {{-- ---------------------------- LEFT COLUMN ---------------------------- --}}
                <div class="col-lg-6 hm-hero__left">
                    <span class="hm-badge hm-reveal" data-delay="100">
                        <span class="hm-badge__icon" aria-hidden="true"></span>
                        <span class="hm-badge__text">Learn &bull; Practice &bull; Get Hired</span>
                    </span>

                    <h1 class="hm-hero__title hm-reveal" data-delay="200" id="hmHeroTitle">
                        Your Future Starts With the Right Skills
                    </h1>

                    <p class="hm-hero__text hm-reveal" data-delay="300">
                        Build practical knowledge, work on real-world projects, and prepare for opportunities across today's fastest-growing industries.
                    </p>

                    <div class="hm-hero__actions hm-reveal" data-delay="400">
                        <a class="hm-btn hm-btn--primary" href="#courses">
                            <span class="hm-btn__label">Explore Course <i class="fa-solid fa-chevron-right"></i></span>
                        </a>
                        <a class="hm-btn hm-btn--ghost" href="{{ route('frontend.contact-us') }}">
                            <span class="hm-btn__label apply">Apply </span>
                        </a>
                    </div>
                </div>

                {{-- ---------------------------- RIGHT COLUMN ---------------------------- --}}
                <div class="col-lg-6 hm-hero__right">
                    <div class="hm-hero__visual" id="hmVisual">

                        <img class="hm-hero__student hm-reveal hm-reveal--fade" data-delay="200"
                             src="{{ asset('assets/images/Hero-section/hero-right-img.webp') }}"
                             alt="Smiling Hire Minds Academy student holding a notebook"
                             width="560" height="548" decoding="async" fetchpriority="high">

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
            <span class="hm-tech hm-tech--html hm-float-a hm-a hm-reveal hm-reveal--fade" data-delay="500" aria-hidden="true">
                <img src="{{ asset('assets/images/Hero-section/html.png') }}" alt="HTML5" width="30" height="30" loading="lazy">
            </span>
            <span class="hm-tech hm-tech--js hm-float-b hm-b hm-reveal hm-reveal--fade" data-delay="500" aria-hidden="true">
                <img src="{{ asset('assets/images/Hero-section/javascript.webp') }}" alt="JavaScript" width="30" height="30" loading="lazy">
            </span>

        </div>
    </section>

    {{-- ============================ TRUSTED PARTNERS ============================ --}}
    @php
        // Logos live in public/assets/images/partners-section/. Add a row here
        // (e.g. ['img' => 'flipkart.webp', 'name' => 'Flipkart']) once the image
        // exists and it will appear in the marquee automatically.
        $partners = [
            ['img' => 'amason.webp',    'name' => 'Amazon'],
            ['img' => 'google.webp',    'name' => 'Google'],
            ['img' => 'microsoft.webp', 'name' => 'Microsoft'],
            ['img' => 'tech.webp',      'name' => 'Tech'],
        ];
    @endphp
    <section class="hm-partners" aria-labelledby="hmPartnersTitle">
        <div class="container">
            <h2 class="hm-partners__title" id="hmPartnersTitle">Our Trusted Partners</h2>
        </div>

        {{-- Full-bleed marquee so the edge-fade sits at the screen edges. Two
             identical groups + translateX(-50%) = seamless infinite loop. --}}
        <div class="hm-partners__marquee">
            <div class="hm-partners__track">
                @for ($group = 0; $group < 2; $group++)
                    <ul class="hm-partners__group" @if ($group === 1) aria-hidden="true" @endif>
                        {{-- Each group repeats the set a few times so it always spans wider
                             than the viewport (no blank gap on large screens). --}}
                        @for ($repeat = 0; $repeat < 3; $repeat++)
                            @foreach ($partners as $partner)
                                <li class="hm-partners__item">
                                    <img class="hm-partners__logo"
                                         src="{{ asset('assets/images/partners-section/'.$partner['img']) }}"
                                         alt="{{ $partner['name'] }} Partner Logo"
                                         height="40" loading="lazy" draggable="false">
                                </li>
                            @endforeach
                        @endfor
                    </ul>
                @endfor
            </div>
        </div>
    </section>

    {{-- ============================ ABOUT US ============================ --}}
    <section class="hm-about" id="about" data-io aria-labelledby="hmAboutTitle">

        {{-- Same slow-rotating premium background as the hero --}}
        <div class="hm-about__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/about-us/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
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
            <div class="row hm-about__stats" role="list">
                <div class="col-6 col-lg-3 hm-stat hm-anim hm-anim--up hm-anim--d1" role="listitem">
                    <div class="hm-stat__num" data-target="2.5" data-decimals="1" data-suffix="K+">2.5K+</div>
                    <div class="hm-stat__label">Students Trained</div>
                </div>
                <div class="col-6 col-lg-3 hm-stat hm-anim hm-anim--up hm-anim--d2" role="listitem">
                    <div class="hm-stat__num" data-target="150" data-decimals="0" data-suffix="+">150+</div>
                    <div class="hm-stat__label">Industry-Focused Courses</div>
                </div>
                <div class="col-6 col-lg-3 hm-stat hm-anim hm-anim--up hm-anim--d3" role="listitem">
                    <div class="hm-stat__num" data-target="95" data-decimals="0" data-suffix="%">95%</div>
                    <div class="hm-stat__label">Learner Satisfaction</div>
                </div>
                <div class="col-6 col-lg-3 hm-stat hm-anim hm-anim--up hm-anim--d4" role="listitem">
                    <div class="hm-stat__num" data-target="50" data-decimals="0" data-suffix="+">50+</div>
                    <div class="hm-stat__label">Hiring &amp; Training Partners</div>
                </div>
            </div>
        </div>
    </section>

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
                'bg'      => 'bg-1.png', 'component' => 'component-1.png', 'person' => 'person-1.png', 'fit' => 'tall',
            ],
            [
                'title'   => 'Career Support',
                'desc'    => 'Receive personalized career guidance with resume reviews, mock interviews, and placement assistance to help you confidently enter the job market.',
                'pills'   => ['Resume Building', 'Mock Interviews', 'Placement Support'],
                'tone'    => 'blue',
                'reverse' => true,
                'bg'      => 'bg-2.png', 'component' => 'component-2.png', 'person' => 'person-2.png', 'fit' => 'wide',
            ],
            [
                'title'   => 'Hands-On Learning',
                'desc'    => 'Learn by doing through live projects, real-world case studies, workshops, and practical assignments that build job-ready skills.',
                'pills'   => ['Live Projects', 'Practical Workshops', 'Case Studies'],
                'tone'    => 'yellow',
                'reverse' => false,
                'bg'      => 'bg-3.png', 'component' => 'component-3.png', 'person' => 'person-3.png', 'fit' => 'tall',
            ],
            [
                'title'   => 'Industry-Aligned Curriculum',
                'desc'    => 'Our curriculum is continuously updated with the latest tools, technologies, and industry trends to keep your skills relevant.',
                'pills'   => ['Latest Technologies', 'Updated Syllabus', 'In-Demand Skills'],
                'tone'    => 'pink',
                'reverse' => true,
                'bg'      => 'bg-4.png', 'component' => 'component-4.png', 'person' => 'person-4.png', 'fit' => 'wide',
            ],
        ];
    @endphp
    <section class="hm-why" id="why-choose" aria-labelledby="hm-why-title">
        {{-- Heading scrolls away with the page; only the card area pins below it. --}}
        <div class="hm-why__intro">
            <div class="container">
                <div class="hm-why__head">
                    <span class="hm-why__eyebrow"><i class="fa-solid fa-square"></i> Why Choose HireMinds</span>
                    <h2 class="hm-why__title" id="hm-why-title">Everything You Need to Launch a Successful Career</h2>
                    <p class="hm-why__lead">Gain practical skills, learn from industry experts, and receive career guidance that prepares you for real-world opportunities.</p>
                </div>
            </div>
        </div>

        <div class="hm-why__pin">
            {{-- Reused hero rotating background — same image / blur / opacity / 90s spin --}}
            <div class="hm-why__bg" aria-hidden="true">
                <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
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
                'person'  => 'person-2.png',
                'tone'    => 'purple',
            ],
            [
                'speaker' => 'Regina Phalange',
                'title'   => 'Nail your interviews',
                'type'    => 'Live Event',
                'price'   => '₹499/-',
                'link'    => '#',
                'person'  => 'person-3.png',
                'tone'    => 'teal',
            ],
            [
                'speaker' => 'Rachel Bennett',
                'title'   => 'Sell your first product online',
                'type'    => 'Live Event',
                'price'   => '₹499/-',
                'link'    => '#',
                'person'  => 'person-1.png',
                'tone'    => 'green',
            ],
        ];
    @endphp
    <section class="hm-events" id="events" aria-labelledby="hmEventsTitle">
        <div class="hm-events__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/events/events-bg.png') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container hm-events__container">
            <div class="hm-events__head" data-ev-io>
                <span class="hm-events__badge hm-ev-anim"><span class="hm-events__badge-sq"></span> Upcoming Event</span>
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

    {{-- ============================ TOP CATEGORIES ============================ --}}
    @php
        // Icons live in public/assets/images/categories/ (each is a coloured badge).
        // 'tone' selects the pastel card background defined in home.css.
        $categories = [
            ['name' => 'IT & Software',  'count' => '07 Courses', 'icon' => 'software.png',     'tone' => 'red'],
            ['name' => 'Cloud & DevOps', 'count' => '04 Courses', 'icon' => 'cloud.png',        'tone' => 'purple'],
            ['name' => 'Data & AI',      'count' => '06 Courses', 'icon' => 'data.png',         'tone' => 'teal'],
            ['name' => 'Cyber Security', 'count' => '03 Courses', 'icon' => 'security.png',      'tone' => 'pink'],
            ['name' => 'Engineering',    'count' => '05 Courses', 'icon' => 'engineering.png',   'tone' => 'blue'],
            ['name' => 'Communication',  'count' => '03 Courses', 'icon' => 'communication.png', 'tone' => 'gold'],
            ['name' => 'Leadership',     'count' => '02 Courses', 'icon' => 'leadership.png',    'tone' => 'peach'],
            ['name' => 'Finance',        'count' => '04 Courses', 'icon' => 'finance.png',       'tone' => 'green'],
            ['name' => 'Data & AI',      'count' => '06 Courses', 'icon' => 'data.png',         'tone' => 'teal'],
            ['name' => 'Cyber Security', 'count' => '03 Courses', 'icon' => 'security.png',      'tone' => 'pink'],
            ['name' => 'IT & Software',  'count' => '07 Courses', 'icon' => 'software.png',     'tone' => 'red'],
            ['name' => 'Cloud & DevOps', 'count' => '04 Courses', 'icon' => 'cloud.png',        'tone' => 'purple'],
        ];
    @endphp
    <section class="hm-cats" id="categories" data-io aria-labelledby="hmCatsTitle">

        {{-- Same slow-rotating premium background as the hero --}}
        <div class="hm-cats__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/categories/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
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
                                <img src="{{ asset('assets/images/categories/'.$cat['icon']) }}"
                                     alt="{{ $cat['name'] }} icon" width="56" height="56" loading="lazy">
                            </span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================ POPULAR COURSES ============================ --}}
    @php
        // Thumbnails live in public/assets/images/courses/ (each is a composed image).
        $courses = [
            ['img' => 'course-1.webp', 'badge' => 'Development',     'title' => 'Learning JavaScript With Imagination', 'rating' => '4.5'],
            ['img' => 'course-2.png',  'badge' => 'Corporate',       'title' => 'Learning JavaScript With Imagination', 'rating' => '4.5'],
            ['img' => 'course-3.png',  'badge' => 'Team Leadership',  'title' => 'Learning JavaScript With Imagination', 'rating' => '4.5'],
            ['img' => 'course-4.webp', 'badge' => 'Career Readiness', 'title' => 'Learning JavaScript With Imagination', 'rating' => '4.5'],
        ];
    @endphp
    <section class="hm-courses" id="courses" data-io aria-labelledby="hmCoursesTitle">

        {{-- Same slow-rotating premium background as the hero --}}
        <div class="hm-courses__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
        </div>

        {{-- Decorative dotted / striped shapes --}}
        <img class="hm-courses__deco hm-courses__deco--tl" aria-hidden="true"
             src="{{ asset('assets/images/courses/Component 34.png') }}" alt="" loading="lazy">
        <img class="hm-courses__deco hm-courses__deco--br" aria-hidden="true"
             src="{{ asset('assets/images/courses/Group (4).png') }}" alt="" loading="lazy">

        <div class="container hm-courses__container">

            {{-- Header --}}
            <div class="hm-courses__head">
                <img class="hm-courses__float" aria-hidden="true"
                     src="{{ asset('assets/images/courses/Group 258.png') }}" alt="" loading="lazy">

                <span class="hm-courses__label hm-anim hm-anim--up">
                    <span class="hm-courses__label-icon" aria-hidden="true"></span>
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
                        <article class="hm-course hm-anim hm-anim--up hm-anim--d{{ ($i % 4) + 1 }}">
                            <div class="hm-course__thumb">
                                <img src="{{ asset('assets/images/courses/'.$course['img']) }}"
                                     alt="{{ $course['title'] }} course thumbnail" loading="lazy">
                            </div>
                            <div class="hm-course__body">
                                <div class="hm-course__tags">
                                    <span class="hm-course__badge">{{ $course['badge'] }}</span>
                                    <span class="hm-course__rating">
                                        <img src="{{ asset('assets/images/courses/noto_star (1).png') }}" alt="" aria-hidden="true"> {{ $course['rating'] }}
                                    </span>
                                </div>
                                <h3 class="hm-course__title">{{ $course['title'] }}</h3>
                                <ul class="hm-course__meta">
                                    <li class="hm-course__meta-row">
                                        <span class="hm-course__meta-item">
                                            <img src="{{ asset('assets/images/courses/iconamoon_clock-light.png') }}" alt="" aria-hidden="true"> 3 months
                                        </span>
                                        <span class="hm-course__meta-item">
                                            <img src="{{ asset('assets/images/courses/school.png') }}" alt="" aria-hidden="true"> On-Campus Learning
                                        </span>
                                    </li>
                                    <li class="hm-course__meta-row">
                                        <span class="hm-course__meta-item">
                                            <i class="fa-regular fa-circle-check" aria-hidden="true"></i> Industry Certificate
                                        </span>
                                    </li>
                                </ul>
                                <a class="hm-course__btn" href="#">
                                    <span>View Course</span>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    </div>
                @endforeach
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
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container hm-stories__container">

            {{-- Header --}}
            <div class="hm-stories__head">
                <span class="hm-stories__label hm-anim hm-anim--up">
                    <span class="hm-stories__label-icon" aria-hidden="true"></span>
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
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container hm-blogs__container">

            {{-- Header --}}
            <div class="hm-blogs__head">
                <span class="hm-blogs__label hm-anim hm-anim--up">
                    <span class="hm-blogs__label-icon" aria-hidden="true"></span>
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
    @php
        // 12-strong pool (the 6 images repeat). The stage shows 9 at a time and the
        // nav arrows page through the pool. Full image URLs so the JS can reuse them.
        $rimg = fn ($n) => asset('assets/images/review/customer-'.$n.'.png');
        $pool = [
            ['img' => $rimg(1), 'name' => 'Crystal Maiden', 'role' => 'UI/UX Designer',    'review' => "The mentorship here is on another level. Every project pushed me to think like a real designer, and the feedback was honest and practical. I landed my dream role within weeks of finishing."],
            ['img' => $rimg(2), 'name' => 'Arjun Mehta',    'role' => 'Software Developer', 'review' => "I came in knowing almost nothing and left building full applications with confidence. The hands-on approach and constant support made all the difference in my career."],
            ['img' => $rimg(3), 'name' => 'Priya Nair',     'role' => 'Data Analyst',       'review' => "What stood out was how industry-focused everything felt. Real datasets, real problems, real interviews. I felt prepared from day one when I stepped into my new job."],
            ['img' => $rimg(4), 'name' => 'Rahul Verma',    'role' => 'Frontend Engineer',  'review' => "The trainers genuinely care about your growth. They answered every doubt and helped me polish my portfolio until it truly stood out to recruiters."],
            ['img' => $rimg(5), 'name' => 'Sneha Kapoor',   'role' => 'Product Manager',    'review' => "From resume reviews to mock interviews, the career guidance was incredible. I switched fields completely and still felt supported every single step of the way."],
            ['img' => $rimg(6), 'name' => 'Vikram Singh',   'role' => 'DevOps Engineer',    'review' => "Practical, intense, and worth every minute. The projects mirror exactly what companies expect, so the transition into my first role felt seamless and natural."],
            ['img' => $rimg(1), 'name' => 'Ananya Rao',     'role' => 'Business Analyst',    'review' => "I joined unsure of my direction and left with a clear path and a job offer. The structured roadmap and mentor check-ins kept me motivated the whole way through."],
            ['img' => $rimg(2), 'name' => 'Karan Malhotra', 'role' => 'Cloud Engineer',     'review' => "The labs felt exactly like a real workplace. By the time I interviewed, nothing surprised me — I had already solved similar problems dozens of times here."],
            ['img' => $rimg(3), 'name' => 'Meera Iyer',     'role' => 'QA Engineer',        'review' => "Supportive community, sharp instructors, and projects that actually matter. I rebuilt my confidence and my resume at the same time, and it paid off quickly."],
            ['img' => $rimg(4), 'name' => 'Rohan Das',      'role' => 'Backend Developer',  'review' => "Every doubt I raised got a thoughtful answer. The pace was challenging but fair, and the placement team stayed with me until I signed my offer letter."],
            ['img' => $rimg(5), 'name' => 'Divya Menon',    'role' => 'Digital Marketer',   'review' => "They don't just teach tools, they teach how to think. That mindset shift is what got me hired over candidates with far more experience than me."],
            ['img' => $rimg(6), 'name' => 'Aditya Joshi',   'role' => 'ML Engineer',        'review' => "From fundamentals to deployment, everything connected. I walked into my first role already comfortable shipping real features to real users."],
        ];
    @endphp
    <section class="hm-tst" id="testimonials" data-io aria-labelledby="hmTstTitle">

        {{-- Same slow-rotating hero background (shows faintly through the panel) --}}
        <div class="hm-tst__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container">
            <div class="hm-tst__panel">

                {{-- Decorative striped / dotted graphics --}}
                <span class="hm-tst__deco hm-tst__deco--stripe-tl" aria-hidden="true"></span>
                <span class="hm-tst__deco hm-tst__deco--dots-tr"  aria-hidden="true"></span>
                <span class="hm-tst__deco hm-tst__deco--dots-bl"  aria-hidden="true"></span>
                <span class="hm-tst__deco hm-tst__deco--stripe-br" aria-hidden="true"></span>
                <span class="hm-tst__deco hm-tst__deco--stripe-c" aria-hidden="true"></span>

                {{-- Header --}}
                <div class="hm-tst__head">
                    <span class="hm-tst__label hm-anim hm-anim--up">
                        <span class="hm-tst__label-icon" aria-hidden="true"></span>
                        <span class="hm-tst__label-text">Testimonials</span>
                    </span>
                    <h2 class="hm-tst__title hm-anim hm-anim--up hm-anim--d1" id="hmTstTitle">Voices of Career Transformation</h2>

                    <div class="hm-tst__badge hm-anim hm-anim--up hm-anim--d2">
                        <span class="hm-tst__badge-avatars">
                            @foreach (array_slice($pool, 0, 4) as $t)
                                <img src="{{ $t['img'] }}" alt="" aria-hidden="true">
                            @endforeach
                        </span>
                        <span class="hm-tst__badge-text">2k+ Learner</span>
                        <span class="hm-tst__badge-sep" aria-hidden="true"></span>
                        <span class="hm-tst__badge-rating"><i class="fa-solid fa-star" aria-hidden="true"></i> 4.8/5</span>
                        <span class="hm-tst__badge-sep" aria-hidden="true"></span>
                        <span class="hm-tst__badge-rating"><img class="hm-tst__badge-google" src="{{ asset('assets/images/Hero-section/google.png') }}" alt="Google"> 4.8/5</span>
                    </div>
                </div>

                {{-- Stage: 9 scattered profiles (positions in CSS) + the pop-up review card --}}
                <div class="hm-tst__stage" id="hmTstStage">
                    @for ($i = 0; $i < 9; $i++)
                        @php $t = $pool[$i % count($pool)]; @endphp
                        <button type="button"
                                class="hm-tst__profile hm-tst__profile--{{ $i + 1 }} hm-anim"
                                data-slot="{{ $i }}"
                                data-name="{{ $t['name'] }}"
                                data-role="{{ $t['role'] }}"
                                data-review="{{ $t['review'] }}"
                                aria-label="Show review from {{ $t['name'] }}">
                            <span class="hm-tst__profile-img">
                                <img src="{{ $t['img'] }}" alt="{{ $t['name'] }}" loading="lazy">
                            </span>
                        </button>
                    @endfor

                    {{-- Review card — pops up next to the active / hovered profile --}}
                    <div class="hm-tst__card-wrap" id="hmTstCardWrap">
                        <div class="hm-tst__card is-swap" id="hmTstCard">
                            <div class="hm-tst__card-top">
                                <img class="hm-tst__card-avatar" id="hmTstAvatar" src="{{ $pool[0]['img'] }}" alt="{{ $pool[0]['name'] }}">
                                <div>
                                    <div class="hm-tst__card-name" id="hmTstName">{{ $pool[0]['name'] }}</div>
                                    <div class="hm-tst__card-role" id="hmTstRole">{{ $pool[0]['role'] }}</div>
                                </div>
                            </div>
                            <p class="hm-tst__card-text" id="hmTstText">{{ $pool[0]['review'] }}</p>
                            <div class="hm-tst__card-stars" aria-label="Rated 5 out of 5">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Full pool the arrows page through (data only) --}}
                    <script type="application/json" id="hmTstData">@json($pool)</script>
                </div>

                {{-- Navigation --}}
                <div class="hm-tst__nav hm-anim hm-anim--up hm-anim--d3">
                    <button type="button" class="hm-tst__navbtn hm-tst__navbtn--prev" id="hmTstPrev" aria-label="Previous review">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="hm-tst__navbtn hm-tst__navbtn--next" id="hmTstNext" aria-label="Next review">
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ FAQ ============================ --}}
    @php
        $faqs = [
            ['q' => 'What courses does Hire Minds Academy offer?', 'a' => 'Our courses span in-demand fields like software development, data & AI, cloud & DevOps, cyber security, and professional skills. Every program is designed by industry experts and blends practical learning, live projects, interview preparation, and dedicated placement support so you graduate genuinely job-ready.'],
            ['q' => 'How long are the training programs?', 'a' => 'Most tracks run between three and six months depending on the depth you choose. We offer flexible weekday and weekend batches along with self-paced modules, so you can learn effectively whether you are a student, a working professional, or switching careers.'],
            ['q' => 'Will I receive placement assistance?', 'a' => 'Yes. Every learner gets end-to-end placement support including resume building, mock interviews, portfolio reviews, and direct referrals to our hiring partners. Our career team stays with you from your very first module until you sign your offer letter.'],
            ['q' => 'Do I receive a course certificate?', 'a' => 'Absolutely. On successful completion of your program and final projects, you receive an industry-recognized certificate from Hire Minds Academy that you can add to your resume and LinkedIn to showcase your verified, job-ready skills to recruiters.'],
            ['q' => 'Can beginners join these courses?', 'a' => 'Definitely. Our programs are structured to take complete beginners from the fundamentals all the way to advanced, real-world skills. With mentor support, hands-on labs, and a friendly community, no prior experience is required to get started.'],
        ];
    @endphp
    <section class="hm-faq" id="faq" data-io aria-labelledby="hmFaqTitle">

        {{-- Same slow-rotating hero background + soft warm overlay --}}
        <div class="hm-faq__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
        </div>
        <div class="hm-faq__overlay" aria-hidden="true"></div>

        <div class="container hm-faq__container">
            <div class="row align-items-center g-5">

                {{-- Left: heading + description + illustration --}}
                <div class="col-lg-5 hm-faq__left hm-anim hm-anim--left">
                    <span class="hm-faq__label">
                        <span class="hm-faq__label-icon" aria-hidden="true"></span>
                        <span class="hm-faq__label-text">FAQ</span>
                    </span>
                    <h2 class="hm-faq__title" id="hmFaqTitle">Frequently Asking Questions</h2>
                    <p class="hm-faq__desc">
                        Gain practical skills, learn from industry experts, and receive career guidance
                        that prepares you for real-world opportunities.
                    </p>

                    <div class="hm-faq__visual">
                        <img class="hm-faq__deco hm-faq__deco--star1" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">
                        <img class="hm-faq__deco hm-faq__deco--star2" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">
                        <img class="hm-faq__deco hm-faq__deco--dots" src="{{ asset('assets/images/faq/component.png') }}" alt="" aria-hidden="true" loading="lazy">
                        <span class="hm-faq__deco hm-faq__deco--stripe" aria-hidden="true"></span>
                        <img class="hm-faq__img" src="{{ asset('assets/images/faq/faq-img.png') }}"
                             alt="A learner considering the Hire Minds Academy programs" loading="lazy">
                    </div>
                </div>

                {{-- Right: Bootstrap accordion --}}
                <div class="col-lg-7 hm-faq__right">
                    <div class="accordion hm-faq__accordion" id="hmFaqAccordion">
                        @foreach ($faqs as $i => $faq)
                            @php $open = $i === 2; @endphp
                            <div class="accordion-item hm-faq__item hm-anim hm-anim--up hm-anim--d{{ $i + 1 }}">
                                <h3 class="accordion-header">
                                    <button class="accordion-button {{ $open ? '' : 'collapsed' }}" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#hmFaqBody{{ $i }}"
                                            aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="hmFaqBody{{ $i }}">
                                        {{ $faq['q'] }}
                                    </button>
                                </h3>
                                <div id="hmFaqBody{{ $i }}" class="accordion-collapse collapse {{ $open ? 'show' : '' }}"
                                     data-bs-parent="#hmFaqAccordion">
                                    <div class="accordion-body">{{ $faq['a'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ CONTACT / ENQUIRY ============================ --}}
    <section class="hm-contact" id="contact" data-io aria-labelledby="hmContactTitle">

        {{-- Same slow-rotating hero background --}}
        <div class="hm-contact__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container hm-contact__container">
            <div class="hm-contact__card">
                {{-- Subtle decorations --}}
                <img class="hm-contact__deco hm-contact__deco--star" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">
                <span class="hm-contact__deco hm-contact__deco--glow" aria-hidden="true"></span>

                <div class="row g-5 align-items-stretch">

                    {{-- Left: information --}}
                    <div class="col-lg-5 hm-contact__info hm-anim hm-anim--left">
                        <span class="hm-contact__label">
                            <span class="hm-contact__label-icon" aria-hidden="true"></span>
                            <span class="hm-contact__label-text">Let's Connect</span>
                        </span>
                        <h2 class="hm-contact__title" id="hmContactTitle">Let's Start Your Career Journey Together</h2>
                        <p class="hm-contact__desc">
                            Have questions about our programs or career support? Fill out the form and our
                            team will get back to you within 24 hours.
                        </p>

                        <h3 class="hm-contact__subtitle">Office Information</h3>
                        <ul class="hm-contact__list">
                            <li class="hm-contact__item">
                                <span class="hm-contact__icon" aria-hidden="true"><img class="hm-contact__icon-img" src="{{ asset('assets/images/contact-form/heroicons_map-pin.png') }}" alt=""></span>
                                <div class="hm-contact__item-body">
                                    <div class="hm-contact__item-title">Chennai Branch :</div>
                                    <p class="hm-contact__item-text">No 22 / 97, KGEYES VEDA RANGA NIVAS 4th Floor, 4th Avenue, Ashok Nagar, Chennai – 33</p>
                                </div>
                            </li>
                            <li class="hm-contact__item">
                                <span class="hm-contact__icon" aria-hidden="true"><img class="hm-contact__icon-img" src="{{ asset('assets/images/contact-form/heroicons_map-pin.png') }}" alt=""></span>
                                <div class="hm-contact__item-body">
                                    <div class="hm-contact__item-title">Coimbatore Branch :</div>
                                    <p class="hm-contact__item-text">339, Chinnasamy Naidu Rd, Siddhapudur, Balasundaram Layout, B.K.R Nagar, Coimbatore, Tamil Nadu 641044</p>
                                </div>
                            </li>
                            <li class="hm-contact__item">
                                <span class="hm-contact__icon" aria-hidden="true"><img class="hm-contact__icon-img" src="{{ asset('assets/images/contact-form/proicons_call.png') }}" alt=""></span>
                                <div class="hm-contact__item-body">
                                    <div class="hm-contact__item-title">Phone Number :</div>
                                    <p class="hm-contact__item-text"><a href="tel:+917824094044">+91 78240 94044</a></p>
                                </div>
                            </li>
                            <li class="hm-contact__item">
                                <span class="hm-contact__icon" aria-hidden="true"><img class="hm-contact__icon-img" src="{{ asset('assets/images/contact-form/heroicons-outline_mail.png') }}" alt=""></span>
                                <div class="hm-contact__item-body">
                                    <div class="hm-contact__item-title">Email :</div>
                                    <p class="hm-contact__item-text"><a href="mailto:info@hiremindsacademy.com">info@hiremindsacademy.com</a></p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    {{-- Right: enquiry form --}}
                    <div class="col-lg-7 hm-contact__formcol hm-anim hm-anim--right">
                        <form class="hm-contact__form needs-validation" id="hmContactForm" method="POST" action="#" novalidate>
                            @csrf

                            <div class="hm-field">
                                <label class="hm-field__label" for="cfName">Full Name</label>
                                <input type="text" class="form-control hm-input" id="cfName" name="name" placeholder="Alex Johnson" required>
                                <div class="invalid-feedback">Please enter your full name.</div>
                            </div>

                            <div class="hm-field">
                                <label class="hm-field__label" for="cfEmail">Email</label>
                                <input type="email" class="form-control hm-input" id="cfEmail" name="email" placeholder="example@gmail.com" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>

                            <div class="hm-field">
                                <label class="hm-field__label" for="cfPhone">Phone Number</label>
                                <div class="hm-phone">
                                    <span class="hm-phone__code">+91</span>
                                    <input type="tel" class="form-control hm-input hm-phone__input" id="cfPhone" name="phone"
                                           placeholder="Mobile Number" inputmode="numeric" pattern="[0-9]{10}" required>
                                    <div class="invalid-feedback">Enter a valid 10-digit mobile number.</div>
                                </div>
                            </div>

                            <div class="hm-field">
                                <label class="hm-field__label" for="cfLooking">What are you looking for?</label>
                                <select class="form-select hm-input hm-select" id="cfLooking" name="looking_for" required>
                                    <option value="" disabled selected hidden>Select the looking</option>
                                    <option>Course Information</option>
                                    <option>Placement Support</option>
                                    <option>Corporate Training</option>
                                    <option>Student Support</option>
                                    <option>General Enquiry</option>
                                </select>
                                <div class="invalid-feedback">Please choose an option.</div>
                            </div>

                            <div class="hm-field">
                                <label class="hm-field__label" for="cfInterest">Area of interest</label>
                                <select class="form-select hm-input hm-select" id="cfInterest" name="interest" required>
                                    <option value="" disabled selected hidden>Select the interest</option>
                                    <option>Data Analytics</option>
                                    <option>Full Stack Development</option>
                                    <option>HR Training</option>
                                    <option>Digital Marketing</option>
                                    <option>UI/UX Design</option>
                                    <option>AI &amp; Machine Learning</option>
                                </select>
                                <div class="invalid-feedback">Please choose an area of interest.</div>
                            </div>

                            <div class="hm-field">
                                <label class="hm-field__label" for="cfMessage">Message</label>
                                <textarea class="form-control hm-input hm-textarea" id="cfMessage" name="message" placeholder="Tell us how we can help you..."></textarea>
                            </div>

                            <button type="submit" class="hm-contact__submit">
                                <span class="hm-btn__label">Send Enquiry <i class="fa-solid fa-paper-plane" aria-hidden="true"></i></span>
                            </button>

                            <p class="hm-contact__note" id="hmContactNote" role="status" hidden>
                                Thanks! Your enquiry has been received — our team will get back to you within 24 hours.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ OUR JOURNEY / REELS ============================ --}}
    @php
        // Covers live in public/assets/images/our-journey/. Replace the 'url'
        // values with the real Instagram Reel links whenever they are ready.
        $reels = [
            ['image' => 'reel-1.webp', 'url' => 'https://www.instagram.com/hireminds_academy/', 'title' => 'A mentoring session at Hire Minds Academy'],
            ['image' => 'reel-2.webp', 'url' => 'https://www.instagram.com/hireminds_academy/', 'title' => 'Inside a Hire Minds Academy classroom'],
            ['image' => 'reel-3.webp', 'url' => 'https://www.instagram.com/hireminds_academy/', 'title' => 'Learners collaborating on a live project'],
            ['image' => 'reel-4.webp', 'url' => 'https://www.instagram.com/hireminds_academy/', 'title' => 'A hands-on workshop moment'],
            ['image' => 'reel-5.webp', 'url' => 'https://www.instagram.com/hireminds_academy/', 'title' => 'Talent Acquisition and HR Recruitment training'],
        ];
    @endphp
    <section class="hm-reels" id="our-journey" data-io aria-labelledby="hmReelsTitle">

        {{-- Same slow-rotating hero background --}}
        <div class="hm-reels__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
        </div>
        <img class="hm-reels__deco hm-reels__deco--star1" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">
        <img class="hm-reels__deco hm-reels__deco--star2" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">

        <div class="container hm-reels__container">

            {{-- Header --}}
            <div class="hm-reels__head">
                <span class="hm-reels__label hm-anim hm-anim--up">
                    <span class="hm-reels__label-icon" aria-hidden="true"></span>
                    <span class="hm-reels__label-text">Our Journey</span>
                </span>
                <h2 class="hm-reels__title hm-anim hm-anim--up hm-anim--d1" id="hmReelsTitle">Watch Our Learning Journey</h2>
                <p class="hm-reels__desc hm-anim hm-anim--up hm-anim--d2">
                    Catch the latest classroom moments, workshops, and success stories.<br>
                    Follow <a class="hm-reels__handle" href="https://www.instagram.com/hireminds_academy/" target="_blank" rel="noopener"><i class="fa-brands fa-instagram" aria-hidden="true"></i> @hireminds_academy</a> for more exclusive updates.
                </p>
            </div>

            {{-- Swiper slider --}}
            <div class="swiper hm-reels__swiper hm-anim hm-anim--up hm-anim--d3">
                <div class="swiper-wrapper">
                    {{-- Rendered twice so there are more slides than are visible — this
                         gives the arrows / loop somewhere to advance to (5 images only). --}}
                    @foreach (array_merge($reels, $reels) as $reel)
                        <div class="swiper-slide hm-reels__slide">
                            <div class="hm-reel-float">
                                <div class="hm-reel" data-instagram="{{ $reel['url'] }}" role="link" tabindex="0"
                                     aria-label="Watch on Instagram: {{ $reel['title'] }}">
                                    <img class="hm-reel__img" src="{{ asset('assets/images/our-journey/'.$reel['image']) }}"
                                         alt="{{ $reel['title'] }}" loading="lazy">
                                    <span class="hm-reel__badge" aria-hidden="true"><i class="fa-brands fa-instagram"></i> Instagram Reel</span>
                                    <span class="hm-reel__overlay" aria-hidden="true"></span>
                                    <span class="hm-reel__play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
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

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>
    <script src="{{ asset('assets/js/frontend/home.js') }}" defer></script>

    {{-- Entrance reveals (About + Categories) + one-time stat counters (vanilla JS) --}}
    <script>
        (function () {
            'use strict';
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var hasIO = 'IntersectionObserver' in window;

            /* ---- Entrance reveals: add .is-in to each [data-io] section in view ---- */
            var ioSections = document.querySelectorAll('[data-io]');
            if (reduce || !hasIO) {
                ioSections.forEach(function (s) { s.classList.add('is-in'); });
            } else {
                var revealIO = new IntersectionObserver(function (entries, obs) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) { e.target.classList.add('is-in'); obs.unobserve(e.target); }
                    });
                }, { threshold: 0.15 });
                ioSections.forEach(function (s) { revealIO.observe(s); });
            }

            /* ---- Count-up statistics (About) — runs once, then holds the final value ---- */
            var about = document.getElementById('about');
            if (!about) return;
            var nums = about.querySelectorAll('.hm-stat__num');

            function format(value, decimals, suffix) {
                return value.toFixed(decimals) + suffix;
            }

            function runCounters() {
                nums.forEach(function (el) {
                    var target = parseFloat(el.getAttribute('data-target')) || 0;
                    var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
                    var suffix = el.getAttribute('data-suffix') || '';

                    if (reduce) { el.textContent = format(target, decimals, suffix); return; }

                    var duration = 1800, startTime = null;
                    function tick(now) {
                        if (startTime === null) startTime = now;
                        var p = Math.min((now - startTime) / duration, 1);
                        var eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
                        el.textContent = format(target * eased, decimals, suffix);
                        if (p < 1) {
                            requestAnimationFrame(tick);
                        } else {
                            el.textContent = format(target, decimals, suffix); // snap to exact final
                        }
                    }
                    requestAnimationFrame(tick);
                });
            }

            var stats = about.querySelector('.hm-about__stats');
            if (!stats || reduce || !hasIO) {
                runCounters();
            } else {
                new IntersectionObserver(function (entries, obs) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) { runCounters(); obs.disconnect(); }
                    });
                }, { threshold: 0.4 }).observe(stats);
            }
        })();
    </script>

    {{-- Testimonials: hover a profile → review card pops up beside it;
         arrows page 9 new people in from the pool --}}
    <script>
        (function () {
            'use strict';
            var stage = document.getElementById('hmTstStage');
            var cardWrap = document.getElementById('hmTstCardWrap');
            var card = document.getElementById('hmTstCard');
            if (!stage || !cardWrap || !card) return;

            var pool = [];
            var dataEl = document.getElementById('hmTstData');
            try { pool = JSON.parse(dataEl.textContent); } catch (e) { pool = []; }

            var slots = Array.prototype.slice.call(stage.querySelectorAll('.hm-tst__profile'));
            var avatar = document.getElementById('hmTstAvatar');
            var nameEl = document.getElementById('hmTstName');
            var roleEl = document.getElementById('hmTstRole');
            var textEl = document.getElementById('hmTstText');
            var prevBtn = document.getElementById('hmTstPrev');
            var nextBtn = document.getElementById('hmTstNext');
            var offset = 0, active = 0;

            function poolAt(i) { return pool.length ? pool[(offset + i) % pool.length] : null; }

            // Repopulate the 9 slots from the current pool window (used by the arrows)
            function renderSlots() {
                slots.forEach(function (btn, i) {
                    var t = poolAt(i);
                    if (!t) return;
                    var img = btn.querySelector('img');
                    if (img) { img.src = t.img; img.alt = t.name; }
                    btn.dataset.name = t.name;
                    btn.dataset.role = t.role;
                    btn.dataset.review = t.review;
                    btn.setAttribute('aria-label', 'Show review from ' + t.name);
                });
            }

            // Position the card right next to the given profile (clamped to the stage)
            function placeCard(btn) {
                var sw = stage.clientWidth, sh = stage.clientHeight;
                var cw = cardWrap.offsetWidth, ch = cardWrap.offsetHeight;
                var left = btn.offsetLeft + btn.offsetWidth / 2 - cw * 0.16;
                var top = btn.offsetTop + btn.offsetHeight * 0.55;
                left = Math.max(0, Math.min(left, Math.max(0, sw - cw)));
                top = Math.max(0, Math.min(top, Math.max(0, sh - ch)));
                cardWrap.style.left = left + 'px';
                cardWrap.style.top = top + 'px';
            }

            function activate(i) {
                var btn = slots[i];
                if (!btn) return;
                active = i;
                var img = btn.querySelector('img');
                card.classList.remove('is-swap'); void card.offsetWidth; card.classList.add('is-swap');
                if (img && avatar) { avatar.src = img.src; avatar.alt = btn.dataset.name || ''; }
                if (nameEl) nameEl.textContent = btn.dataset.name || '';
                if (roleEl) roleEl.textContent = btn.dataset.role || '';
                if (textEl) textEl.textContent = btn.dataset.review || '';
                slots.forEach(function (b, idx) { b.classList.toggle('is-active', idx === i); });
                placeCard(btn);
                cardWrap.classList.add('is-shown');
            }

            function paginate(dir) {
                if (!pool.length) return;
                offset = ((offset + dir * slots.length) % pool.length + pool.length) % pool.length;
                stage.classList.add('is-paging');
                renderSlots();
                activate(0);
                setTimeout(function () { stage.classList.remove('is-paging'); }, 70);
            }

            slots.forEach(function (btn, i) {
                btn.addEventListener('mouseenter', function () { activate(i); });
                btn.addEventListener('focus', function () { activate(i); });
                btn.addEventListener('click', function () { activate(i); });
            });
            if (nextBtn) nextBtn.addEventListener('click', function () { paginate(1); });
            if (prevBtn) prevBtn.addEventListener('click', function () { paginate(-1); });
            window.addEventListener('resize', function () { if (slots[active]) placeCard(slots[active]); });

            renderSlots();
            activate(0);
        })();
    </script>

    {{-- Contact form: Bootstrap-style validation, no page reload --}}
    <script>
        (function () {
            'use strict';
            var form = document.getElementById('hmContactForm');
            if (!form) return;
            var note = document.getElementById('hmContactNote');

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    var firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) firstInvalid.focus();
                    return;
                }
                // Valid — no backend yet, so acknowledge and reset in-place.
                form.classList.remove('was-validated');
                form.reset();
                if (note) {
                    note.hidden = false;
                    clearTimeout(note._t);
                    note._t = setTimeout(function () { note.hidden = true; }, 6000);
                }
            });
        })();
    </script>

    {{-- Our Journey reels — Swiper slider + click-to-open Instagram --}}
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" crossorigin="anonymous" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var swiperEl = document.querySelector('.hm-reels__swiper');
            if (!swiperEl || typeof Swiper === 'undefined') return;

            new Swiper(swiperEl, {
                slidesPerView: 1.2,
                spaceBetween: 20,
                loop: true,
                speed: 1000,
                grabCursor: true,
                autoplay: { delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true },
                navigation: { prevEl: '#hmReelsPrev', nextEl: '#hmReelsNext' },
                breakpoints: {
                    768:  { slidesPerView: 3, spaceBetween: 20 },
                    1200: { slidesPerView: 5, spaceBetween: 20 }
                }
            });

            // Whole card is clickable → open its Instagram Reel in a new tab.
            // Delegation covers Swiper's loop-cloned slides too.
            swiperEl.addEventListener('click', function (e) {
                var reel = e.target.closest('.hm-reel[data-instagram]');
                if (reel && reel.dataset.instagram) window.open(reel.dataset.instagram, '_blank', 'noopener');
            });
            swiperEl.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                var reel = e.target.closest('.hm-reel[data-instagram]');
                if (reel && reel.dataset.instagram) { e.preventDefault(); window.open(reel.dataset.instagram, '_blank', 'noopener'); }
            });
        });
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
                        end: '+=3000',
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
                t += MOVE + HOLD;               // card 4 fully shown

                // Card 4 → out, section releases into the next section.
                toExit(cards[3], t);

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
