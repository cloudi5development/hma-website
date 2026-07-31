@extends('frontend.layouts.template-base')

@section('title', 'Contact Us — Hire Minds Academy')
@section('meta_description', 'Talk to Hire Minds Academy. Visit our Chennai or Coimbatore branch, call +91 78240 94044, or send us an enquiry — our team replies within 24 hours.')

@push('styles')
    {{-- Poppins — the heading typeface used across the site's hero/banner blocks --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">

    {{-- Shared sections, also used on the home page. contact.css must come LAST:
         it fits both of them to this page (spacing, backdrop) by overriding
         rules of equal specificity, which only works on load order. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/contact-form.css') }}?v={{ filemtime(public_path('assets/css/frontend/contact-form.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/faq.css') }}?v={{ filemtime(public_path('assets/css/frontend/faq.css')) }}">
    {{-- ?v=<file mtime> busts the browser cache whenever contact.css changes, so
         edits are never masked by a stale copy. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/contact.css') }}?v={{ filemtime(public_path('assets/css/frontend/contact.css')) }}">
@endpush

@section('content')

    @php
        // Branches drive both the switch buttons and the iframe. Adding a third
        // one here is all it takes — the markup and the JS both read this array.
        // 'map' must be an embeddable Maps URL (?output=embed); a plain
        // /maps/place link refuses to frame and renders blank.
        // Branches come from Settings → Contact ($contact, via AppServiceProvider).
        // Adding a branch there adds a tab here and a map to go with it; each one
        // carries its own embed URL, falling back to a pin from its address.
        $branches = collect($contact['branches'])->keyBy('key')->all();

        // The branch the map opens on — the first in the list.
        $defaultBranch = array_key_first($branches);
    @endphp

    {{-- ============================== BANNER ============================== --}}
    <section class="hm-cnt">
        <div class="container">
            <header class="hm-cnt__banner">
                {{-- The contact icons are part of the photograph, so nothing is
                     overlaid on the right-hand side. --}}
                {{-- WebP: the source PNG was 5120px wide and 3.2 MB for a banner that
                     never renders past the 1320px container. --}}
                <img class="hm-cnt__banner-img"
                     src="{{ asset('assets/images/contact-us/contact-us.webp') }}"
                     alt="" role="presentation" width="1920" height="347" fetchpriority="high">

                <div class="hm-cnt__banner-content">
                    <h1 class="hm-cnt__banner-title">Contact Us</h1>

                    <nav aria-label="Breadcrumb">
                        <ol class="hm-cnt__crumbs">
                            <li><a href="{{ route('frontend.index') }}">Home</a></li>
                            <li class="hm-cnt__crumb-sep" aria-hidden="true">&rsaquo;</li>
                            <li aria-current="page">Contact us</li>
                        </ol>
                    </nav>
                </div>
            </header>
        </div>
    </section>

    {{-- ======================= CONTACT INFO + FORM =======================
         The shared component — identical markup to the home page, including its
         validation JS. contact.css trims its home-page lead-in to fit here.

         $enquiryEvent is set only when the visitor arrived from an event's
         "Register Now" (?event=<slug>); the form then opens pre-filled. --}}
    @include('frontend.partials.contact-form', ['enquiryEvent' => $enquiryEvent])

    {{-- ================================ MAP ================================ --}}
    @if ($branches)
    <section class="hm-cnt-map" aria-labelledby="hmCntMapHeading">
        <div class="container">
            <h2 class="visually-hidden" id="hmCntMapHeading">Our branches on the map</h2>

            <div class="hm-cnt-map__card" data-hm-map>

                {{-- Exactly one branch is active at a time, so the buttons carry
                     aria-pressed rather than being links. --}}
                <div class="hm-cnt-map__switch" role="group" aria-label="Choose a branch">
                    @foreach ($branches as $key => $branch)
                        <button class="hm-cnt-map__btn"
                                type="button"
                                data-hm-branch="{{ $branch['name'] }}"
                                data-hm-map-src="{{ $branch['map'] }}"
                                aria-pressed="{{ $key === $defaultBranch ? 'true' : 'false' }}">
                            <i class="hm-cnt-map__btn-icon fa-solid fa-location-dot" aria-hidden="true"></i>
                            <span>{{ $branch['name'] }}</span>
                        </button>
                    @endforeach
                </div>

                <button class="hm-cnt-map__layers" type="button" aria-label="Map layers">
                    <img src="{{ asset('assets/images/contact-us/copy.png') }}" alt="" aria-hidden="true">
                </button>

                {{-- A real Google Maps embed, so zoom / pan / "View larger map"
                     all work. The JS swaps src to switch branches. --}}
                <iframe class="hm-cnt-map__frame"
                        id="hmCntMapFrame"
                        src="{{ $branches[$defaultBranch]['map'] }}"
                        title="Map of the {{ $branches[$defaultBranch]['name'] }} branch"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>
    @endif

@endsection

@push('scripts')
    {{-- Branch switch — swaps the iframe src in place, no reload. --}}
    <script>
        (function () {
            'use strict';

            var root = document.querySelector('[data-hm-map]');
            if (!root) return;

            var frame = document.getElementById('hmCntMapFrame');
            var btns  = root.querySelectorAll('[data-hm-branch]');
            if (!frame || !btns.length) return;

            btns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (btn.getAttribute('aria-pressed') === 'true') return;   // already showing

                    btns.forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
                    btn.setAttribute('aria-pressed', 'true');

                    frame.src = btn.getAttribute('data-hm-map-src');
                    // Keep the accessible name in step with what is on screen.
                    frame.title = 'Map of the ' + btn.getAttribute('data-hm-branch') + ' branch';
                });
            });
        })();
    </script>
@endpush
