@extends('backend.template.layouts.template-base')

@php
    /* Repeater rows, per list: whatever the last failed submit posted, else what
       is stored. old() keeps the admin's typing on a validation error; the
       database copy is what they see on an ordinary edit. */
    $rowsFor = function (string $group) use ($section) {
        return old('items.' . $group, $section->itemsIn($group)->map(fn ($item) => [
            'title'     => $item->title,
            'subtitle'  => $item->subtitle,
            'text'      => $item->text,
            'is_active' => $item->is_active,
        ])->all());
    };

    // An example for the first box of each list, so an empty row is never a blank stare.
    $examples = [
        'point'    => ['title' => 'e.g. Individual readiness scorecards'],
        'card'     => ['title' => 'e.g. Different students, different gaps'],
        'stage'    => ['title' => 'e.g. Diagnose'],
        'band'     => ['title' => 'e.g. Placement Ready', 'subtitle' => 'e.g. 80-100'],
        'audience' => ['title' => 'e.g. For the student'],
        'module'   => ['title' => 'e.g. Interview Preparation'],
        'step'     => ['title' => 'e.g. Group discussion'],
        'outcome'  => ['title' => 'e.g. Student outcomes'],
        'format'   => ['title' => 'e.g. Placement Accelerator', 'subtitle' => 'e.g. 45-60 hours'],
        'feature'  => ['title' => 'e.g. Recruiter-led curriculum'],
    ];
@endphp

@section('title', 'Edit ' . $section->name)
@section('page_title', 'Edit ' . $section->name)
@section('page_sub', 'Placement Readiness')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.placement-readiness.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Placement Readiness
        </a>
        {{-- Every block's <section> carries id="placement-<key>", so the key is the anchor. --}}
        <a href="{{ route('frontend.placement-readiness') }}#placement-{{ $section->key }}"
           class="btn-ghost" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8.5 7H17v8.5"/></svg>
            View on site
        </a>
    </div>

    <form method="POST" action="{{ route('backend.placement-readiness.update', $section->key) }}" novalidate>
        @csrf
        @method('PUT')

        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- ============================= HEADING ============================= --}}
                <div class="form-section">
                    <h2 class="form-section__title">Heading</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="eyebrow">Label</label>
                            <input type="text" id="eyebrow" name="eyebrow"
                                   class="form-control-hm @error('eyebrow') is-invalid @enderror"
                                   value="{{ old('eyebrow', $section->eyebrow) }}" placeholder="The small line above the heading">
                            @error('eyebrow') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">The small line with the square beside it.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $section->is_active) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Show this section</span>
                                </label>
                            </div>
                            <p class="form-hint">Switched off, the whole block is left out of the page.</p>
                        </div>
                    </div>
                </div>

                <div class="form-row mt-4">
                    <label class="form-label" for="title">Heading</label>
                    <input type="text" id="title" name="title"
                           class="form-control-hm @error('title') is-invalid @enderror"
                           value="{{ old('title', $section->title) }}" placeholder="The big line under the label" required>
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="lead">Intro Text <span class="form-hint" style="display:inline">(optional)</span></label>
                    <textarea id="lead" name="lead" rows="3"
                              class="form-control-hm @error('lead') is-invalid @enderror"
                              placeholder="The paragraph under the heading.">{{ old('lead', $section->lead) }}</textarea>
                    @error('lead') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                @if ($section->uses('note'))
                    <div class="form-row">
                        <label class="form-label" for="note">
                            {{ $section->key === 'hero' ? 'Card Heading' : 'Closing Note' }}
                            <span class="form-hint" style="display:inline">(optional)</span>
                        </label>
                        <textarea id="note" name="note" rows="{{ $section->key === 'hero' ? 2 : 3 }}"
                                  class="form-control-hm @error('note') is-invalid @enderror"
                                  placeholder="{{ $section->key === 'hero'
                                      ? 'e.g. Built for pre-final and final-year students'
                                      : 'e.g. A responsible promise: HMA provides training, assessment and placement assistance…' }}">{{ old('note', $section->note) }}</textarea>
                        @error('note') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="form-hint">
                            {{ $section->key === 'hero'
                                ? 'The heading on the card beside the hero text.'
                                : 'Shown in a tinted panel under this section. Anything before the first colon is printed in bold.' }}
                        </p>
                    </div>
                @endif

                {{-- ============================== BUTTONS ============================= --}}
                @if ($section->uses('buttons') || $section->uses('primary'))
                    <div class="form-section">
                        <h2 class="form-section__title">Buttons</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="form-row" style="margin-bottom:0">
                                <label class="form-label" for="primary_label">Button Text</label>
                                <input type="text" id="primary_label" name="primary_label"
                                       class="form-control-hm @error('primary_label') is-invalid @enderror"
                                       value="{{ old('primary_label', $section->primary_label) }}" placeholder="e.g. Explore the program">
                                @error('primary_label') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-row" style="margin-bottom:0">
                                <label class="form-label" for="primary_url">Button Link</label>
                                <input type="text" id="primary_url" name="primary_url"
                                       class="form-control-hm @error('primary_url') is-invalid @enderror"
                                       value="{{ old('primary_url', $section->primary_url) }}" placeholder="/contact-us">
                                @error('primary_url') <p class="form-error">{{ $message }}</p> @enderror
                                <p class="form-hint">A page on this site (/contact-us), a place on this page (#placement-cta) or a full link.</p>
                            </div>
                        </div>

                        @if ($section->uses('buttons'))
                            <div class="col-12 col-md-6">
                                <div class="form-row" style="margin-bottom:0">
                                    <label class="form-label" for="secondary_label">Second Button Text <span class="form-hint" style="display:inline">(optional)</span></label>
                                    <input type="text" id="secondary_label" name="secondary_label"
                                           class="form-control-hm @error('secondary_label') is-invalid @enderror"
                                           value="{{ old('secondary_label', $section->secondary_label) }}" placeholder="e.g. Plan a diagnostic">
                                    @error('secondary_label') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-row" style="margin-bottom:0">
                                    <label class="form-label" for="secondary_url">Second Button Link</label>
                                    <input type="text" id="secondary_url" name="secondary_url"
                                           class="form-control-hm @error('secondary_url') is-invalid @enderror"
                                           value="{{ old('secondary_url', $section->secondary_url) }}" placeholder="#placement-cta">
                                    @error('secondary_url') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ============================== CONTACT ============================= --}}
                @if ($section->uses('contact'))
                    <div class="form-section">
                        <h2 class="form-section__title">Contact</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="form-row" style="margin-bottom:0">
                                <label class="form-label" for="phone">Phone</label>
                                <input type="text" id="phone" name="phone"
                                       class="form-control-hm @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $section->phone) }}" placeholder="+91 78240 94044">
                                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                                <p class="form-hint">Shown as a button that dials.</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-row" style="margin-bottom:0">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email"
                                       class="form-control-hm @error('email') is-invalid @enderror"
                                       value="{{ old('email', $section->email) }}" placeholder="info@hiremindsacademy.com">
                                @error('email') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                {{-- =============================== ROWS ============================== --}}
                @foreach ($section->groups() as $group => $settings)
                    @php $rows = $rowsFor($group); @endphp

                    <div class="form-section">
                        <h2 class="form-section__title">{{ $settings['plural'] }}</h2>
                        <button type="button" class="btn-soft" data-repeater-add="{{ $group }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            Add {{ $settings['label'] }}
                        </button>
                    </div>

                    @if (! empty($settings['hint']))
                        <p class="form-hint">{{ $settings['hint'] }} Use the ▲ / ▼ buttons to change the order.</p>
                    @endif

                    @error('items.' . $group) <p class="form-error">{{ $message }}</p> @enderror

                    <div data-repeater="{{ $group }}">
                        @foreach ($rows as $r => $row)
                            @include('backend.placement.partials.item-row', [
                                'r' => $r, 'i' => $loop->iteration, 'row' => $row, 'group' => $group, 'section' => $section,
                                'placeholder' => $examples[$group]['title'] ?? '',
                                'subtitlePlaceholder' => $examples[$group]['subtitle'] ?? '',
                            ])
                        @endforeach
                    </div>
                    <p class="form-hint" data-repeater-empty="{{ $group }}" @if (count($rows)) style="display:none" @endif>
                        Nothing here yet — this part of the section stays empty until you add a row.
                    </p>
                @endforeach

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Save Changes
            </button>
            <a href="{{ route('backend.placement-readiness.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

    {{-- One row template per list, cloned by the repeater script. __I__ becomes the row key. --}}
    @foreach ($section->groups() as $group => $settings)
        <template id="{{ $group }}Template">
            @include('backend.placement.partials.item-row', [
                'r' => '__I__', 'row' => [], 'group' => $group, 'section' => $section,
                'placeholder' => $examples[$group]['title'] ?? '',
                'subtitlePlaceholder' => $examples[$group]['subtitle'] ?? '',
            ])
        </template>
    @endforeach

@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';

            /* Repeaters — one per list on this screen.
               A row's position in the DOM is its display order: the form posts
               its fields in document order, so the ▲ / ▼ buttons are all the
               reordering there is and no order field is stored. */
            document.querySelectorAll('[data-repeater]').forEach(function (box) {
                var group   = box.getAttribute('data-repeater'),
                    tpl     = document.getElementById(group + 'Template'),
                    addBtn  = document.querySelector('[data-repeater-add="' + group + '"]'),
                    empty   = document.querySelector('[data-repeater-empty="' + group + '"]'),
                    counter = box.querySelectorAll('[data-row]').length;

                function rows() { return Array.prototype.slice.call(box.querySelectorAll('[data-row]')); }

                function refresh() {
                    var list = rows();
                    if (empty) empty.style.display = list.length ? 'none' : '';
                    list.forEach(function (row, i) {
                        var label = row.querySelector('[data-row-index]');
                        if (label) label.textContent = '#' + (i + 1);
                        // Nothing to move past at the ends.
                        var up = row.querySelector('[data-row-up]'), down = row.querySelector('[data-row-down]');
                        if (up) up.disabled = i === 0;
                        if (down) down.disabled = i === list.length - 1;
                    });
                }

                if (addBtn && tpl) addBtn.addEventListener('click', function () {
                    box.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__I__/g, 'n' + (counter++)));
                    refresh();
                });

                box.addEventListener('click', function (e) {
                    var row, sibling;

                    if (e.target.closest('[data-row-remove]')) {
                        row = e.target.closest('[data-row]');
                        if (row) row.remove();
                        refresh();
                        return;
                    }

                    if (e.target.closest('[data-row-up]')) {
                        row = e.target.closest('[data-row]');
                        sibling = row && row.previousElementSibling;
                        if (sibling) sibling.before(row);
                        refresh();
                        return;
                    }

                    if (e.target.closest('[data-row-down]')) {
                        row = e.target.closest('[data-row]');
                        sibling = row && row.nextElementSibling;
                        if (sibling) sibling.after(row);
                        refresh();
                    }
                });

                refresh();
            });
        })();
    </script>
@endpush
