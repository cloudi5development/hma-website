@extends('backend.template.layouts.template-base')

@php
    // Repeater rows: whatever the last failed submit posted, else what is stored.
    // old() keeps the admin's typing on a validation error; the DB copy is what
    // they see on a normal edit.
    $itemRows = old('items', $section->items->map(fn ($item) => [
        'title'          => $item->title,
        'text'           => $item->text,
        'zoom'           => $item->zoom,
        'eyes'           => $item->eyes,
        'existing_image' => $item->image,
        'is_active'      => $item->is_active,
    ])->all());

    // What one row is called on this section, and the example text under it.
    $rowLabel   = $section->itemLabel();
    $rowsLabel  = $section->itemLabel(true);
    $rowExample = [
        'story'    => 'e.g. It All Started with a Simple Mission',
        'purpose'  => 'e.g. Our Vision !',
        'features' => 'e.g. Hands-On Learning',
        'approach' => 'e.g. Career Guidance',
    ][$section->key] ?? '';
@endphp

@section('title', 'Edit ' . $section->name)
@section('page_title', 'Edit ' . $section->name)
@section('page_sub', 'About Us')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.about-sections.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to About Us
        </a>
        {{-- Each block's <section> carries id="our-<key>", so the key is the anchor. --}}
        <a href="{{ route('frontend.about-us') }}#our-{{ $section->key }}"
           class="btn-ghost" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8.5 7H17v8.5"/></svg>
            View on site
        </a>
    </div>

    <form method="POST" action="{{ route('backend.about-sections.update', $section->key) }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        {{-- One container for the whole form --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- ============================= HEADING ============================= --}}
                <div class="form-section">
                    <h2 class="form-section__title">Heading</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="label">Label</label>
                            <input type="text" id="label" name="label"
                                   class="form-control-hm @error('label') is-invalid @enderror"
                                   value="{{ old('label', $section->label) }}" placeholder="e.g. {{ $section->name }}">
                            @error('label') <p class="form-error">{{ $message }}</p> @enderror
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

                @if ($section->usesHeading())
                    <div class="form-row mt-4">
                        <label class="form-label" for="title">Heading</label>
                        <input type="text" id="title" name="title"
                               class="form-control-hm @error('title') is-invalid @enderror"
                               value="{{ old('title', $section->title) }}" placeholder="The big line under the label">
                        @error('title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-row">
                        <label class="form-label" for="lead">Intro Text <span class="form-hint" style="display:inline">(optional)</span></label>
                        <textarea id="lead" name="lead" rows="3"
                                  class="form-control-hm @error('lead') is-invalid @enderror"
                                  placeholder="The paragraph under the heading.">{{ old('lead', $section->lead) }}</textarea>
                        @error('lead') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                @else
                    {{-- Our Story is told with the label alone; the page shows no
                         heading or intro for it, so neither is offered here. --}}
                    <p class="form-hint mt-3">This section shows the label only — each chapter below carries its own heading.</p>
                @endif

                @if ($section->usesImage())
                    <div class="form-section">
                        <h2 class="form-section__title">Image</h2>
                    </div>

                    <div class="form-row">
                        <label class="form-label" for="image">
                            {{ $section->key === 'approach' ? 'Centre Photo' : 'Section Photo' }}
                        </label>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <span class="tbl-logo {{ $section->key === 'approach' ? 'tbl-logo--round' : '' }}" style="width:96px;height:96px">
                                <img id="imagePreview" src="{{ $section->image_url ?? '' }}" alt=""
                                     style="{{ $section->image ? '' : 'display:none' }}">
                            </span>
                            <div>
                                <input type="file" id="image" name="image" accept="image/*"
                                       class="form-control-hm @error('image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                                {{-- Both slots are square in about.css: the Approach
                                     photo fills a circle, the Purpose one a 1:1 figure
                                     beside the two cards. --}}
                                <p class="form-hint">
                                    <strong>1000 × 1000 px</strong> (square) · WebP / PNG / JPG · max 3 MB ·
                                    {{ $section->key === 'approach'
                                        ? 'the circle crops anything taller.'
                                        : 'it sits in a square frame beside the two cards.' }}
                                </p>
                                @if ($section->image)
                                    <label class="d-inline-flex align-items-center gap-2 form-hint" style="cursor:pointer">
                                        <input type="checkbox" name="remove_image" value="1"> Remove the current image
                                    </label>
                                @endif
                            </div>
                        </div>
                        @error('image') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- ============================== ROWS ============================== --}}
                <div class="form-section">
                    <h2 class="form-section__title">{{ $rowsLabel }}</h2>
                    <button type="button" class="btn-soft" data-repeater-add="items">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add {{ $rowLabel }}
                    </button>
                </div>

                {{-- Colours and placement are handled for you, so the note explains
                     what the ▲ / ▼ buttons actually change beyond the reading order. --}}
                @if ($section->tones())
                    <p class="form-hint">
                        Colours are applied automatically in order, so you only write the words —
                        use the ▲ / ▼ buttons to change which {{ Str::lower($rowLabel) }} goes where.
                    </p>
                @endif

                @if ($section->usesPositions())
                    <p class="form-hint">
                        The circle has six places around it, filled top to bottom down the left
                        side and then the right. A seventh {{ Str::lower($rowLabel) }} starts again at the top left,
                        so it would sit on top of the first one.
                    </p>
                @endif

                @error('items') <p class="form-error">{{ $message }}</p> @enderror

                <div data-repeater="items">
                    @foreach ($itemRows as $r => $row)
                        @include('backend.about-sections.partials.item-row', [
                            'r' => $r, 'row' => $row, 'section' => $section, 'placeholder' => $rowExample,
                        ])
                    @endforeach
                </div>
                <p class="form-hint" data-repeater-empty="items" @if (count($itemRows)) style="display:none" @endif>
                    Nothing here yet — the block stays hidden until you add a row.
                </p>

                @if ($section->key === 'story')
                    <p class="form-hint">
                        Five chapters is what the scroll animation was built around. Two or more keeps it
                        running; a single chapter simply sits still.
                    </p>
                @endif

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Save Changes
            </button>
            <a href="{{ route('backend.about-sections.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

    {{-- Row template cloned by the repeater script. __I__ becomes the row key. --}}
    <template id="itemsTemplate">
        @include('backend.about-sections.partials.item-row', [
            'r' => '__I__', 'row' => [], 'section' => $section, 'placeholder' => $rowExample,
        ])
    </template>

@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';

            // ---- Live preview for the section photo ----
            var input = document.getElementById('image'), img = document.getElementById('imagePreview');
            if (input && img) input.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    img.src = URL.createObjectURL(this.files[0]);
                    img.style.display = 'block';
                }
            });

            /* ---- Repeater ------------------------------------------------------
               A row's position in the DOM is its display order — the form posts
               fields in document order, so the ▲ / ▼ buttons are all the
               reordering that is needed and no order field is stored. */
            var box = document.querySelector('[data-repeater="items"]');
            if (!box) return;

            var tpl     = document.getElementById('itemsTemplate'),
                addBtn  = document.querySelector('[data-repeater-add="items"]'),
                empty   = document.querySelector('[data-repeater-empty="items"]'),
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

            if (addBtn) addBtn.addEventListener('click', function () {
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

            // Photo previews inside rows, bound once by delegation so cloned rows
            // work without re-binding.
            box.addEventListener('change', function (e) {
                var field = e.target;
                if (field.type !== 'file') return;
                var preview = field.closest('[data-row]').querySelector('[data-row-preview]');
                if (preview && field.files && field.files[0]) {
                    preview.src = URL.createObjectURL(field.files[0]);
                    preview.style.display = 'block';
                }
            });

            refresh();
        })();
    </script>
@endpush
