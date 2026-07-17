@extends('frontend.layouts.template-base')

@section('title', 'Testimonials — Hire Minds Academy')
@section('meta_description', 'Real stories from Hire Minds Academy learners — how they gained industry-ready skills, secured opportunities, and built successful careers.')

@push('styles')
    {{-- Swiper — the shared Career Success slider is built on it --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" crossorigin="anonymous">
    {{-- Poppins — the heading typeface used across the site's hero/banner blocks --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">

    {{-- Shared sections, also used on the home page. testimonials.css carries
         BOTH the shared "Voices" section and this page's own styles, so it goes
         last — its page rules fit the sections above it to this layout. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/career-success.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/counters.css') }}">
    {{-- ?v=<file mtime> busts the browser cache whenever testimonials.css
         changes, so edits are never masked by a stale copy. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/testimonials.css') }}?v={{ filemtime(public_path('assets/css/frontend/testimonials.css')) }}">
@endpush

@section('content')

    {{-- ============================== BANNER ============================== --}}
    {{-- .hm-tst-page is also the gate the page-shell CSS keys off (see the
         :has() note in testimonials.css) — renaming it would strip the page
         back to the layout's default centred column. --}}
    <section class="hm-tst-page">
        <div class="container">
            <header class="hm-tst-page__banner">
                <img class="hm-tst-page__banner-img"
                     src="{{ asset('assets/images/testimonials/testimonials-header.webp') }}"
                     alt="" role="presentation">

                <div class="hm-tst-page__banner-content">
                    <h1 class="hm-tst-page__banner-title">Testimonials</h1>

                    <nav aria-label="Breadcrumb">
                        <ol class="hm-tst-page__crumbs">
                            <li><a href="{{ route('frontend.index') }}">Home</a></li>
                            <li class="hm-tst-page__crumb-sep" aria-hidden="true">&rsaquo;</li>
                            <li aria-current="page">Testimonials</li>
                        </ol>
                    </nav>
                </div>
            </header>
        </div>
    </section>

    {{-- =========================== CAREER SUCCESS ===========================
         The shared reel slider — same markup,  styling and Swiper behaviour as
         the home page. Only the header copy differs, so it is passed in rather
         than the section being rebuilt. --}}
    @include('frontend.partials.career-success', [
        'csLabel' => 'Career Success',
        'csTitle' => 'Turning Learning Into Career Success',
        'csDesc'  => 'Discover how our learners gained industry-ready skills, secured opportunities, and built successful careers through HireMinds Academy.',
    ])

    {{-- ============================== COUNTERS ==============================
         The shared component renders a bare .row, so it needs a container of
         its own here — on the home page it borrows the About section's. --}}
    <section class="hm-tst-page__counters" aria-label="Hire Minds Academy in numbers">
        <div class="container">
            @include('frontend.partials.counters')
        </div>
    </section>

    {{-- ============================ TESTIMONIALS ============================
         The shared "Voices of Career Transformation" section — floating
         profiles, review card, navigation and decorations all reused as-is.
         Its JS ships with the partial. --}}
    @include('frontend.partials.testimonials')

@endsection
