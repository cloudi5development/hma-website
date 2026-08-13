@extends('backend.template.layouts.template-base')

@php
    $editing = $schedule->exists;

    // The category select is a filter for the course list, not a stored field.
    // On a failed submit it comes back from old(); otherwise it is read off the
    // batch's own course, so editing opens on the right pair.
    $selectedCategory = (int) old('category_id', $categoryId ?? 0);
    $selectedCourse   = (int) old('course_id', $schedule->course_id ?? 0);

    // Grouped for the <optgroup>s, so a long category list stays readable.
    $categoryGroups = $categories->groupBy(fn ($c) => $c->department?->name ?? 'Other');
@endphp

@section('title', $editing ? 'Edit Schedule' : 'Add Schedule')
@section('page_title', $editing ? 'Edit Schedule' : 'Add Schedule')
@section('page_sub', 'Course Schedule')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.schedules.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Schedule
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.schedules.update', $schedule) : route('backend.schedules.store') }}"
          novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- Row 1 — category, then the courses inside it --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="category_id">Category</label>
                            <select id="category_id" name="category_id"
                                    class="form-control-hm @error('category_id') is-invalid @enderror">
                                <option value="">— All categories —</option>
                                @foreach ($categoryGroups as $deptName => $group)
                                    <optgroup label="{{ $deptName }}">
                                        @foreach ($group as $cat)
                                            <option value="{{ $cat->id }}" {{ $selectedCategory === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="form-hint">Narrows the course list below. Not stored on the batch.</p>
                            @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="course_id">Course</label>
                            {{-- data-category on each option is what the script filters
                                 on, so choosing a category needs no round trip. --}}
                            <select id="course_id" name="course_id"
                                    class="form-control-hm @error('course_id') is-invalid @enderror" required>
                                <option value="">— Select course —</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}"
                                            data-category="{{ $course->category_id }}"
                                            data-duration="{{ $course->duration }}"
                                            {{ $selectedCourse === $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}@unless ($course->is_active) (hidden)@endunless
                                    </option>
                                @endforeach
                            </select>
                            <p class="form-hint" id="courseHint">Pick a category first to shorten this list.</p>
                            @error('course_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Row 2 — the dates --}}
                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date"
                                   class="form-control-hm @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date', $schedule->start_date?->format('Y-m-d')) }}" required>
                            <p class="form-hint">The website only lists batches starting today or later.</p>
                            @error('start_date') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="end_date">End Date <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="date" id="end_date" name="end_date"
                                   class="form-control-hm @error('end_date') is-invalid @enderror"
                                   value="{{ old('end_date', $schedule->end_date?->format('Y-m-d')) }}">
                            @error('end_date') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Row 3 — duration + the daily timing --}}
                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="duration">Duration <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="duration" name="duration"
                                   class="form-control-hm @error('duration') is-invalid @enderror"
                                   value="{{ old('duration', $schedule->duration) }}" placeholder="e.g. 3 Months">
                            <p class="form-hint">Left blank, the course's own duration is shown.</p>
                            @error('duration') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="start_time">Start Time <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="time" id="start_time" name="start_time"
                                   class="form-control-hm @error('start_time') is-invalid @enderror"
                                   value="{{ old('start_time', $schedule->start_time_input) }}">
                            @error('start_time') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="end_time">End Time <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="time" id="end_time" name="end_time"
                                   class="form-control-hm @error('end_time') is-invalid @enderror"
                                   value="{{ old('end_time', $schedule->end_time_input) }}">
                            <p class="form-hint">Times show under the dates on the website.</p>
                            @error('end_time') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Row 4 — fee --}}
                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="fee">Fee <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="number" id="fee" name="fee" min="0" step="1"
                                   class="form-control-hm @error('fee') is-invalid @enderror"
                                   value="{{ old('fee', $schedule->fee === null ? '' : rtrim(rtrim((string) $schedule->fee, '0'), '.')) }}"
                                   placeholder="25000">
                            <p class="form-hint">Numbers only — the site adds the ₹ and the commas.</p>
                            @error('fee') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-8">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Visibility</label>
                            <div class="d-flex align-items-center flex-wrap gap-4" style="min-height:48px">
                                <label class="switch">
                                    <input type="hidden" name="show_fee" value="0">
                                    <input type="checkbox" name="show_fee" value="1" {{ old('show_fee', $schedule->show_fee ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Show Fee on website</span>
                                </label>
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $schedule->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                            <p class="form-hint">
                                Fee hidden, or none entered, shows as <strong>“Contact for Fee”</strong> — never ₹0.
                                Active off keeps the batch here but leaves it off the website.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Schedule' }}
            </button>
            <a href="{{ route('backend.schedules.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';

            var cat    = document.getElementById('category_id');
            var course = document.getElementById('course_id');
            var hint   = document.getElementById('courseHint');
            var dur    = document.getElementById('duration');
            if (!cat || !course) return;

            // Every course is in the markup already; choosing a category hides the
            // ones that do not belong to it. Options are detached rather than just
            // styled, so a hidden course cannot be picked with the keyboard.
            var all = Array.prototype.slice.call(course.options).slice(1).map(function (opt) {
                return { el: opt, category: opt.getAttribute('data-category') || '' };
            });

            function filter() {
                var wanted = cat.value;
                var shown  = 0;

                all.forEach(function (item) {
                    var match = !wanted || item.category === wanted;
                    item.el.hidden   = !match;
                    item.el.disabled = !match;
                    if (match) shown++;
                });

                // A course left selected from another category would still post,
                // so clear it rather than leaving a mismatch on screen.
                var current = course.selectedOptions[0];
                if (current && current.disabled) course.value = '';

                if (hint) {
                    hint.textContent = !wanted
                        ? 'Pick a category first to shorten this list.'
                        : (shown ? shown + ' course' + (shown === 1 ? '' : 's') + ' in this category.'
                                 : 'No courses in this category yet.');
                }
            }

            // Prefill the duration placeholder from the chosen course, so leaving
            // the field blank visibly means "use the course's own duration".
            function syncDurationHint() {
                if (!dur) return;
                var opt = course.selectedOptions[0];
                var d = opt && opt.getAttribute('data-duration');
                dur.placeholder = d ? d + ' (course default)' : 'e.g. 3 Months';
            }

            cat.addEventListener('change', function () { filter(); syncDurationHint(); });
            course.addEventListener('change', syncDurationHint);

            filter();
            syncDurationHint();
        })();
    </script>
@endpush
