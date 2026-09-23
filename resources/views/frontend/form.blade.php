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

{{-- The form stands on its own: no site header, footer or breadcrumb, and no
     "Back to Home" after submitting. Its link is sent straight to students,
     and nothing on the page should lead them off it into the rest of the
     website (user request, 2026-09-18). --}}
{{-- Inline with a literal — never "…)1@endsection": Blade does not see a
     directive glued to a preceding digit, and the section's buffer stays open. --}}
@section('standalone', 'yes')

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

            <div class="hmf__paper">

                @include('frontend.partials.form-header', ['form' => $form])

                {{-- Everything below the banner sits on the white sheet. The
                     padding lives here rather than on the card, so the banner
                     can run edge to edge. --}}
                {{-- Tinted only when the form has sections, so their cards
                     have something to sit on. A plain form keeps the plain
                     white sheet it has always had. --}}
                <div class="hmf__body @if ($form->hasSections()) hmf__body--grouped @endif">

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

                        {{-- Whatever shape the admin built: one list, groups, or
                             steps. One request carries the whole form in every
                             case, so nothing below this line knows or cares. --}}
                        @include('frontend.partials.form-structure', ['form' => $form])

                        {{-- On a stepped form the script reveals this on the
                             last step. It starts visible so that a form with no
                             JavaScript can still be submitted. --}}
                        <div class="hmf__actions" data-submit-row>
                            {{-- Inside the action row, above the button: on a
                                 stepped form the script reveals this row on the
                                 last step, and a tick box with no Submit beside
                                 it would be a puzzle on page one.

                                 Only on the public page — the admin's preview is
                                 a rehearsal by somebody already signed in, and
                                 it stores nothing. --}}
                            @include('frontend.partials.recaptcha', [
                                'action'    => 'dynamic_form',
                                'noteClass' => 'hm-recaptcha-note hmf__recaptcha',
                            ])

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

            @include('frontend.partials.form-footer')
        </div>
    </section>

@endsection

@push('scripts')
    @include('frontend.partials.form-scripts')
@endpush
