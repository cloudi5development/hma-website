{{--
|--------------------------------------------------------------------------
| Google reCAPTCHA — shared by every form on the site
|--------------------------------------------------------------------------
|
| Drop it INSIDE the <form>, naming the action the server will check:
|
|   @include('frontend.partials.recaptcha', ['action' => 'contact_enquiry'])
|
| Params:
|   $action       required — v3 only, but always pass it: it costs nothing and
|                 the site keeps working if the version is switched.
|   $attribution  optional — false to leave the small print to the page (v3).
|   $noteClass    optional — a different class for that small print.
|
| It renders nothing at all unless both keys are configured, so an install
| without them keeps the forms exactly as they were.
|
| TWO SHAPES, one switch (services.recaptcha.version):
|
|   v2  the "I'm not a robot" checkbox. Google's widget draws itself into the
|       div below and puts its own <textarea name="g-recaptcha-response"> there,
|       so this file must NOT also render a field of that name.
|   v3  invisible: a hidden field this file renders, filled with a token minted
|       as the form is sent.
|
| Styling: .hm-recaptcha in assets/css/style.css (loaded site-wide).
--}}
@php
    $recaptcha        = app(\App\Services\RecaptchaVerifier::class);
    $recaptchaSiteKey = $recaptcha->enabled() ? $recaptcha->siteKey() : null;
    $isCheckbox       = $recaptcha->isCheckbox();
    $showAttribution  = ($attribution ?? true) && ! $isCheckbox;
    $recaptchaNote    = $noteClass ?? 'hm-recaptcha-note';
@endphp

@if ($recaptchaSiteKey)
    @if ($isCheckbox)
        {{-- Google draws the checkbox in here and adds the response field
             itself. data-recaptcha-box is ours, for the script below. --}}
        <div class="hm-recaptcha">
            <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}" data-recaptcha-box></div>
            <p class="hm-recaptcha__error" data-recaptcha-error hidden>
                Please tick the box to confirm you are not a robot.
            </p>
        </div>
    @else
        {{-- Filled at submit time and posted with the rest of the fields. --}}
        <input type="hidden" name="g-recaptcha-response" data-recaptcha-action="{{ $action }}">
    @endif

    @if ($showAttribution)
        {{-- Required wherever the invisible badge is hidden. The v2 widget
             carries Google's own branding, so it needs no line of its own. --}}
        <p class="{{ $recaptchaNote }}">
            This site is protected by reCAPTCHA and the Google
            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacy Policy</a> and
            <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Terms of Service</a> apply.
        </p>
    @endif

    @once
        @push('scripts')
            @if ($isCheckbox)
                {{-- No ?render= : that is the invisible build. Left to render
                     itself, so several forms on one page each get a widget. --}}
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <script>
                    (function () {
                        'use strict';

                        /**
                         * Stop a submission whose box has not been ticked, and say so.
                         *
                         * Listening on the document in the CAPTURE phase so this runs
                         * before each form's own submit handler — those handlers send
                         * the form straight away, and a listener on the form itself
                         * would not be guaranteed to go first (for an event fired AT
                         * an element, its own listeners run in registration order,
                         * capture flag or not).
                         *
                         * The server checks the answer for real; this is only so the
                         * visitor is told before a round trip.
                         */
                        document.addEventListener('submit', function (e) {
                            var form = e.target;

                            if (!form || typeof form.querySelector !== 'function') return;

                            var box = form.querySelector('[data-recaptcha-box]');
                            if (!box) return;

                            var error  = form.querySelector('[data-recaptcha-error]');
                            var answer = form.querySelector('[name="g-recaptcha-response"]');
                            var ticked = answer && answer.value;

                            if (error) error.hidden = !!ticked;

                            if (!ticked) {
                                e.preventDefault();
                                e.stopPropagation();
                                box.scrollIntoView({ block: 'center', behavior: 'smooth' });
                            }
                        }, true);

                        // A ticked box clears the message straight away.
                        document.addEventListener('click', function (e) {
                            var form = e.target && e.target.closest && e.target.closest('form');
                            if (!form) return;

                            var error = form.querySelector('[data-recaptcha-error]');
                            if (error && !error.hidden) error.hidden = true;
                        });
                    })();
                </script>
            @else
                <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}" async defer></script>
                <script>
                    (function () {
                        'use strict';

                        var SITE_KEY = @json($recaptchaSiteKey);
                        var FIELD    = 'input[name="g-recaptcha-response"][data-recaptcha-action]';
                        var WAIT_MS  = 4000;   // how long to wait for Google's script

                        /**
                         * One listener for every form on the page, in the CAPTURE phase
                         * on the document.
                         *
                         * It has to run before the form's own submit handler, because
                         * those handlers read the form's data straight away and send it.
                         * A capture listener on the form itself would not do: for an
                         * event fired AT an element, that element's listeners run in the
                         * order they were added, capture or not. On an ancestor, capture
                         * genuinely runs first — so this is the one place a token can be
                         * slipped in without every form's script knowing about it.
                         */
                        document.addEventListener('submit', function (e) {
                            var form = e.target;

                            if (!form || typeof form.querySelector !== 'function') return;

                            var field = form.querySelector(FIELD);

                            // Not a form we guard, or this is the re-submit below and
                            // the token is already in place.
                            if (!field || field.value) return;

                            e.preventDefault();
                            e.stopPropagation();

                            var submitter = e.submitter || null;

                            // Hand the form back to its own code, with or without a
                            // token. Sending without one is not silently allowed: the
                            // server refuses a blank token, and says so.
                            function resume(token) {
                                field.value = token || '';

                                if (form.requestSubmit) {
                                    form.requestSubmit(submitter);
                                } else {
                                    form.submit();   // pre-2020 browsers: no submit event
                                }

                                // A token is good for one send and about two minutes, so
                                // the next attempt must mint a fresh one. Cleared after
                                // the current task, by which point the form data has been
                                // read — clearing it synchronously could race a real
                                // (non-AJAX) submission's serialisation.
                                setTimeout(function () { field.value = ''; }, 0);
                            }

                            function execute() {
                                try {
                                    grecaptcha.ready(function () {
                                        grecaptcha.execute(SITE_KEY, { action: field.dataset.recaptchaAction })
                                            .then(resume)
                                            .catch(function () { resume(''); });
                                    });
                                } catch (err) {
                                    resume('');
                                }
                            }

                            // api.js is async, so a quick visitor can beat it here.
                            // Wait for it rather than sending a submission that would
                            // only be refused.
                            if (typeof grecaptcha !== 'undefined' && grecaptcha.execute) {
                                execute();
                                return;
                            }

                            var waited = 0;
                            var poll = setInterval(function () {
                                if (typeof grecaptcha !== 'undefined' && grecaptcha.execute) {
                                    clearInterval(poll);
                                    execute();
                                } else if ((waited += 100) >= WAIT_MS) {
                                    clearInterval(poll);
                                    resume('');   // blocked or offline: let the server decide
                                }
                            }, 100);
                        }, true);
                    })();
                </script>
            @endif
        @endpush
    @endonce
@endif
