{{-- One FAQ row. See highlight-row for why $r must be treated as an opaque key. --}}
@php
    $active = array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true;
@endphp

<div class="rep-row" data-row>
    <div class="rep-row__head">
        <span class="rep-row__index" data-row-index>#{{ $i ?? 1 }}</span>

        <div class="rep-row__tools">
            <label class="switch switch--sm">
                <input type="hidden" name="event_faqs[{{ $r }}][is_active]" value="0">
                <input type="checkbox" name="event_faqs[{{ $r }}][is_active]" value="1" {{ $active ? 'checked' : '' }}>
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

    <div class="form-row">
        <label class="form-label">Question</label>
        <input type="text" name="event_faqs[{{ $r }}][question]"
               class="form-control-hm @error('event_faqs.' . $r . '.question') is-invalid @enderror"
               value="{{ $row['question'] ?? '' }}" placeholder="e.g. Do I need prior experience?">
        @error('event_faqs.' . $r . '.question') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-row" style="margin-bottom:0">
        <label class="form-label">Answer</label>
        <textarea name="event_faqs[{{ $r }}][answer]" rows="3" class="form-control-hm"
                  placeholder="The answer as it should read on the page.">{{ $row['answer'] ?? '' }}</textarea>
    </div>
</div>
