@extends('backend.template.layouts.template-base')

@php
    $editing = $course->exists;
    $canAddPopular = $course->is_popular || $popularOther < \App\Models\Course::MAX_POPULAR;

    // Existing FAQ rows (or old input on validation error), blank rows dropped.
    $faqRows = old('faqs', $course->faqs->map(fn ($f) => ['question' => $f->question, 'answer' => $f->answer])->all());
    $faqRows = array_values(array_filter($faqRows, fn ($r) => trim($r['question'] ?? '') !== '' || trim($r['answer'] ?? '') !== ''));

    // Existing batches (or old input on a validation error). Dates come back out
    // as Y-m-d because that is what <input type="date"> reads and posts.
    $scheduleRows = old('schedules', $course->schedules->map(fn ($s) => [
        'start_date' => $s->start_date?->format('Y-m-d'),
        'end_date'   => $s->end_date?->format('Y-m-d'),
        'duration'   => $s->duration,
        'fee'        => $s->fee === null ? null : rtrim(rtrim((string) $s->fee, '0'), '.'),
        'show_fee'   => $s->show_fee,
        'is_active'  => $s->is_active,
    ])->all());
    $scheduleRows = array_values(array_filter($scheduleRows, fn ($r) => filled($r['start_date'] ?? null)));

    // The block opens already expanded when the switch is on, or when a failed
    // submit is coming back with rows the admin would otherwise not see.
    $scheduleOn = (bool) old('schedule_enabled', $course->schedule_enabled) || count($scheduleRows);
@endphp

@section('title', $editing ? 'Edit Course' : 'Add Course')
@section('page_title', $editing ? 'Edit Course' : 'Add Course')
@section('page_sub', 'Courses')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.courses.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Courses
        </a>
    </div>

    @include('backend.partials.flash')

    <form method="POST"
          action="{{ $editing ? route('backend.courses.update', $course) : route('backend.courses.store') }}"
          enctype="multipart/form-data" novalidate
          data-popular-can="{{ $canAddPopular ? '1' : '0' }}"
          data-max-faqs="{{ \App\Models\Course::MAX_FAQS }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- One container for the whole form; the sections below are separated by
             a rule + heading instead of by separate cards. --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- ========================= Course Details ========================= --}}
                <div class="form-section">
                    <h2 class="form-section__title">Course Details</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="category_id">
                                Category <span class="form-hint" style="display:inline">(department is set by the category)</span>
                            </label>
                            <select id="category_id" name="category_id"
                                    class="form-control-hm @error('category_id') is-invalid @enderror" required>
                                <option value="">— Select category —</option>
                                @foreach ($categories as $deptName => $group)
                                    <optgroup label="{{ $deptName }}">
                                        @foreach ($group as $cat)
                                            <option value="{{ $cat->id }}" {{ (int) old('category_id', $course->category_id) === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="name">Course Name</label>
                            <input type="text" id="name" name="name"
                                   class="form-control-hm @error('name') is-invalid @enderror"
                                   value="{{ old('name', $course->name) }}" placeholder="e.g. Python Programming" required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="slug">Slug <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="slug" name="slug"
                                   class="form-control-hm @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $course->slug) }}" placeholder="auto from name">
                            @error('slug') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-6 col-md-3">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="batch_start_date">Batch Start</label>
                            <input type="date" id="batch_start_date" name="batch_start_date"
                                   class="form-control-hm @error('batch_start_date') is-invalid @enderror"
                                   value="{{ old('batch_start_date', optional($course->batch_start_date)->toDateString()) }}" required>
                            @error('batch_start_date') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="duration">Duration</label>
                            <input type="text" id="duration" name="duration"
                                   class="form-control-hm @error('duration') is-invalid @enderror"
                                   value="{{ old('duration', $course->duration) }}" placeholder="3 Months" required>
                            @error('duration') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="training_mode">Mode</label>
                            <select id="training_mode" name="training_mode" class="form-control-hm @error('training_mode') is-invalid @enderror" required>
                                <option value="">—</option>
                                @foreach (\App\Models\Course::TRAINING_MODES as $mode)
                                    <option value="{{ $mode }}" {{ old('training_mode', $course->training_mode) === $mode ? 'selected' : '' }}>{{ $mode }}</option>
                                @endforeach
                            </select>
                            @error('training_mode') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="skill_level">Skill Level</label>
                            <select id="skill_level" name="skill_level" class="form-control-hm @error('skill_level') is-invalid @enderror" required>
                                <option value="">—</option>
                                @foreach (\App\Models\Course::SKILL_LEVELS as $level)
                                    <option value="{{ $level }}" {{ old('skill_level', $course->skill_level) === $level ? 'selected' : '' }}>{{ $level }}</option>
                                @endforeach
                            </select>
                            @error('skill_level') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- ============================ Content ============================ --}}
                <div class="form-section">
                    <h2 class="form-section__title">Content</h2>
                </div>

                <div class="form-row">
                    <label class="form-label" for="short_description">Short Description</label>
                    <textarea id="short_description" name="short_description" rows="2"
                              class="form-control-hm @error('short_description') is-invalid @enderror"
                              placeholder="One or two lines shown on the course card…">{{ old('short_description', $course->short_description) }}</textarea>
                    @error('short_description') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="full_description">Full Description</label>
                    <textarea id="full_description" name="full_description" rows="4"
                              class="form-control-hm @error('full_description') is-invalid @enderror">{{ old('full_description', $course->full_description) }}</textarea>
                    @error('full_description') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="overview">Course Overview</label>
                    <textarea id="overview" name="overview" rows="3"
                              class="form-control-hm @error('overview') is-invalid @enderror">{{ old('overview', $course->overview) }}</textarea>
                    @error('overview') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row">
                            <label class="form-label" for="learning_outcomes">Learning Outcomes <span class="form-hint" style="display:inline">(one per line)</span></label>
                            <textarea id="learning_outcomes" name="learning_outcomes" rows="4"
                                      class="form-control-hm @error('learning_outcomes') is-invalid @enderror">{{ old('learning_outcomes', $course->learning_outcomes) }}</textarea>
                            @error('learning_outcomes') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row">
                            <label class="form-label" for="prerequisites">Prerequisites</label>
                            <textarea id="prerequisites" name="prerequisites" rows="4"
                                      class="form-control-hm @error('prerequisites') is-invalid @enderror">{{ old('prerequisites', $course->prerequisites) }}</textarea>
                            @error('prerequisites') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label" for="certification">Certification Details</label>
                    <textarea id="certification" name="certification" rows="2"
                              class="form-control-hm @error('certification') is-invalid @enderror">{{ old('certification', $course->certification) }}</textarea>
                    @error('certification') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- ========================== Course Image ========================== --}}
                <div class="form-section">
                    <h2 class="form-section__title">Course Image</h2>
                </div>

                <div class="d-flex align-items-start gap-3 flex-wrap">
                    @if ($course->image)
                        <div class="hm-media" style="width:220px"><img id="coursePreview" src="{{ $course->image_url }}" alt="" style="width:100%;display:block"></div>
                    @else
                        <div class="hm-media hm-media--empty" id="coursePreviewWrap" style="width:220px;min-height:140px">
                            <img id="coursePreview" src="{{ asset('backend/template/images/actions/product-img.svg') }}" alt="">
                            No image uploaded
                        </div>
                    @endif
                    <div>
                        <input type="file" id="image" name="image" accept="image/*"
                               class="form-control-hm @error('image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                        <p class="form-hint">WebP / PNG / JPG · max 3 MB.</p>
                        @error('image') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- ========================= Course Brochure ========================= --}}
                <div class="form-section">
                    <h2 class="form-section__title">Course Brochure</h2>
                </div>

                <div class="form-row">
                    <label class="form-label" for="brochure">
                        Brochure PDF <span class="form-hint" style="display:inline">(optional)</span>
                    </label>

                    @if ($course->brochure)
                        {{-- What is on file now, with a link to check it. --}}
                        <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
                            <a href="{{ asset($course->brochure) }}" target="_blank" rel="noopener" class="btn-ghost">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/>
                                </svg>
                                View current brochure
                            </a>
                            <label class="check-chip">
                                <input type="hidden" name="remove_brochure" value="0">
                                <input type="checkbox" name="remove_brochure" value="1" {{ old('remove_brochure') ? 'checked' : '' }}>
                                <span class="check-chip__box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span class="check-chip__label">Remove brochure</span>
                            </label>
                        </div>
                    @endif

                    <input type="file" id="brochure" name="brochure" accept="application/pdf,.pdf"
                           class="form-control-hm @error('brochure') is-invalid @enderror" style="height:auto;padding:9px 12px">
                    <p class="form-hint">
                        PDF only · max 10 MB.
                        {{ $course->brochure ? 'Uploading a new file replaces the current one.' : '' }}
                        Visitors download it from the “Brochure” button on the course page — with no file uploaded, that button is hidden.
                    </p>
                    @error('brochure') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- ====================== Status, order, placement ====================== --}}
                <div class="form-section">
                    <h2 class="form-section__title">Status &amp; Placement</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $course->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="sort_order">Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="form-control-hm @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $course->sort_order ?? 0) }}">
                            @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="rating">Rating</label>
                            <input type="number" id="rating" name="rating" min="0" max="5" step="0.1"
                                   class="form-control-hm @error('rating') is-invalid @enderror"
                                   value="{{ old('rating', $course->rating ?? '4.5') }}">
                            @error('rating') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-row mt-3" style="margin-bottom:0">
                    <label class="form-label">Placement</label>
                    <div class="d-flex flex-wrap gap-2">
                        <label class="check-chip">
                            <input type="hidden" name="is_popular" value="0">
                            <input type="checkbox" id="isPopular" name="is_popular" value="1" {{ old('is_popular', $course->is_popular ?? false) ? 'checked' : '' }}>
                            <span class="check-chip__box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span class="check-chip__label">Show in Popular Courses <span class="form-hint" style="display:inline">(home, max {{ \App\Models\Course::MAX_POPULAR }})</span></span>
                        </label>
                        <label class="check-chip">
                            <input type="hidden" name="is_continue_learning" value="0">
                            <input type="checkbox" name="is_continue_learning" value="1" {{ old('is_continue_learning', $course->is_continue_learning ?? false) ? 'checked' : '' }}>
                            <span class="check-chip__box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span class="check-chip__label">Show in Continue Learning</span>
                        </label>
                        <label class="check-chip">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $course->is_featured ?? false) ? 'checked' : '' }}>
                            <span class="check-chip__box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span class="check-chip__label">Featured (Top Courses)</span>
                        </label>
                    </div>
                </div>

                {{-- ============================== FAQs ============================== --}}
                <div class="form-section">
                    <h2 class="form-section__title">FAQs <span class="form-hint" style="display:inline">(max {{ \App\Models\Course::MAX_FAQS }})</span></h2>
                    <button type="button" class="btn-soft" id="faqAdd">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add FAQ
                    </button>
                </div>

                <div id="faqRepeater">
                    @foreach ($faqRows as $r => $row)
                        <div class="faq-row" data-faq-row>
                            <div class="form-row">
                                <label class="form-label">Question</label>
                                <input type="text" name="faqs[{{ $r }}][question]" class="form-control-hm" value="{{ $row['question'] ?? '' }}" placeholder="Question">
                            </div>
                            <div class="form-row" style="margin-bottom:10px">
                                <label class="form-label">Answer</label>
                                <textarea name="faqs[{{ $r }}][answer]" rows="2" class="form-control-hm" placeholder="Answer">{{ $row['answer'] ?? '' }}</textarea>
                            </div>
                            <div class="text-end"><button type="button" class="btn-danger-soft" data-faq-remove>Remove</button></div>
                            <hr class="faq-sep">
                        </div>
                    @endforeach
                </div>
                <p class="form-hint" id="faqEmpty" @if(count($faqRows)) style="display:none" @endif>No FAQs yet — add up to {{ \App\Models\Course::MAX_FAQS }}.</p>

                {{-- ========================= COURSE SCHEDULE ========================= --}}
                <div class="form-section">
                    <h2 class="form-section__title">Course Schedule</h2>
                    <button type="button" class="btn-soft" id="scheduleAdd" @if (! $scheduleOn) style="display:none" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add Schedule
                    </button>
                </div>

                <div class="form-row">
                    <label class="switch">
                        <input type="hidden" name="schedule_enabled" value="0">
                        <input type="checkbox" id="scheduleEnabled" name="schedule_enabled" value="1" {{ $scheduleOn ? 'checked' : '' }}>
                        <span class="switch__track"></span>
                        <span class="switch__label">Enable Schedule</span>
                    </label>
                    <p class="form-hint">
                        Schedule this course for an upcoming batch. Switched off, the batches below are kept
                        but the course is left out of “Upcoming Course Schedules” on the home page.
                    </p>
                </div>

                {{-- Every batch is edited in place, so the list below is both the
                     table of existing schedules and the form that changes them. --}}
                <div id="scheduleFields" @if (! $scheduleOn) style="display:none" @endif>
                    <div id="scheduleRepeater">
                        @foreach ($scheduleRows as $r => $row)
                            @include('backend.courses.partials.schedule-row', ['r' => $r, 'row' => $row])
                        @endforeach
                    </div>
                    <p class="form-hint" id="scheduleEmpty" @if (count($scheduleRows)) style="display:none" @endif>
                        No batches yet — use “Add Schedule” to list one.
                    </p>
                </div>

                {{-- =============================== SEO =============================== --}}
                <div class="form-section">
                    <h2 class="form-section__title">SEO</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="meta_title">Meta Title</label>
                            <input type="text" id="meta_title" name="meta_title" class="form-control-hm @error('meta_title') is-invalid @enderror" value="{{ old('meta_title', $course->meta_title) }}">
                            @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="meta_keywords">Meta Keywords <span class="form-hint" style="display:inline">(comma separated)</span></label>
                            <input type="text" id="meta_keywords" name="meta_keywords" class="form-control-hm @error('meta_keywords') is-invalid @enderror" value="{{ old('meta_keywords', $course->meta_keywords) }}">
                            @error('meta_keywords') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-row mt-3" style="margin-bottom:0">
                    <label class="form-label" for="meta_description">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" rows="2" class="form-control-hm @error('meta_description') is-invalid @enderror">{{ old('meta_description', $course->meta_description) }}</textarea>
                    @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Course' }}
            </button>
            <a href="{{ route('backend.courses.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

    {{-- FAQ row template (cloned by JS) --}}
    <template id="faqTemplate">
        <div class="faq-row" data-faq-row>
            <div class="form-row">
                <label class="form-label">Question</label>
                <input type="text" name="faqs[__I__][question]" class="form-control-hm" placeholder="Question">
            </div>
            <div class="form-row" style="margin-bottom:10px">
                <label class="form-label">Answer</label>
                <textarea name="faqs[__I__][answer]" rows="2" class="form-control-hm" placeholder="Answer"></textarea>
            </div>
            <div class="text-end"><button type="button" class="btn-danger-soft" data-faq-remove>Remove</button></div>
            <hr class="faq-sep">
        </div>
    </template>

    {{-- Schedule row template (cloned by JS) --}}
    <template id="scheduleTemplate">
        @include('backend.courses.partials.schedule-row', ['r' => '__I__', 'row' => []])
    </template>

@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';
            var form = document.querySelector('form[data-max-faqs]');
            if (!form) return;

            // ---- Course image preview ----
            var fileInput = document.getElementById('image'), preview = document.getElementById('coursePreview');
            if (fileInput) fileInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    var wrap = document.getElementById('coursePreviewWrap');
                    if (wrap) wrap.classList.remove('hm-media--empty');
                    preview.src = URL.createObjectURL(this.files[0]);
                }
            });

            // ---- Popular cap ----
            var canPopular = form.getAttribute('data-popular-can') === '1';
            var popBox = document.getElementById('isPopular');
            if (popBox) popBox.addEventListener('change', function () {
                if (this.checked && !canPopular) {
                    window.alert('You can only select {{ \App\Models\Course::MAX_POPULAR }} Popular Courses.');
                    this.checked = false;
                }
            });

            // ---- FAQ repeater ----
            var MAX = parseInt(form.getAttribute('data-max-faqs'), 10) || 5;
            var repeater = document.getElementById('faqRepeater');
            var tpl = document.getElementById('faqTemplate');
            var addBtn = document.getElementById('faqAdd');
            var empty = document.getElementById('faqEmpty');
            var counter = repeater.querySelectorAll('[data-faq-row]').length;

            function rows() { return repeater.querySelectorAll('[data-faq-row]').length; }
            function refresh() { if (empty) empty.style.display = rows() ? 'none' : ''; }

            addBtn.addEventListener('click', function () {
                if (rows() >= MAX) { window.alert('Maximum ' + MAX + ' FAQs allowed.'); return; }
                var html = tpl.innerHTML.replace(/__I__/g, 'n' + (counter++));
                repeater.insertAdjacentHTML('beforeend', html);
                refresh();
            });

            repeater.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-faq-remove]');
                if (!btn) return;
                var row = btn.closest('[data-faq-row]');
                if (row) row.remove();
                refresh();
            });

            refresh();

            // ---- Course Schedule ----
            var schedToggle = document.getElementById('scheduleEnabled');
            var schedFields = document.getElementById('scheduleFields');
            var schedAdd    = document.getElementById('scheduleAdd');
            var schedBox    = document.getElementById('scheduleRepeater');
            var schedTpl    = document.getElementById('scheduleTemplate');
            var schedEmpty  = document.getElementById('scheduleEmpty');

            if (!schedToggle || !schedBox) return;

            var schedCounter = schedBox.querySelectorAll('[data-schedule-row]').length;

            function schedRows() { return schedBox.querySelectorAll('[data-schedule-row]').length; }

            function schedRefresh() {
                if (schedEmpty) schedEmpty.style.display = schedRows() ? 'none' : '';
            }

            function addSchedule() {
                schedBox.insertAdjacentHTML('beforeend',
                    schedTpl.innerHTML.replace(/__I__/g, 'n' + (schedCounter++)));
                schedRefresh();
            }

            // Off hides the whole block — including its Add button, so a batch is
            // never typed into a section that is switched off.
            schedToggle.addEventListener('change', function () {
                schedFields.style.display = this.checked ? '' : 'none';
                schedAdd.style.display    = this.checked ? '' : 'none';

                // Turning it on with nothing listed opens the first empty batch,
                // rather than showing an explanation and no fields.
                if (this.checked && !schedRows()) addSchedule();
            });

            schedAdd.addEventListener('click', addSchedule);

            schedBox.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-schedule-remove]');
                if (!btn) return;
                var row = btn.closest('[data-schedule-row]');
                if (row) row.remove();
                schedRefresh();
            });

            schedRefresh();
        })();
    </script>
@endpush
