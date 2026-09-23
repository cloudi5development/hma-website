{{--
|--------------------------------------------------------------------------
| Course enquiry modal (shared component)
|--------------------------------------------------------------------------
|
| "Take the First Step Toward Your Dream Career" — the enquiry form opened by
| the course-details hero's "Enroll Now" button and by the Apply buttons in the
| Upcoming Course Schedules table (home page and /schedules).
|
| Include it once per page, inside @section('content'):
|
|     @include('frontend.partials.course-enquiry-modal', ['selectedCourseId' => $model->id])
|
| and push its stylesheet from the page (the head is already rendered by the
| time an @include runs, so @push('styles') cannot work from in here):
|
|     <link rel="stylesheet" href="{{ asset('assets/css/frontend/enquiry-modal.css') }}?v={{ filemtime(public_path('assets/css/frontend/enquiry-modal.css')) }}">
|
| Bootstrap 5's JS bundle supplies the show/hide, backdrop, focus trap, ESC and
| click-outside, so the page must load it too.
|
| $selectedCourseId is optional — pass it to preselect that course.
|
| Any control that opens the modal may name a course and a batch:
|
|     <button data-bs-toggle="modal" data-bs-target="#hmEnquireModal"
|             data-enq-course="12" data-enq-batch="20 Aug 2026 – 20 Nov 2026">
|
| The script below then selects that course and shows the batch line, so the
| visitor sees which schedule they are applying for and the enquiry records it.
--}}
@php
    // Every active course (id + name), so the "Course" select is complete and
    // submits a real course_id.
    $enquiryCourses = \App\Models\Course::active()->orderBy('name')->get(['id', 'name']);
    $selectedCourseId ??= null;

    $careerGoals = [
        'Career Switch',
        'Looking for Course Completion',
        'Looking for Job',
        'Looking for Career Upgrade',
    ];

    // Transparent cut-out of the support executive — sits on the cream panel.
    $enquiryFigure = 'assets/images/courses/enquiry-modal.webp';
@endphp

<div class="modal fade hm-enq" id="hmEnquireModal" tabindex="-1"
     aria-labelledby="hmEnquireTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <button class="hm-enq__close" type="button" data-bs-dismiss="modal" aria-label="Close">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>

            <div class="row hm-enq__row">

                {{-- ---------------------------- LEFT ---------------------------- --}}
                <div class="col-12 col-lg-5 hm-enq__aside">
                    <img class="hm-enq__logo" src="{{ \App\Models\Setting::image('site_logo', 'assets/images/branding/logo.png') }}"
                         alt="Hire Minds Academy">

                    <h2 class="hm-enq__title" id="hmEnquireTitle">Take the First Step Toward Your Dream Career</h2>

                    <p class="hm-enq__desc">
                        Share your details and our experts will help you choose the best
                        program based on your career goals and interests.
                    </p>

                    {{-- Decorations --}}
                    <img class="hm-enq__deco hm-enq__deco--dots" aria-hidden="true"
                         src="{{ asset('assets/images/courses/dots.png') }}" alt="" loading="lazy">
                    <img class="hm-enq__deco hm-enq__deco--lines" aria-hidden="true"
                         src="{{ asset('assets/images/courses/pattern.png') }}" alt="" loading="lazy">
                    <img class="hm-enq__deco hm-enq__deco--star-a" aria-hidden="true"
                         src="{{ asset('assets/images/faq/star.png') }}" alt="" loading="lazy">
                    <img class="hm-enq__deco hm-enq__deco--star-b" aria-hidden="true"
                         src="{{ asset('assets/images/faq/star.png') }}" alt="" loading="lazy">

                    <figure class="hm-enq__figure">
                        <img src="{{ asset($enquiryFigure) }}" alt="" role="presentation" loading="lazy">
                    </figure>
                </div>

                {{-- ---------------------------- RIGHT ---------------------------- --}}
                <div class="col-12 col-lg-7 hm-enq__main">
                    {{-- novalidate: the browser's own bubbles are replaced by the
                         inline messages below each field. --}}
                    <form class="hm-enq__form" id="hmEnquireForm" method="POST" action="{{ route('frontend.course-enquiry.store') }}" novalidate>
                        @csrf

                        {{-- Filled in when the visitor arrives from an Apply button
                             on a schedule row; hidden and empty otherwise, so the
                             course-details "Enroll Now" flow is unchanged. --}}
                        <p class="hm-enq__batch" id="hmEnquireBatch" hidden>
                            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                            <span>Applying for the <strong data-enq-batch-label></strong> batch</span>
                        </p>
                        <input type="hidden" name="batch" id="hmEnquireBatchInput" value="">

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="hm-enq__field" data-hm-field>
                                    <label class="hm-enq__label" for="enqName">Full Name</label>
                                    <input class="hm-enq__input" id="enqName" name="name" type="text"
                                           placeholder="Alex Johnson" required>
                                    <p class="hm-enq__error" data-hm-error>Please enter your full name.</p>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="hm-enq__field" data-hm-field>
                                    <label class="hm-enq__label" for="enqEmail">Email</label>
                                    <input class="hm-enq__input" id="enqEmail" name="email" type="email"
                                           placeholder="example@gmail.com" required>
                                    <p class="hm-enq__error" data-hm-error>Please enter a valid email address.</p>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="hm-enq__field" data-hm-field>
                                    <label class="hm-enq__label" for="enqPhone">Phone Number</label>
                                    <div class="hm-enq__phone">
                                        <span class="hm-enq__phone-code">+91</span>
                                        <input class="hm-enq__input" id="enqPhone" name="phone" type="tel"
                                               placeholder="Mobile Number" inputmode="numeric"
                                               pattern="[0-9]{10}" required>
                                    </div>
                                    <p class="hm-enq__error" data-hm-error>Enter a valid 10-digit mobile number.</p>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="hm-enq__field" data-hm-field>
                                    <label class="hm-enq__label" for="enqCity">City/Location</label>
                                    <input class="hm-enq__input" id="enqCity" name="city" type="text"
                                           placeholder="Enter Your Place">
                                    <p class="hm-enq__error" data-hm-error>Please enter your city.</p>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="hm-enq__field" data-hm-field>
                                    <label class="hm-enq__label" for="enqCourse">Course</label>
                                    <select class="hm-enq__input hm-enq__select" id="enqCourse" name="course_id" required>
                                        <option value="" disabled {{ $selectedCourseId ? '' : 'selected' }} hidden>Select a course</option>
                                        @foreach ($enquiryCourses as $option)
                                            <option value="{{ $option->id }}" {{ $option->id === $selectedCourseId ? 'selected' : '' }}>{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="hm-enq__error" data-hm-error>Please choose a course.</p>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="hm-enq__field" data-hm-field>
                                    <label class="hm-enq__label" for="enqCareerGoal">Current Career Goal</label>
                                    <select class="hm-enq__input hm-enq__select" id="enqCareerGoal" name="career_goal" required>
                                        <option value="" disabled selected hidden>Select your career goal</option>
                                        @foreach ($careerGoals as $option)
                                            <option>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <p class="hm-enq__error" data-hm-error>Please choose your career goal.</p>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="hm-enq__field" data-hm-field>
                                    <label class="hm-enq__label" for="enqMessage">Message</label>
                                    <textarea class="hm-enq__input hm-enq__textarea" id="enqMessage" name="message"
                                              placeholder="Tell us how we can help you..."></textarea>
                                </div>
                            </div>
                        </div>

                        @include('frontend.partials.recaptcha', ['action' => 'course_enquiry'])

                        <button class="hm-enq__submit" type="submit">
                            <span>Send Enquiry</span>
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                        </button>

                        <p class="hm-enq__note" id="hmEnquireNote" role="status" hidden>
                            Thanks! Your enquiry has been received — our team will get back to you within 24 hours.
                        </p>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
    {{-- Enquiry modal — inline validation, no reload, no alert(). --}}
    <script>
        (function () {
            'use strict';

            var form = document.getElementById('hmEnquireForm');
            if (!form) return;

            var modalEl = document.getElementById('hmEnquireModal');
            var note    = document.getElementById('hmEnquireNote');

            // Field -> its own rule. Anything not listed is optional and always
            // passes, so adding a field to the markup cannot silently block submit.
            var rules = {
                enqName:     function (v) { return v.trim().length > 0; },
                enqEmail:    function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); },
                enqPhone:    function (v) { return /^[0-9]{10}$/.test(v.trim()); },
                enqCourse:      function (v) { return v !== ''; },
                enqCareerGoal:  function (v) { return v !== ''; }
            };

            function fieldOf(el) { return el.closest('[data-hm-field]'); }

            function validate(el) {
                var rule = rules[el.id];
                if (!rule) return true;

                var ok    = rule(el.value);
                var field = fieldOf(el);
                if (field) field.classList.toggle('is-invalid', !ok);
                return ok;
            }

            // Re-check as the user fixes a field, but only once it has been marked
            // — validating on first keystroke would flag an empty field instantly.
            Object.keys(rules).forEach(function (id) {
                var el = document.getElementById(id);
                if (!el) return;

                el.addEventListener('blur', function () { validate(el); });
                el.addEventListener('input', function () {
                    var field = fieldOf(el);
                    if (field && field.classList.contains('is-invalid')) validate(el);
                });
                el.addEventListener('change', function () { validate(el); });
            });

            var submitBtn = form.querySelector('.hm-enq__submit');

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
                if (btnText) btnText.textContent = 'Sending…';

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
                    if (note) { note.textContent = 'Sorry, something went wrong. Please try again.'; note.hidden = false; }
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    if (btnText) btnText.textContent = original;
                });
            });

            /* ---- Opened from a schedule row ------------------------------------
               Bootstrap hands the triggering element over on show.bs.modal, so the
               course and the batch are read straight off the Apply button. A
               trigger that names neither (course-details' "Enroll Now") leaves the
               form exactly as it was rendered. */
            var batchLine  = document.getElementById('hmEnquireBatch');
            var batchInput = document.getElementById('hmEnquireBatchInput');
            var batchLabel = form.querySelector('[data-enq-batch-label]');
            var courseSel  = document.getElementById('enqCourse');

            // What the form was rendered with, so closing puts it back.
            var defaultCourse = courseSel ? courseSel.value : '';

            function applyBatch(batch) {
                if (!batchLine || !batchInput) return;

                batchInput.value = batch || '';
                if (batchLabel) batchLabel.textContent = batch || '';
                batchLine.hidden = !batch;
            }

            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', function (e) {
                    var trigger = e.relatedTarget;
                    if (!trigger) return;

                    var courseId = trigger.getAttribute('data-enq-course');
                    if (courseId && courseSel) {
                        courseSel.value = courseId;
                        var field = fieldOf(courseSel);
                        if (field) field.classList.remove('is-invalid');
                    }

                    applyBatch(trigger.getAttribute('data-enq-batch'));
                });

                // Leave the modal as it was found: clear the values, the messages
                // and the note, so reopening never shows the last visit's state.
                modalEl.addEventListener('hidden.bs.modal', function () {
                    form.reset();
                    form.querySelectorAll('[data-hm-field]').forEach(function (f) {
                        f.classList.remove('is-invalid');
                    });
                    if (courseSel) courseSel.value = defaultCourse;
                    applyBatch('');
                    if (note) { note.hidden = true; clearTimeout(note._t); }
                });
            }
        })();
    </script>
@endpush
