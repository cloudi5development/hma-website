{{-- One branch office in the Contact settings repeater.
     @param int|string $i       row index (the literal __I__ inside the template)
     @param array      $branch  ['name', 'address', 'map'] --}}

<div class="branch-row" data-branch-row>
    <div class="branch-row__head">
        <span class="branch-row__num">{{ is_int($i) ? $i + 1 : '' }}</span>
        <button type="button" class="btn-danger-soft btn-icon" data-branch-remove aria-label="Remove branch">
            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
        </button>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Branch Name</label>
                <input type="text" name="branches[{{ $i }}][name]" class="form-control-hm"
                       value="{{ $branch['name'] ?? '' }}" placeholder="e.g. Chennai">
                <p class="form-hint">Used as the tab label on the Contact page.</p>
            </div>
        </div>
        <div class="col-12 col-md-8">
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label">Address</label>
                <textarea name="branches[{{ $i }}][address]" rows="2" class="form-control-hm"
                          data-branch-address placeholder="Street, area, city, PIN">{{ $branch['address'] ?? '' }}</textarea>
            </div>
        </div>
    </div>

    <div class="form-row mt-3" style="margin-bottom:0">
        <label class="form-label">Google Map Embed URL <span class="form-hint" style="display:inline">(optional)</span></label>
        <textarea name="branches[{{ $i }}][map]" rows="3" spellcheck="false"
                  class="form-control-hm" data-branch-map
                  style="font-family:ui-monospace,Consolas,monospace;font-size:12px"
                  placeholder='<iframe src="https://www.google.com/maps/embed?pb=…"></iframe>'>{{ $branch['map'] ?? '' }}</textarea>
        <p class="form-hint">
            Open Google Maps, find the location, choose <strong>Share → Embed a map</strong>, and paste
            the whole <code>&lt;iframe&gt;</code> code (or just its URL) here.
            Leave it blank to pin the map from the address above.
        </p>
    </div>

    <div class="branch-row__map" data-branch-mapwrap>
        <div class="branch-row__map-bar">
            <span class="form-label" style="margin:0">Map Preview</span>
            <a class="btn-ghost btn-sm" data-branch-open href="#" target="_blank" rel="noopener">
                Open in Maps
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M20 4l-8 8M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
            </a>
        </div>
        <iframe class="branch-row__frame" data-branch-frame src="" title="Map preview"
                loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
</div>
