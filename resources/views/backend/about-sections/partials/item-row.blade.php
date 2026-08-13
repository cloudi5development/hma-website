{{--
    One repeater row for an About Us section.

    Rendered both inside the @foreach (with a real key) and inside the <template>
    the repeater script clones, where $r is the literal __I__ placeholder — so
    nothing here may depend on the key being a number.

    Which fields show depends on the section: only Our Story carries a photo per
    row. Colour and — on Our Approach — the place around the circle are NOT asked
    for: the section works them out from the row's position in the list, so the
    admin writes copy and the page keeps its design.

    existing_image carries the stored path across a save: the controller replaces
    the whole set of rows on every submit, so without it a row that is not
    re-uploaded would lose its photo. The controller only accepts a value this
    section already owns.
--}}
@php
    $active   = array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true;
    $image    = $row['existing_image'] ?? null;
    $withText = $section->key !== 'approach';
@endphp

<div class="rep-row" data-row>
    <div class="rep-row__head">
        <span class="rep-row__index" data-row-index>#{{ $i ?? 1 }}</span>

        <div class="rep-row__tools">
            <label class="switch switch--sm">
                <input type="hidden" name="items[{{ $r }}][is_active]" value="0">
                <input type="checkbox" name="items[{{ $r }}][is_active]" value="1" {{ $active ? 'checked' : '' }}>
                <span class="switch__track"></span>
                <span class="switch__label">Show</span>
            </label>
            <button type="button" class="btn-icon" data-row-up aria-label="Move up">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 15 6-6 6 6"/></svg>
            </button>
            <button type="button" class="btn-icon" data-row-down aria-label="Move down">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <button type="button" class="btn-danger-soft" data-row-remove>Remove</button>
        </div>
    </div>

    @if ($section->usesItemImages())
        <input type="hidden" name="items[{{ $r }}][existing_image]" value="{{ $image }}">
    @endif

    <div class="row g-3">
        <div class="col-12">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">{{ $section->key === 'approach' ? 'Label' : 'Title' }}</label>
                <input type="text" name="items[{{ $r }}][title]"
                       class="form-control-hm @error('items.' . $r . '.title') is-invalid @enderror"
                       value="{{ $row['title'] ?? '' }}" placeholder="{{ $placeholder ?? '' }}">
                @error('items.' . $r . '.title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($withText)
            <div class="col-12">
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">Description <span class="form-hint" style="display:inline">(optional)</span></label>
                    <textarea name="items[{{ $r }}][text]" rows="{{ $section->key === 'story' ? 4 : 3 }}"
                              class="form-control-hm @error('items.' . $r . '.text') is-invalid @enderror"
                              placeholder="The paragraph shown under the title.">{{ $row['text'] ?? '' }}</textarea>
                    @error('items.' . $r . '.text') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        @if ($section->usesItemImages())
            <div class="col-12">
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">Photo</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="tbl-logo" style="width:72px;height:72px">
                            <img data-row-preview src="{{ $image ? asset($image) : '' }}" alt=""
                                 style="{{ $image ? '' : 'display:none' }}">
                        </span>
                        <div style="flex:1;min-width:220px">
                            <input type="file" name="items[{{ $r }}][image]" accept="image/*"
                                   class="form-control-hm" style="height:auto;padding:9px 12px">
                            <p class="form-hint"><strong>800 × 800 px</strong> (square) · WebP / PNG / JPG · max 3 MB · the cards in the stack are square.</p>
                            <label class="d-inline-flex align-items-center gap-2 form-hint" style="cursor:pointer">
                                <input type="hidden" name="items[{{ $r }}][zoom]" value="0">
                                <input type="checkbox" name="items[{{ $r }}][zoom]" value="1" {{ !empty($row['zoom']) ? 'checked' : '' }}>
                                Crop in — use this when the photo has a border or shadow baked into it
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($section->key === 'features')
            <div class="col-12">
                <label class="d-inline-flex align-items-center gap-2 form-hint" style="cursor:pointer">
                    <input type="hidden" name="items[{{ $r }}][eyes]" value="0">
                    <input type="checkbox" name="items[{{ $r }}][eyes]" value="1" {{ !empty($row['eyes']) ? 'checked' : '' }}>
                    Show the animated eyes on this card (the "Who We Are" card carries them)
                </label>
            </div>
        @endif
    </div>
</div>
