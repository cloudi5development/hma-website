{{--
    One upcoming batch of this course.

    Rendered both inside the @foreach (with a real key) and inside the <template>
    the repeater script clones, where $r is the literal __I__ placeholder — so
    nothing here may depend on the key being a number.
--}}
@php
    // A brand-new batch starts active with its fee shown; an existing one keeps
    // whatever is stored.
    $active  = array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true;
    $showFee = array_key_exists('show_fee', $row) ? (bool) $row['show_fee'] : true;
@endphp

<div class="faq-row" data-schedule-row>
    <div class="row g-3">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Start Date</label>
                <input type="date" name="schedules[{{ $r }}][start_date]"
                       class="form-control-hm @error('schedules.' . $r . '.start_date') is-invalid @enderror"
                       value="{{ $row['start_date'] ?? '' }}">
                @error('schedules.' . $r . '.start_date') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">End Date <span class="form-hint" style="display:inline">(optional)</span></label>
                <input type="date" name="schedules[{{ $r }}][end_date]"
                       class="form-control-hm @error('schedules.' . $r . '.end_date') is-invalid @enderror"
                       value="{{ $row['end_date'] ?? '' }}">
                @error('schedules.' . $r . '.end_date') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Duration <span class="form-hint" style="display:inline">(optional)</span></label>
                <input type="text" name="schedules[{{ $r }}][duration]"
                       class="form-control-hm @error('schedules.' . $r . '.duration') is-invalid @enderror"
                       value="{{ $row['duration'] ?? '' }}" placeholder="e.g. 3 Months">
                @error('schedules.' . $r . '.duration') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Schedule Fee <span class="form-hint" style="display:inline">(optional)</span></label>
                <input type="number" name="schedules[{{ $r }}][fee]" min="0" step="1"
                       class="form-control-hm @error('schedules.' . $r . '.fee') is-invalid @enderror"
                       value="{{ $row['fee'] ?? '' }}" placeholder="25000">
                @error('schedules.' . $r . '.fee') <p class="form-error">{{ $message }}</p> @enderror
                <p class="form-hint">Numbers only — the site adds the ₹ and the commas.</p>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-3">
        <div class="d-flex align-items-center flex-wrap gap-4">
            <label class="switch switch--sm">
                <input type="hidden" name="schedules[{{ $r }}][show_fee]" value="0">
                <input type="checkbox" name="schedules[{{ $r }}][show_fee]" value="1" {{ $showFee ? 'checked' : '' }}>
                <span class="switch__track"></span>
                <span class="switch__label">Show Fee</span>
            </label>
            <label class="switch switch--sm">
                <input type="hidden" name="schedules[{{ $r }}][is_active]" value="0">
                <input type="checkbox" name="schedules[{{ $r }}][is_active]" value="1" {{ $active ? 'checked' : '' }}>
                <span class="switch__track"></span>
                <span class="switch__label">Active</span>
            </label>
        </div>
        <button type="button" class="btn-danger-soft" data-schedule-remove>Remove</button>
    </div>

    <hr class="faq-sep">
</div>
