{{--
|--------------------------------------------------------------------------
| Contact — office information + enquiry form (shared section)
|--------------------------------------------------------------------------
|
| Used by the home page and the contact page:
|
|   @include('frontend.partials.contact-form')
|
| and push its stylesheet from the page (the head is already rendered by the
| time an @include runs, so @push('styles') cannot work from in here):
|
|   <link rel="stylesheet" href="{{ asset('assets/css/frontend/contact-form.css') }}?v={{ filemtime(public_path('assets/css/frontend/contact-form.css')) }}">
|
| The validation JS is pushed by this partial itself (see the bottom), so the
| component carries its own behaviour wherever it is dropped in.
|
| Note: the .hm-anim / data-io reveal is a HOME-PAGE enhancement — those rules
| live in home.css alongside the observer that adds .is-in. On any page that
| does not load home.css the classes are inert, so the section simply renders
| visible rather than staying stuck at opacity 0.
--}}
    <section class="hm-contact" id="contact" data-io aria-labelledby="hmContactTitle">

        {{-- Same slow-rotating hero background --}}
        <div class="hm-contact__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
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
                           <span class="hm-cats__label-icon" aria-hidden="true"></span>
                            <span class="hm-contact__label-text">Let's Connect</span>
                        </span>
                        <h2 class="hm-contact__title" id="hmContactTitle">Let's Start Your Career Journey Together</h2>

                        <h3 class="hm-contact__subtitle">Office Information</h3>
                        <ul class="hm-contact__list">
                            {{-- One entry per branch, in the order they are listed in Settings. --}}
                            @foreach ($contact['branches'] as $branch)
                                <li class="hm-contact__item">
                                    <span class="hm-contact__icon" aria-hidden="true"><img class="hm-contact__icon-img" src="{{ asset('assets/images/contact-form/heroicons_map-pin.png') }}" alt="" loading="lazy" decoding="async"></span>
                                    <div class="hm-contact__item-body">
                                        <div class="hm-contact__item-title">{{ $branch['name'] }} Branch :</div>
                                        <p class="hm-contact__item-text">{{ $branch['address'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                            <li class="hm-contact__item">
                                <span class="hm-contact__icon" aria-hidden="true"><img class="hm-contact__icon-img" src="{{ asset('assets/images/contact-form/proicons_call.png') }}" alt="" loading="lazy" decoding="async"></span>
                                <div class="hm-contact__item-body">
                                    <div class="hm-contact__item-title">Phone Number :</div>
                                    <p class="hm-contact__item-text">
                                        <span class="hm-contact__text hm-contact__text--desktop">{{ $contact['phone'] }}</span>
                                        <a href="{{ $contact['phone_href'] }}" class="hm-contact__link hm-contact__link--mobile">{{ $contact['phone'] }}</a>
                                    </p>
                                </div>
                            </li>
                            <li class="hm-contact__item">
                                <span class="hm-contact__icon" aria-hidden="true"><img class="hm-contact__icon-img" src="{{ asset('assets/images/contact-form/heroicons-outline_mail.png') }}" alt="" loading="lazy" decoding="async"></span>
                                <div class="hm-contact__item-body">
                                    <div class="hm-contact__item-title">Email :</div>
                                    <p class="hm-contact__item-text">
                                        <span class="hm-contact__text hm-contact__text--desktop">{{ $contact['email'] }}</span>
                                        <a href="{{ $contact['email_href'] }}" class="hm-contact__link hm-contact__link--mobile">{{ $contact['email'] }}</a>
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    {{-- Right: enquiry form --}}
                    <div class="col-lg-7 hm-contact__formcol hm-anim hm-anim--right">
                        <form class="hm-contact__form needs-validation" id="hmContactForm" method="POST" action="{{ route('frontend.contact-enquiry.store') }}" novalidate>
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

                            @php
                                // Set only when the page passed one in — an event's
                                // "Register Now" links to /contact-us?event=<slug>.
                                // The home page never passes it, so this whole block
                                // is inert there and the form renders unchanged.
                                $enquiryEvent = $enquiryEvent ?? null;

                                $prefill = $enquiryEvent
                                    ? 'I would like to register for ' . $enquiryEvent->title
                                        . ($enquiryEvent->formatted_date ? ' on ' . $enquiryEvent->formatted_date : '') . '.'
                                    : '';
                            @endphp

                            @if ($enquiryEvent)
                                {{-- `interest` is already validated and stored by
                                     ContactEnquiryController and shown in the panel as
                                     "Area of interest", so the event needs no new field. --}}
                                <input type="hidden" name="interest" value="{{ $enquiryEvent->title }}">
                            @endif

                            <div class="hm-field">
                                <label class="hm-field__label" for="cfMessage">Message</label>
                                <textarea class="form-control hm-input hm-textarea" id="cfMessage" name="message" placeholder="Tell us how we can help you...">{{ $prefill }}</textarea>
                            </div>

                            @include('frontend.partials.recaptcha', ['action' => 'contact_enquiry'])

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

@push('scripts')
    {{-- Contact form: HTML5 validation + AJAX submit (no page reload). Stores the
         enquiry and emails the sender; see Frontend\ContactEnquiryController. --}}
    <script>
        (function () {
            'use strict';
            var form = document.getElementById('hmContactForm');
            if (!form) return;
            var note   = document.getElementById('hmContactNote');
            var submit = form.querySelector('.hm-contact__submit');
            var label  = submit ? submit.querySelector('.hm-btn__label') : null;
            var labelHtml = label ? label.innerHTML : '';

            function showNote(msg, ok) {
                if (!note) return;
                note.textContent = msg;
                note.style.color = ok ? '' : '#c0392b';
                note.hidden = false;
                clearTimeout(note._t);
                note._t = setTimeout(function () { note.hidden = true; }, 6000);
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    var firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) firstInvalid.focus();
                    return;
                }
                form.classList.remove('was-validated');

                if (submit) { submit.disabled = true; if (label) label.textContent = 'Sending…'; }

                send();
            });

            function send() {
                fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: new FormData(form)
                })
                .then(function (r) {
                    // 422 carries the reason - a failed reCAPTCHA check says so
                    // in words rather than as "something went wrong".
                    if (r.status === 422) {
                        return r.json().then(function (body) { return Promise.reject(firstError(body)); });
                    }
                    return r.ok ? r.json() : Promise.reject(null);
                })
                .then(function (data) {
                    if (data && data.success) {
                        form.reset();
                        showNote('Thanks! Your enquiry has been received — our team will get back to you within 24 hours.', true);
                    } else {
                        showNote('Something went wrong. Please try again.', false);
                    }
                })
                .catch(function (message) {
                    showNote(
                        typeof message === 'string' && message
                            ? message
                            : 'Sorry, we could not send your enquiry. Please try again or call us.',
                        false,
                    );
                })
                .finally(function () {
                    if (submit) { submit.disabled = false; if (label) label.innerHTML = labelHtml; }
                });
            }

            /** The first message out of a Laravel validation response. */
            function firstError(body) {
                var errors = body && body.errors;
                if (!errors) return body && body.message;
                for (var key in errors) {
                    if (errors[key] && errors[key].length) return errors[key][0];
                }
                return body.message;
            }
        })();
    </script>
@endpush
