@extends('frontend.layouts.template-base')

{{-- Block sections rather than @section('title', $value): the inline form
     double-escapes an "&" and leaks an output buffer on a null. Same treatment
     as course-details and content-page. --}}
@section('title'){!! $form->title . ' — Hire Minds Academy' !!}@endsection

@if (filled($form->description))
    @section('meta_description'){!! \Illuminate\Support\Str::limit(strip_tags($form->description), 160) !!}@endsection
@endif

{{-- A form is not a page anyone should find in search results ahead of the page
     that links to it, and an unpublished one must not be indexed at all. --}}
@section('meta_robots'){!! $form->isPublished() ? 'index, follow' : 'noindex, nofollow' !!}@endsection

@push('styles')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/dynamic-form.css') }}?v={{ filemtime(public_path('assets/css/frontend/dynamic-form.css')) }}">
@endpush

@section('content')

    {{-- --embed strips the page furniture down to the form itself: inside an
         iframe the site's own banner and breadcrumb are somebody else's chrome
         wrapped around ours. The site header/footer are the layout's, so the
         class only tightens what this page adds. --}}
    <section class="hmf @if ($embed) hmf--embed @endif">
        <div class="container">

            @unless ($embed)
                <nav aria-label="Breadcrumb">
                    <ol class="hmf__crumbs">
                        <li><a href="{{ route('frontend.index') }}">Home</a></li>
                        <li class="hmf__crumb-sep" aria-hidden="true">&rsaquo;</li>
                        <li aria-current="page">{{ $form->title }}</li>
                    </ol>
                </nav>
            @endunless

            <div class="hmf__paper">

                @include('frontend.partials.form-header', ['form' => $form])

                {{-- Everything below the banner sits on the white sheet. The
                     padding lives here rather than on the card, so the banner
                     can run edge to edge. --}}
                <div class="hmf__body">

                {{-- The thank-you, after a submission that did not redirect.
                     It REPLACES everything else rather than sitting above it:
                     a form with "allow multiple" off is closed to this browser
                     the moment it is submitted, so the old layout congratulated
                     the visitor and then showed them a padlock saying they had
                     already submitted. One outcome, one screen. --}}
                @if (session('form_success'))
                    <div class="hmf__done" role="status">
                        {{-- Decorative: the message below carries the meaning,
                             so this is hidden from screen readers rather than
                             described. --}}
                        {{-- The width/height attributes match the CSS so the
                             page does not jump as the GIF loads. --}}
                        <img class="hmf__done-gif" src="{{ asset('assets/images/forms/Success.gif') }}"
                             alt="" aria-hidden="true" width="120" height="120">

                        {{-- Stand-in for anyone who has asked their system not
                             to play animation — a GIF cannot be paused by CSS,
                             so it is swapped out entirely. --}}
                        <span class="hmf__done-static" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>
                            </svg>
                        </span>

                        {{-- The admin's own success message, as the headline.
                             Nothing is invented around it. --}}
                        <h2 class="hmf__done-title">{{ session('form_success') }}</h2>

                        @unless ($embed)
                            <a class="hmf__done-btn" href="{{ route('frontend.index') }}">
                                Back to Home
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        @endunless
                    </div>
                @else

                {{-- Refused after the fact: the form closed, or filled up, while
                     this page was open. --}}
                @if (session('form_error'))
                    <div class="hmf__note hmf__note--warn" role="alert">
                        <p>{{ session('form_error') }}</p>
                    </div>
                @endif

                @if (! $accepting)
                    {{-- Draft, disabled, full, or already submitted by this
                         browser. The wording is the admin's own closed message
                         wherever they set one. --}}
                    <div class="hmf__closed" role="status">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                        <p>{{ $closedReason }}</p>
                    </div>
                @elseif ($form->fields->isEmpty())
                    {{-- Published with nothing to fill in. The panel refuses to
                         publish an empty form, so this is only reachable if the
                         last field was removed afterwards. --}}
                    <div class="hmf__closed" role="status">
                        <p>This form has no questions yet.</p>
                    </div>
                @else
                    @if ($errors->any())
                        <div class="hmf__note hmf__note--warn" role="alert">
                            <p>Please check the {{ $errors->count() === 1 ? 'highlighted field' : 'highlighted fields' }} below and try again.</p>
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ route('frontend.form.submit', ['slug' => $form->slug] + ($embed ? ['embed' => 1] : [])) }}"
                          enctype="multipart/form-data"
                          class="hmf__form">
                        @csrf

                        {{-- Honeypot. Off-screen rather than display:none, which
                             some bots check for, and never announced or focusable.
                             A person cannot fill it; anything that does is
                             answered with the success page and stored nowhere. --}}
                        <div class="hmf__hp" aria-hidden="true">
                            <label for="{{ \App\Services\FormSubmissionService::HONEYPOT }}">Leave this field empty</label>
                            <input type="text" id="{{ \App\Services\FormSubmissionService::HONEYPOT }}"
                                   name="{{ \App\Services\FormSubmissionService::HONEYPOT }}"
                                   tabindex="-1" autocomplete="off">
                        </div>

                        @include('frontend.partials.form-fields', ['fields' => $form->fields])

                        <div class="hmf__actions">
                            <button type="submit" class="hmf__submit">
                                <span>{{ $form->submit_label }}</span>
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </form>
                @endif

                @endif{{-- /form_success --}}

                </div>{{-- /.hmf__body --}}
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    <script>
        /* Conditional visibility.
           A field carrying data-cond-* is shown only while the field it names
           holds the value it names. Every such field starts `hidden` in the
           markup, so the rule holds even before this runs and a field is never
           flashed on screen before being taken away.

           This is presentation only: FormField::isVisibleFor() applies the same
           rule on the server, so a hidden field is not held to its "required"
           rule and a forged value for one is not stored. */
        (function () {
            'use strict';

            var form = document.querySelector('.hmf__form');
            if (!form) return;

            var conditional = Array.prototype.slice.call(form.querySelectorAll('[data-cond-field]'));
            if (!conditional.length) return;

            function currentValue(key) {
                var inputs = form.querySelectorAll('[name="' + key + '"], [name="' + key + '[]"]');
                var values = [];

                Array.prototype.forEach.call(inputs, function (input) {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        if (input.checked) values.push(input.value);
                    } else if (input.value !== '') {
                        values.push(input.value);
                    }
                });

                return values;
            }

            function refresh() {
                conditional.forEach(function (field) {
                    var values  = currentValue(field.getAttribute('data-cond-field')),
                        wanted  = field.getAttribute('data-cond-value'),
                        matches = values.indexOf(wanted) !== -1,
                        show    = field.getAttribute('data-cond-op') === 'not_equals' ? !matches : matches;

                    field.hidden = !show;

                    // A hidden control must not block submission on its own
                    // `required`, and must not post a value the server would
                    // then have to ignore.
                    Array.prototype.forEach.call(field.querySelectorAll('input, select, textarea'), function (input) {
                        input.disabled = !show;
                    });
                });
            }

            form.addEventListener('change', refresh);
            form.addEventListener('input', refresh);
            refresh();
        })();
    </script>
@endpush
