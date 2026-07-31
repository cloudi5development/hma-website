{{--
    One "Meet Our Speakers" row. See highlight-row for why $r must be treated as
    an opaque key.

    existing_photo carries the stored path across a save: the controller replaces
    the whole set of rows on every submit, so without it a row that is not
    re-uploaded would lose its photo. The controller only accepts a value that
    this event already owns.
--}}
@php
    $active = array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true;
    $photo  = $row['existing_photo'] ?? null;
@endphp

<div class="rep-row" data-row>
    <div class="rep-row__head">
        <span class="rep-row__index" data-row-index>#{{ $i ?? 1 }}</span>

        <div class="rep-row__tools">
            <label class="switch switch--sm">
                <input type="hidden" name="speakers[{{ $r }}][is_active]" value="0">
                <input type="checkbox" name="speakers[{{ $r }}][is_active]" value="1" {{ $active ? 'checked' : '' }}>
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

    <input type="hidden" name="speakers[{{ $r }}][existing_photo]" value="{{ $photo }}">

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Name</label>
                <input type="text" name="speakers[{{ $r }}][name]"
                       class="form-control-hm @error('speakers.' . $r . '.name') is-invalid @enderror"
                       value="{{ $row['name'] ?? '' }}" placeholder="e.g. Rahul Sharma">
                @error('speakers.' . $r . '.name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Designation <span class="form-hint" style="display:inline">(optional)</span></label>
                <input type="text" name="speakers[{{ $r }}][designation]" class="form-control-hm"
                       value="{{ $row['designation'] ?? '' }}" placeholder="e.g. Senior AI Engineer">
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Company <span class="form-hint" style="display:inline">(optional)</span></label>
                <input type="text" name="speakers[{{ $r }}][company]" class="form-control-hm"
                       value="{{ $row['company'] ?? '' }}" placeholder="e.g. Google">
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">LinkedIn <span class="form-hint" style="display:inline">(optional)</span></label>
                <input type="text" name="speakers[{{ $r }}][linkedin]" class="form-control-hm"
                       value="{{ $row['linkedin'] ?? '' }}" placeholder="https://linkedin.com/in/…">
            </div>
        </div>
        <div class="col-12">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Photo <span class="form-hint" style="display:inline">(optional)</span></label>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="tbl-logo tbl-logo--round" style="width:56px;height:56px">
                        <img data-row-preview src="{{ $photo ? asset($photo) : '' }}" alt=""
                             style="{{ $photo ? '' : 'display:none' }}">
                    </span>
                    <div style="flex:1;min-width:220px">
                        <input type="file" name="speakers[{{ $r }}][photo]" accept="image/*"
                               class="form-control-hm" style="height:auto;padding:9px 12px">
                        <p class="form-hint">WebP / PNG / JPG · max 2 MB. Blank shows the speaker's initials instead.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
