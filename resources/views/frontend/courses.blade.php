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
        $thumbs = [
            ['img' => 'course-1.webp', 'badge' => 'Development',      'slug' => 'learning-javascript-development'],
            ['img' => 'course-4.webp', 'badge' => 'Career Readiness', 'slug' => 'learning-javascript-career'],
            ['img' => 'course-3.png',  'badge' => 'Team Leadership',  'slug' => 'learning-javascript-leadership'],
            ['img' => 'course-2.png',  'badge' => 'Corporate',        'slug' => 'learning-javascript-corporate'],
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
            ];
        }

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

                <p class="hm-crs-bar__count">Showing {{ $from }}&ndash;{{ $to }} of {{ $total }} Results</p>
            </div>

            {{-- =============================== GRID =============================== --}}
            <section aria-label="Courses">
                <div class="row hm-crs-grid">
                    @foreach ($courses as $i => $course)
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
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
