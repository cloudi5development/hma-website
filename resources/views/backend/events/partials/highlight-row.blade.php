{{--
    One "What You Will Learn" row.

    Rendered both inside the @foreach (with a real key) and inside the <template>
    the repeater script clones, where $r is the literal __I__ placeholder — so
    nothing here may depend on the key being a number.
--}}
@php
    // A brand-new row starts enabled; an existing one keeps whatever is stored.
    $active = array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true;
@endphp

<div class="rep-row" data-row>
    <div class="rep-row__head">
        <span class="rep-row__index" data-row-index>#{{ $i ?? 1 }}</span>

        <div class="rep-row__tools">
            <label class="switch switch--sm">
                <input type="hidden" name="highlights[{{ $r }}][is_active]" value="0">
                <input type="checkbox" name="highlights[{{ $r }}][is_active]" value="1" {{ $active ? 'checked' : '' }}>
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
        <div class="col-12 col-md-4">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Icon</label>
                <select name="highlights[{{ $r }}][icon]" class="form-control-hm">
                    @foreach (\App\Models\Event::HIGHLIGHT_ICONS as $key => $label)
                        <option value="{{ $key }}" {{ ($row['icon'] ?? array_key_first(\App\Models\Event::HIGHLIGHT_ICONS)) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12 col-md-8">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Title</label>
                <input type="text" name="highlights[{{ $r }}][title]"
                       class="form-control-hm @error('highlights.' . $r . '.title') is-invalid @enderror"
                       value="{{ $row['title'] ?? '' }}" placeholder="e.g. Hands-on with real tools">
                @error('highlights.' . $r . '.title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="col-12">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Description <span class="form-hint" style="display:inline">(optional)</span></label>
                <textarea name="highlights[{{ $r }}][description]" rows="2" class="form-control-hm"
                          placeholder="A line of detail under the title.">{{ $row['description'] ?? '' }}</textarea>
            </div>
        </div>
    </div>
</div>
