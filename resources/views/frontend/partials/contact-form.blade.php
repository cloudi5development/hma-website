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

                fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: new FormData(form)
                })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                .then(function (data) {
                    if (data && data.success) {
                        form.reset();
                        showNote('Thanks! Your enquiry has been received — our team will get back to you within 24 hours.', true);
                    } else {
                        showNote('Something went wrong. Please try again.', false);
                    }
                })
                .catch(function () {
                    showNote('Sorry, we could not send your enquiry. Please try again or call us.', false);
                })
                .finally(function () {
                    if (submit) { submit.disabled = false; if (label) label.innerHTML = labelHtml; }
                });
            });
        })();
    </script>
@endpush
