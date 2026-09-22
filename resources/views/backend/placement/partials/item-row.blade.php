{{--
    One repeater row of one list inside a Placement Readiness block.

    Rendered both inside the @foreach (with a real key) and inside the <template>
    the repeater script clones, where $r is the literal __I__ placeholder — so
    nothing here may depend on the key being a number.

    Which boxes it shows is the list's own business: PlacementSection::GROUPS
    says whether this list uses a second line (a score range, a duration) and a
    paragraph, and what to call each of them on this list.
--}}
@php
    $active = array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true;
    $field  = 'items[' . $group . '][' . $r . ']';
    $errorKey = 'items.' . $group . '.' . $r;
@endphp

<div class="rep-row" data-row>
    <div class="rep-row__head">
        <span class="rep-row__index" data-row-index>#{{ $i ?? 1 }}</span>

        <div class="rep-row__tools">
            <label class="switch switch--sm">
                <input type="hidden" name="{{ $field }}[is_active]" value="0">
                <input type="checkbox" name="{{ $field }}[is_active]" value="1" {{ $active ? 'checked' : '' }}>
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

    <div class="row g-3">
        <div class="col-12 {{ $section->groupUses($group, 'subtitle') ? 'col-md-8' : '' }}">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">{{ $section->fieldLabel($group, 'title') }}</label>
                <input type="text" name="{{ $field }}[title]"
                       class="form-control-hm @error($errorKey . '.title') is-invalid @enderror"
                       value="{{ $row['title'] ?? '' }}" placeholder="{{ $placeholder ?? '' }}">
                @error($errorKey . '.title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($section->groupUses($group, 'subtitle'))
            <div class="col-12 col-md-4">
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">{{ $section->fieldLabel($group, 'subtitle') }}</label>
                    <input type="text" name="{{ $field }}[subtitle]"
                           class="form-control-hm @error($errorKey . '.subtitle') is-invalid @enderror"
                           value="{{ $row['subtitle'] ?? '' }}" placeholder="{{ $subtitlePlaceholder ?? '' }}">
                    @error($errorKey . '.subtitle') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        @if ($section->groupUses($group, 'text'))
            <div class="col-12">
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">
                        {{ $section->fieldLabel($group, 'text') }}
                        <span class="form-hint" style="display:inline">(optional)</span>
                    </label>
                    <textarea name="{{ $field }}[text]" rows="2"
                              class="form-control-hm @error($errorKey . '.text') is-invalid @enderror"
                              placeholder="The line shown under the heading.">{{ $row['text'] ?? '' }}</textarea>
                    @error($errorKey . '.text') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif
    </div>
</div>
