@extends('frontend.layouts.template-base')

@php
    // SEO: the event's own fields win, then its plain content, then the
    // site-wide SEO Defaults the layout partials fall back to.
    $metaTitle       = $event->seo_title ?: $event->title . ' — Hire Minds Academy';
    $metaDescription = $event->seo_description ?: $event->short_description;
    $ogTitle         = $event->seo_title ?: $event->title;
    $ogImage         = $event->og_image ? asset($event->og_image) : $event->banner_url;
@endphp

{{-- Block sections rather than the one-line @section('title', $value) form,
     because the inline form has two traps for values that come out of the
     database:

       • it stores e($value), and meta-tags/seo-content escape again when they
         yield it — so an "&" in the title reaches the browser as "&amp;", which
         is exactly what a title like "AI & Future Tech Bootcamp 2026" hits;
       • it only skips ob_start() when the value is not null, so a blank SEO
         field (@section('meta_keywords', null)) opens an output buffer that is
         never closed.

     A block stores the raw text and the layout escapes it exactly once. The
     guards keep an empty field from registering a section at all — an empty
     string would win over Settings → SEO Defaults instead of falling through
     to it. og_type stays inline: @yield echoes it unescaped, and it is a
     literal rather than admin input. --}}
@section('title'){!! $metaTitle !!}@endsection
@section('og_title'){!! $ogTitle !!}@endsection
@section('og_image'){!! $ogImage !!}@endsection
@section('og_type', 'article')

@if (filled($metaDescription))
    @section('meta_description'){!! $metaDescription !!}@endsection
    @section('og_description'){!! $metaDescription !!}@endsection
@endif

@if (filled($event->seo_keywords))
    @section('meta_keywords'){!! $event->seo_keywords !!}@endsection
@endif

@push('seo')
    @if (filled($event->schema_json))
        {{-- Raw JSON-LD the admin pasted in. Printed only when it parses, so a
             malformed paste can never emit a broken <script> into the head. --}}
        @php $schema = json_decode($event->schema_json, true); @endphp
        @if (json_last_error() === JSON_ERROR_NONE)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif
    @endif
@endpush

@push('styles')
    {{-- Poppins — the heading typeface used across the site's hero/banner blocks --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    {{-- Reused component styles, then the page's own last so it can fit them. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/frontend/faq.css') }}?v={{ filemtime(public_path('assets/css/frontend/faq.css')) }}">
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/event-details.css') }}?v={{ filemtime(public_path('assets/css/frontend/event-details.css')) }}">
@endpush

@section('content')

    @php
        // Registering normally opens the modal at the foot of this page. An event
        // that carries its own registration link is the exception: the admin filled
        // that in to send people somewhere specific (a ticketing site, a webinar
        // host), so it wins and the modal is not rendered at all.
        //
        // "#" counts as no link. It is the placeholder the older events were
        // seeded with, and treating it as a real address turned every Register
        // button on those pages into a dead link that went nowhere.
        $eventLink   = trim((string) $event->link);
        $registerUrl = ($eventLink === '' || $eventLink === '#') ? null : $eventLink;
    @endphp

    {{-- The modifier trims this section's bottom padding when the FAQ band
         follows it, so the two do not stack their padding into a gap. --}}
    <section class="hm-ed @if ($event->faqs->isNotEmpty()) hm-ed--with-faq @endif">
        <div class="container">

            {{-- ============================== BANNER ============================== --}}
            <header class="hm-ed__banner">
                <img class="hm-ed__banner-img" src="{{ $event->banner_url }}"
                     alt="" role="presentation" loading="eager" fetchpriority="high">

                <div class="hm-ed__banner-content">
                    <h1 class="hm-ed__banner-title">Upcoming Event</h1>

                    <nav aria-label="Breadcrumb">
                        <ol class="hm-ed__crumbs">
                            <li><a href="{{ route('frontend.index') }}">Home</a></li>
                            <li class="hm-ed__crumb-sep" aria-hidden="true">&rsaquo;</li>
                            <li><a href="{{ route('frontend.events') }}">Events</a></li>
                            <li class="hm-ed__crumb-sep" aria-hidden="true">&rsaquo;</li>
                            <li aria-current="page">Event Details</li>
                        </ol>
                    </nav>
                </div>
            </header>

            <div class="row hm-ed__layout">

                {{-- ============================ LEFT COLUMN ============================ --}}
                <div class="col-12 col-lg-8">

                    {{-- ------------------- Hero + summary: one card -------------------
                         The banner image and the detail block are a single panel in
                         the design — one outline, one shadow, no seam between them.
                         The wrapper owns the rounding and clips both children, so
                         neither needs to know which corners are its own. --}}
                    <article class="hm-ed-main hm-ed-anim">

                        <div class="hm-ed-hero__media hm-ed-hero__media--{{ $event->tone }}">
                            <div class="hm-ed-hero__body">
                                <span class="hm-ed-hero__speaker">{{ $event->speaker }}</span>
                                <h2 class="hm-ed-hero__title">{{ $event->title }}</h2>

                                @include('frontend.events.partials.register-button', [
                                    'class' => 'hm-ed-hero__cta', 'label' => 'Register Event', 'arrow' => true,
                                ])
                            </div>

                            <img class="hm-ed-hero__person" src="{{ $event->image_url }}"
                                 alt="{{ $event->speaker }}" loading="eager" decoding="async">
                        </div>

                    <div class="hm-ed-intro">
                        <h2 class="hm-ed-intro__title">{{ $event->title }}</h2>

                        @if ($event->short_description)
                            <p class="hm-ed-intro__desc">{{ $event->short_description }}</p>
                        @endif

                        @if ($event->organizer)
                            <p class="hm-ed-intro__org">
                                <span class="hm-ed-intro__org-mark" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v4.5c0 1 2.7 2.5 6 2.5s6-1.5 6-2.5V12"/>
                                    </svg>
                                </span>
                                {{ $event->organizer }}
                                <svg class="hm-ed-intro__verified" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 2 14.6 4.2 18 3.9l.8 3.3L21.8 9 20.4 12l1.4 3-3 1.8-.8 3.3-3.4-.3L12 22l-2.6-2.2-3.4.3-.8-3.3L2.2 15l1.4-3-1.4-3 3-1.8.8-3.3 3.4.3L12 2Zm-1.2 13.2 5-5-1.5-1.5-3.5 3.6-1.6-1.7-1.5 1.5 3.1 3.1Z"/>
                                </svg>
                            </p>
                        @endif

                        {{-- Date / time / location / seats --}}
                        <ul class="hm-ed-facts">
                            @if ($event->formatted_date)
                                <li class="hm-ed-facts__item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/>
                                    </svg>
                                    <span>
                                        <b>{{ $event->formatted_date }}</b>
                                        @if ($event->weekday) <small>{{ $event->weekday }}</small> @endif
                                    </span>
                                </li>
                            @endif

                            @if ($event->time_range)
                                <li class="hm-ed-facts__item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>
                                    </svg>
                                    <span><b>{{ $event->formatted_time }}</b>
                                        @if ($event->formatted_end_time) <small>to {{ $event->formatted_end_time }}</small> @endif
                                    </span>
                                </li>
                            @endif

                            @if ($event->full_address)
                                <li class="hm-ed-facts__item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>
                                    </svg>
                                    <span><b>{{ $event->full_address }}</b></span>
                                </li>
                            @endif

                            @if ($event->seats_left !== null)
                                <li class="hm-ed-facts__item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="9" cy="8" r="3.4"/><path d="M3 20c0-3.3 2.7-5.4 6-5.4s6 2.1 6 5.4"/><path d="M16 4.6a3.4 3.4 0 0 1 0 6.8M21 20c0-2.6-1.5-4.3-4-5"/>
                                    </svg>
                                    <span>
                                        {{-- The headline number is the room size; the red line under it
                                             is what is still open. Same pairing as the sidebar card. --}}
                                        <b>{{ $event->total_seats ?: $event->seats_left }} Seats</b>
                                        <small class="hm-ed-facts__left">Only {{ $event->seats_left }} Left</small>
                                    </span>
                                </li>
                            @endif
                        </ul>

                        {{-- Price + register --}}
                        <div class="hm-ed-price">
                            <div class="hm-ed-price__col">
                                @if ($event->has_offer)
                                    <span class="hm-ed-price__label">Early Bird Price</span>
                                @endif

                                <div class="hm-ed-price__figures">
                                    @if ($event->payable_price)
                                        <span class="hm-ed-price__now">{{ $event->payable_price }}</span>
                                    @endif

                                    @if ($event->has_offer)
                                        <span class="hm-ed-price__was">{{ $event->price }}</span>
                                    @endif

                                    @if ($event->discount_percentage)
                                        <span class="hm-ed-price__off">{{ $event->discount_percentage }}% OFF</span>
                                    @endif
                                </div>
                            </div>

                            @include('frontend.events.partials.register-button', [
                                'class' => 'hm-ed-btn hm-ed-btn--primary',
                            ])
                        </div>

                        @if ($event->duration || $event->language || $event->level)
                            <ul class="hm-ed-chips">
                                @foreach (array_filter([$event->duration, $event->language, $event->level]) as $chip)
                                    <li>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9"/><path d="m8.4 12.2 2.5 2.5 4.7-4.9"/>
                                        </svg>
                                        {{ $chip }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    </article>

                    {{-- ------------------------ What You Will Learn ------------------------ --}}
                    @if ($event->highlights->isNotEmpty())
                        <section class="hm-ed-sec hm-ed-anim" aria-labelledby="hmEdLearn">
                            <h2 class="hm-ed-sec__title" id="hmEdLearn">What You Will Learn</h2>

                            @php
                                // A tint per card, cycled by position so neighbours never
                                // match. Position rather than icon key: the admin reorders
                                // rows with the arrows and the row of colours should stay
                                // varied whatever icons they picked.
                                $learnTones = ['green', 'amber', 'teal', 'pink', 'blue', 'lilac'];
                            @endphp

                            <div class="row hm-ed-learn">
                                @foreach ($event->highlights as $highlight)
                                    <div class="col-6 col-md-4 col-xl">
                                        <div class="hm-ed-learn__card">
                                            {{-- The glyph is painted by a CSS mask, not drawn as an
                                                 <img>: the icons-details artwork is a near-black
                                                 outline, and masking lets it take the tile's ink
                                                 while still being the admin's chosen drawing. --}}
                                            <span class="hm-ed-learn__icon hm-ed-learn__icon--{{ $learnTones[$loop->index % count($learnTones)] }}"
                                                  aria-hidden="true">
                                                <i class="hm-ed-learn__glyph"
                                                   style="--hm-ed-icon:url('{{ $highlight->icon_url }}')"></i>
                                            </span>
                                            <h3 class="hm-ed-learn__title">{{ $highlight->title }}</h3>
                                            @if ($highlight->description)
                                                <p class="hm-ed-learn__desc">{{ $highlight->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- --------------------------- Description --------------------------- --}}
                    @if ($event->description)
                        <section class="hm-ed-sec hm-ed-anim" aria-labelledby="hmEdAbout">
                            <h2 class="hm-ed-sec__title" id="hmEdAbout">About This Event</h2>
                            <div class="hm-ed-sec__body">{!! nl2br(e($event->description)) !!}</div>
                        </section>
                    @endif

                    {{-- ------------------------- Meet Our Speakers ------------------------- --}}
                    @if ($event->speakers->isNotEmpty())
                        <section class="hm-ed-sec hm-ed-anim" aria-labelledby="hmEdSpeakers">
                            <h2 class="hm-ed-sec__title" id="hmEdSpeakers">Meet Our Speakers</h2>

                            <div class="row hm-ed-speakers">
                                @foreach ($event->speakers as $speaker)
                                    <div class="col-12 col-sm-6 col-xl-4">
                                        <article class="hm-ed-speaker">
                                            @if ($speaker->photo)
                                                <img class="hm-ed-speaker__photo" src="{{ asset($speaker->photo) }}"
                                                     alt="{{ $speaker->name }}" width="54" height="54"
                                                     loading="lazy" decoding="async">
                                            @else
                                                <span class="hm-ed-speaker__photo hm-ed-speaker__photo--blank" aria-hidden="true">
                                                    {{ Str::substr($speaker->name, 0, 1) }}
                                                </span>
                                            @endif

                                            <div class="hm-ed-speaker__who">
                                                <h3 class="hm-ed-speaker__name">{{ $speaker->name }}</h3>
                                                @if ($speaker->designation)
                                                    <p class="hm-ed-speaker__role">{{ $speaker->designation }}</p>
                                                @endif
                                                @if ($speaker->company)
                                                    <p class="hm-ed-speaker__co">{{ $speaker->company }}</p>
                                                @endif
                                            </div>

                                            @if ($speaker->linkedin)
                                                <a class="hm-ed-speaker__in" href="{{ $speaker->linkedin }}"
                                                   target="_blank" rel="noopener noreferrer"
                                                   aria-label="{{ $speaker->name }} on LinkedIn">
                                                    <i class="fa-brands fa-linkedin-in" aria-hidden="true"></i>
                                                </a>
                                            @endif
                                        </article>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                </div>

                {{-- =========================== RIGHT SIDEBAR =========================== --}}
                <div class="col-12 col-lg-4">
                    <aside class="hm-ed-side">

                        <div class="hm-ed-card hm-ed-anim">
                            <h2 class="hm-ed-card__title">Event Details</h2>

                            <ul class="hm-ed-card__list">
                                @if ($event->formatted_date)
                                    <li>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/>
                                        </svg>
                                        <span>{{ $event->formatted_date }}</span>
                                    </li>
                                @endif

                                @if ($event->formatted_time)
                                    <li>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>
                                        </svg>
                                        {{-- Start only, as in the design; the facts row above
                                             already carries the "to 06:00 PM" half. --}}
                                        <span>{{ $event->formatted_time }}</span>
                                    </li>
                                @endif

                                @if ($event->full_address)
                                    <li>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>
                                        </svg>
                                        <span>{{ $event->full_address }}</span>
                                    </li>
                                @endif

                                @if ($event->seats_left !== null)
                                    <li>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="9" cy="8" r="3.4"/><path d="M3 20c0-3.3 2.7-5.4 6-5.4s6 2.1 6 5.4"/><path d="M16 4.6a3.4 3.4 0 0 1 0 6.8M21 20c0-2.6-1.5-4.3-4-5"/>
                                        </svg>
                                        <span>
                                            {{ $event->total_seats ?: $event->seats_left }} Seats
                                            @if ($event->seats_left !== null)
                                                <b class="hm-ed-card__left">Only {{ $event->seats_left }} Left</b>
                                            @endif
                                        </span>
                                    </li>
                                @endif
                            </ul>

                            @include('frontend.events.partials.register-button', [
                                'class' => 'hm-ed-btn hm-ed-btn--primary hm-ed-btn--block',
                            ])
                        </div>

                        @if ($upcoming->isNotEmpty())
                            <div class="hm-ed-up hm-ed-anim">
                                <h2 class="hm-ed-up__title">Upcoming Events</h2>

                                @foreach ($upcoming as $other)
                                    <article class="hm-ed-up__item hm-ed-up__item--{{ $other->tone }}">
                                        <div class="hm-ed-up__body">
                                            <h3 class="hm-ed-up__name">{{ $other->title }}</h3>
                                            <a class="hm-ed-btn hm-ed-btn--small"
                                               href="{{ route('frontend.event-details', $other->slug) }}">
                                                Event Details
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M7 17 17 7M8.5 7H17v8.5"/>
                                                </svg>
                                            </a>
                                        </div>
                                        <img class="hm-ed-up__thumb" src="{{ $other->thumbnail_url }}"
                                             alt="{{ $other->title }}" loading="lazy" decoding="async">
                                    </article>
                                @endforeach

                                <a class="hm-ed-btn hm-ed-btn--primary hm-ed-btn--block"
                                   href="{{ route('frontend.events') }}">See all</a>
                            </div>
                        @endif
                    </aside>
                </div>
            </div>
        </div>
    </section>

    {{-- ================================ FAQ ================================
         Outside .hm-ed on purpose: the shared accordion is a full-bleed band
         with its own background, so nesting it in the left column boxed it in
         against the sidebar. Sitting here it is the last section before the
         footer, exactly as on the course details page.

         Passing $faqs makes the partial's view composer stand down, so these
         are THIS event's questions rather than the site-wide set. --}}
    @if ($event->faqs->isNotEmpty())
        @include('frontend.partials.faq', ['faqs' => $event->faqs])
    @endif

    {{-- Registration modal — only when the Register buttons open it. An event
         with its own registration link sends people offsite instead. --}}
    @unless ($registerUrl)
        @include('frontend.events.partials.register-modal')
    @endunless
@endsection

@push('scripts')
    {{-- Bootstrap's bundle is NOT in common-js — each page that needs it pushes
         it, as the home, about and course-details pages do. This page needs it
         twice over: the FAQ accordion is Bootstrap collapse, and the Register
         buttons are Bootstrap modal triggers. Without it both are inert. --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>

    {{-- Fade-up on arrival. Mirrors the reveal used elsewhere on the site: the
         observer only adds a class, the transition itself is CSS. --}}
    <script>
        (function () {
            'use strict';

            var items = document.querySelectorAll('.hm-ed-anim');
            if (!items.length) return;

            if (!('IntersectionObserver' in window) ||
                window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                items.forEach(function (el) { el.classList.add('is-in'); });
                return;
            }

            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

            items.forEach(function (el) { io.observe(el); });
        })();
    </script>

    {{-- Registration modal — inline validation, no reload, no alert().
         Same shape as the course-details enquiry modal so the two behave alike. --}}
    <script>
        (function () {
            'use strict';

            var form = document.getElementById('hmRegisterForm');
            if (!form) return;   // event has its own link, so no modal was rendered

            var modalEl = document.getElementById('hmRegisterModal');
            var note    = document.getElementById('hmRegisterNote');

            // Field -> its own rule. Anything not listed is optional and always
            // passes, so adding a field to the markup cannot silently block submit.
            var rules = {
                regName:  function (el) { return el.value.trim().length > 0; },
                regEmail: function (el) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim()); },
                regPhone: function (el) { return /^[0-9]{10}$/.test(el.value.trim()); },
                regEvent: function (el) { return el.value !== ''; },
                regTerms: function (el) { return el.checked; }
            };

            function fieldOf(el) { return el.closest('[data-hm-field]'); }

            function validate(el) {
                var rule = rules[el.id];
                if (!rule) return true;

                var ok = rule(el);
                var field = fieldOf(el);
                if (field) field.classList.toggle('is-invalid', !ok);
                return ok;
            }

            // Re-check as the user fixes a field, but only once it has been marked —
            // validating on the first keystroke would flag an empty field instantly.
            Object.keys(rules).forEach(function (id) {
                var el = document.getElementById(id);
                if (!el) return;

                el.addEventListener('blur', function () { validate(el); });
                el.addEventListener('change', function () { validate(el); });
                el.addEventListener('input', function () {
                    var field = fieldOf(el);
                    if (field && field.classList.contains('is-invalid')) validate(el);
                });
            });

            var submitBtn = form.querySelector('.hm-reg__submit');

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
                if (btnText) btnText.textContent = 'Registering…';

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
                    if (note) {
                        note.textContent = 'Sorry, something went wrong. Please try again.';
                        note.hidden = false;
                    }
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
