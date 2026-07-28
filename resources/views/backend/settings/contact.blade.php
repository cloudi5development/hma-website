@extends('backend.template.layouts.template-base')

@section('title', 'Contact Settings')
@section('page_title', 'Settings')
@section('page_sub', 'Contact')

@php
    $s = fn ($k, $d = null) => \App\Models\Setting::get($k, $d);

    // Old input on a validation bounce, otherwise the saved branches.
    $branches = old('branches', \App\Models\Setting::branches());
@endphp

@section('content')

    <div class="page-head">
        <div><h1 class="page-head__title">Settings</h1><p class="page-head__sub">Contact details and branch offices shown across the site</p></div>
    </div>

    @include('backend.settings._nav')

    <form method="POST" action="{{ route('backend.settings.contact.update') }}" id="contactSettings">
        @csrf @method('PUT')

        {{-- ------------------------- Phone + email ------------------------- --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="contact_phone">Phone</label>
                            <input type="text" id="contact_phone" name="contact_phone" class="form-control-hm"
                                   value="{{ old('contact_phone', $s('contact_phone', '+91 78240 94044')) }}">
                            <p class="form-hint">Also used for the click-to-call link.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="contact_email">Email</label>
                            <input type="email" id="contact_email" name="contact_email"
                                   class="form-control-hm @error('contact_email') is-invalid @enderror"
                                   value="{{ old('contact_email', $s('contact_email', 'info@hiremindsacademy.com')) }}">
                            @error('contact_email') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------------------------- Branches ---------------------------- --}}
        <div class="hm-card mb-3">
            <div class="hm-card__head" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                <h2 class="hm-card__title">Branch Offices</h2>
                <button type="button" class="btn-soft" id="branchAdd">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add Branch
                </button>
            </div>
            <div class="hm-card__body">
                <p class="form-hint" style="margin:0 0 16px">
                    Each branch becomes a tab on the Contact page map and a line in the footer.
                    Add as many as you need — the page follows this list.
                </p>

                <div id="branchRepeater">
                    @foreach ($branches as $i => $branch)
                        @include('backend.settings._branch-row', ['i' => $i, 'branch' => $branch])
                    @endforeach
                </div>

                <p class="form-hint" id="branchEmpty" @if (count($branches)) style="display:none" @endif>
                    No branches yet — add one to show the map on the Contact page.
                </p>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Save Contact Settings
            </button>
            <a href="{{ route('frontend.contact-us') }}" target="_blank" rel="noopener" class="btn-ghost">View Contact page</a>
        </div>
    </form>

    {{-- Row template cloned by the Add Branch button --}}
    <template id="branchTemplate">
        @include('backend.settings._branch-row', ['i' => '__I__', 'branch' => ['name' => '', 'address' => '', 'map' => '']])
    </template>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var form     = document.getElementById('contactSettings');
    var repeater = document.getElementById('branchRepeater');
    var tpl      = document.getElementById('branchTemplate');
    var addBtn   = document.getElementById('branchAdd');
    var empty    = document.getElementById('branchEmpty');
    if (!form || !repeater || !tpl || !addBtn) return;

    var counter = repeater.querySelectorAll('[data-branch-row]').length;

    function rows() { return repeater.querySelectorAll('[data-branch-row]').length; }
    function refresh() { if (empty) empty.style.display = rows() ? 'none' : ''; }

    addBtn.addEventListener('click', function () {
        repeater.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__I__/g, 'n' + (counter++)));
        refresh();
        var last = repeater.lastElementChild;
        var field = last ? last.querySelector('input') : null;
        if (field) field.focus();
    });

    repeater.addEventListener('click', function (e) {
        var remove = e.target.closest('[data-branch-remove]');
        if (remove) {
            var row = remove.closest('[data-branch-row]');
            if (row) row.remove();
            refresh();
        }
    });

    // Typing an address or pasting an embed code refreshes that row's preview.
    repeater.addEventListener('input', function (e) {
        if (e.target.matches('[data-branch-map], [data-branch-address]')) {
            updatePreview(e.target.closest('[data-branch-row]'));
        }
    });

    /**
     * Mirrors Setting::normaliseMapUrl() — pull src out of a pasted <iframe>,
     * otherwise build a map from the address so the preview is never blank.
     */
    function updatePreview(row) {
        if (!row) return;
        var frame = row.querySelector('[data-branch-frame]');
        var wrap  = row.querySelector('[data-branch-mapwrap]');
        var link  = row.querySelector('[data-branch-open]');
        if (!frame || !wrap) return;

        var rawEl  = row.querySelector('[data-branch-map]');
        var addrEl = row.querySelector('[data-branch-address]');
        var raw     = rawEl ? rawEl.value : '';
        var address = addrEl ? addrEl.value : '';

        var match = raw.match(/src\s*=\s*["']([^"']+)["']/i);
        var url   = (match ? match[1] : raw).trim();

        if (!/^https?:\/\/([^\/]*\.)?google\.[a-z.]+\//i.test(url)) {
            url = address.trim()
                ? 'https://www.google.com/maps?q=' + encodeURIComponent(address) + '&output=embed'
                : '';
        }

        if (frame.src !== url) frame.src = url;
        wrap.style.display = url ? '' : 'none';
        if (link) link.href = url.replace(/[?&]output=embed/, '');
    }

    Array.prototype.forEach.call(repeater.querySelectorAll('[data-branch-row]'), updatePreview);
    refresh();
})();
</script>
@endpush
