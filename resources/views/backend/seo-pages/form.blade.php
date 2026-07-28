@extends('backend.template.layouts.template-base')

@php
    $editing = $seoPage->exists;

    // Blade helper: label + live character counter, with the range search engines
    // actually render so the counter means something.
    $count = fn ($id, $max) => '<span class="char-count" data-count-for="' . $id . '" data-max="' . $max . '">0/' . $max . '</span>';
@endphp

@section('title', $editing ? 'Edit Page SEO' : 'Add Page SEO')
@section('page_title', $editing ? 'Edit Page SEO' : 'Add Page SEO')
@section('page_sub', 'SEO')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.seo-pages.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Page SEO
        </a>
    </div>

    @include('backend.partials.flash')

    <form method="POST"
          action="{{ $editing ? route('backend.seo-pages.update', $seoPage) : route('backend.seo-pages.store') }}"
          enctype="multipart/form-data" novalidate id="seoForm">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row g-3">
            {{-- ============================ EDITOR ============================ --}}
            <div class="col-12 col-xl-8">

                {{-- Which page this record targets --}}
                <div class="hm-card mb-3">
                    <div class="hm-card__body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="form-row" style="margin-bottom:0">
                                    <label class="form-label" for="key">
                                        Page Key <span style="color:var(--danger)">*</span>
                                    </label>
                                    <input type="text" id="key" name="key" list="routeNames"
                                           class="form-control-hm @error('key') is-invalid @enderror"
                                           value="{{ old('key', $seoPage->key) }}" placeholder="e.g. about-us" required>
                                    <datalist id="routeNames">
                                        @foreach ($routeNames as $name)
                                            <option value="{{ $name }}"></option>
                                        @endforeach
                                    </datalist>
                                    <p class="form-hint">The frontend route this applies to. Pick one from the list.</p>
                                    @error('key') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-row" style="margin-bottom:0">
                                    <label class="form-label" for="label">
                                        Page Name <span style="color:var(--danger)">*</span>
                                    </label>
                                    <input type="text" id="label" name="label"
                                           class="form-control-hm @error('label') is-invalid @enderror"
                                           value="{{ old('label', $seoPage->label) }}" placeholder="e.g. About Us" required>
                                    <p class="form-hint">Only shown in this admin list.</p>
                                    @error('label') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-row mt-3" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <label class="switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $seoPage->is_active ?? true) ? 'checked' : '' }}>
                                <span class="switch__track"></span>
                                <span class="switch__label">Active</span>
                            </label>
                            <p class="form-hint">Switch off to let the page fall back to the meta in its own template.</p>
                        </div>
                    </div>
                </div>

                {{-- Meta / Social / Schema --}}
                <div class="hm-card mb-3">
                    <div class="hm-tabs" role="tablist">
                        <button type="button" class="hm-tabs__btn is-active" data-tab="meta" role="tab" aria-selected="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>
                            Meta
                        </button>
                        <button type="button" class="hm-tabs__btn" data-tab="social" role="tab" aria-selected="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
                            Social
                        </button>
                        <button type="button" class="hm-tabs__btn" data-tab="schema" role="tab" aria-selected="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/></svg>
                            Schema
                        </button>
                    </div>

                    <div class="hm-card__body">

                        {{-- ---------------------------- META ---------------------------- --}}
                        <div class="hm-tab-panel is-active" data-panel="meta">
                            <div class="form-row">
                                <label class="form-label" for="title">SEO Title {!! $count('title', 60) !!}</label>
                                <input type="text" id="title" name="title" maxlength="180"
                                       class="form-control-hm @error('title') is-invalid @enderror"
                                       value="{{ old('title', $seoPage->title) }}"
                                       placeholder="Shown as the browser tab and the search result heading">
                                <p class="form-hint">Google truncates around 60 characters. Lead with the page's main keyword.</p>
                                @error('title') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-row">
                                <label class="form-label" for="meta_description">Meta Description {!! $count('meta_description', 160) !!}</label>
                                <textarea id="meta_description" name="meta_description" rows="3" maxlength="320"
                                          class="form-control-hm @error('meta_description') is-invalid @enderror"
                                          placeholder="The summary shown under the title in search results…">{{ old('meta_description', $seoPage->meta_description) }}</textarea>
                                <p class="form-hint">Around 155 characters. Write it as an invitation to click, not a keyword list.</p>
                                @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-row">
                                <label class="form-label" for="meta_keywords">Meta Keywords</label>
                                <input type="text" id="meta_keywords" name="meta_keywords"
                                       class="form-control-hm @error('meta_keywords') is-invalid @enderror"
                                       value="{{ old('meta_keywords', $seoPage->meta_keywords) }}" placeholder="Comma separated">
                                @error('meta_keywords') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="form-row" style="margin-bottom:0">
                                        <label class="form-label" for="canonical_url">Canonical URL</label>
                                        <input type="url" id="canonical_url" name="canonical_url"
                                               class="form-control-hm @error('canonical_url') is-invalid @enderror"
                                               value="{{ old('canonical_url', $seoPage->canonical_url) }}"
                                               placeholder="https://hireminds.example/about-us">
                                        <p class="form-hint">Leave blank to use this page's own address.</p>
                                        @error('canonical_url') <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-row" style="margin-bottom:0">
                                        <label class="form-label" for="meta_robots">Robots</label>
                                        <select id="meta_robots" name="meta_robots" class="form-control-hm @error('meta_robots') is-invalid @enderror">
                                            @foreach (\App\Models\SeoPage::ROBOTS as $value => $label)
                                                <option value="{{ $value }}" {{ old('meta_robots', $seoPage->meta_robots ?? 'index, follow') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <p class="form-hint">“No Index” keeps the page out of search results.</p>
                                        @error('meta_robots') <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- --------------------------- SOCIAL --------------------------- --}}
                        <div class="hm-tab-panel" data-panel="social">
                            <div class="field-group">
                                <p class="field-group-title">Open Graph (Facebook / LinkedIn / WhatsApp)</p>

                                <div class="form-row">
                                    <label class="form-label" for="og_title">OG Title {!! $count('og_title', 60) !!}</label>
                                    <input type="text" id="og_title" name="og_title" maxlength="180"
                                           class="form-control-hm @error('og_title') is-invalid @enderror"
                                           value="{{ old('og_title', $seoPage->og_title) }}" placeholder="Falls back to the SEO title">
                                    @error('og_title') <p class="form-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="form-row">
                                    <label class="form-label" for="og_description">OG Description {!! $count('og_description', 200) !!}</label>
                                    <textarea id="og_description" name="og_description" rows="3" maxlength="320"
                                              class="form-control-hm @error('og_description') is-invalid @enderror"
                                              placeholder="Falls back to the meta description">{{ old('og_description', $seoPage->og_description) }}</textarea>
                                    @error('og_description') <p class="form-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="form-row" style="margin-bottom:0">
                                    <label class="form-label" for="og_image">OG Image <span class="form-hint" style="display:inline">(1200 × 630 recommended)</span></label>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <span class="tbl-logo" style="width:120px;height:63px">
                                            <img id="ogPreviewThumb" src="{{ $seoPage->og_image_url ?: '' }}" alt=""
                                                 style="{{ $seoPage->og_image_url ? '' : 'display:none' }}">
                                        </span>
                                        <input type="file" id="og_image" name="og_image" accept="image/*"
                                               class="form-control-hm @error('og_image') is-invalid @enderror" style="height:auto;padding:9px 12px;max-width:340px">
                                    </div>
                                    @error('og_image') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="field-group">
                                <p class="field-group-title">Twitter Card</p>

                                <div class="form-row">
                                    <label class="form-label" for="twitter_title">Twitter Title {!! $count('twitter_title', 60) !!}</label>
                                    <input type="text" id="twitter_title" name="twitter_title" maxlength="180"
                                           class="form-control-hm @error('twitter_title') is-invalid @enderror"
                                           value="{{ old('twitter_title', $seoPage->twitter_title) }}" placeholder="Falls back to the OG title">
                                    @error('twitter_title') <p class="form-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="form-row">
                                    <label class="form-label" for="twitter_description">Twitter Description {!! $count('twitter_description', 200) !!}</label>
                                    <textarea id="twitter_description" name="twitter_description" rows="3" maxlength="320"
                                              class="form-control-hm @error('twitter_description') is-invalid @enderror"
                                              placeholder="Falls back to the OG description">{{ old('twitter_description', $seoPage->twitter_description) }}</textarea>
                                    @error('twitter_description') <p class="form-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="form-row" style="margin-bottom:0">
                                    <label class="form-label" for="twitter_image">Twitter Image <span class="form-hint" style="display:inline">(1200 × 630 recommended)</span></label>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <span class="tbl-logo" style="width:120px;height:63px">
                                            <img id="twPreviewThumb" src="{{ $seoPage->twitter_image_url ?: '' }}" alt=""
                                                 style="{{ $seoPage->twitter_image_url ? '' : 'display:none' }}">
                                        </span>
                                        <input type="file" id="twitter_image" name="twitter_image" accept="image/*"
                                               class="form-control-hm @error('twitter_image') is-invalid @enderror" style="height:auto;padding:9px 12px;max-width:340px">
                                    </div>
                                    <p class="form-hint">Leave blank to reuse the OG image.</p>
                                    @error('twitter_image') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- --------------------------- SCHEMA --------------------------- --}}
                        <div class="hm-tab-panel" data-panel="schema">
                            <div class="alert-hm alert-hm--info">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-5M12 8h.01"/></svg>
                                Optional JSON-LD, printed into this page's head as structured data. Leave blank to use only the site defaults.
                            </div>

                            <div class="form-row">
                                <label class="form-label" for="schema_json">Schema JSON</label>
                                <textarea id="schema_json" name="schema_json" rows="6" spellcheck="false"
                                          class="form-control-hm @error('schema_json') is-invalid @enderror"
                                          style="font-family:ui-monospace,Consolas,monospace;font-size:12.5px"
                                          placeholder='{"@@type": "Organization", "name": "Hire Minds Academy"}'>{{ old('schema_json', $seoPage->schema_json) }}</textarea>
                                {{-- @@ escapes the at-sign: @context and @type are real Blade directives. --}}
                                <p class="form-hint">A JSON object. <code>@@context</code> is added for you.</p>
                                @error('schema_json') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-row">
                                <label class="form-label" for="breadcrumb_schema">Breadcrumb Schema</label>
                                <textarea id="breadcrumb_schema" name="breadcrumb_schema" rows="4" spellcheck="false"
                                          class="form-control-hm @error('breadcrumb_schema') is-invalid @enderror"
                                          style="font-family:ui-monospace,Consolas,monospace;font-size:12.5px"
                                          placeholder='[{"name": "Home", "url": "/"}, {"name": "About Us", "url": "/about-us"}]'>{{ old('breadcrumb_schema', $seoPage->breadcrumb_schema) }}</textarea>
                                <p class="form-hint">A list of steps. Positions are numbered automatically.</p>
                                @error('breadcrumb_schema') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-row" style="margin-bottom:0">
                                <label class="form-label" for="faq_schema">FAQ Schema <span class="form-hint" style="display:inline">(optional)</span></label>
                                <textarea id="faq_schema" name="faq_schema" rows="4" spellcheck="false"
                                          class="form-control-hm @error('faq_schema') is-invalid @enderror"
                                          style="font-family:ui-monospace,Consolas,monospace;font-size:12.5px"
                                          placeholder='[{"question": "Do you offer placement support?", "answer": "Yes — …"}]'>{{ old('faq_schema', $seoPage->faq_schema) }}</textarea>
                                <p class="form-hint">Can earn an expandable FAQ block in search results.</p>
                                @error('faq_schema') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ============================ PREVIEW ============================ --}}
            <div class="col-12 col-xl-4">
                <div class="seo-preview">
                    <div class="hm-card">
                        <div class="hm-tabs" role="tablist">
                            <button type="button" class="hm-tabs__btn is-active" data-preview="google" role="tab">Google</button>
                            <button type="button" class="hm-tabs__btn" data-preview="facebook" role="tab">Facebook</button>
                            <button type="button" class="hm-tabs__btn" data-preview="twitter" role="tab">Twitter</button>
                        </div>
                        <div class="hm-card__body">

                            {{-- Google result --}}
                            <div class="hm-tab-panel is-active" data-preview-panel="google">
                                <div class="seo-preview__card">
                                    <div class="seo-preview__url" id="pvUrl">{{ url('/') }}</div>
                                    <p class="seo-preview__title" id="pvTitle">SEO Title</p>
                                    <p class="seo-preview__desc" id="pvDesc">Meta description preview…</p>
                                </div>
                            </div>

                            {{-- Facebook / LinkedIn share card --}}
                            <div class="hm-tab-panel" data-preview-panel="facebook">
                                <div class="seo-preview__card seo-preview__social">
                                    <img class="seo-preview__media" id="pvOgImage"
                                         src="{{ $seoPage->og_image_url ?: asset('assets/images/branding/logo.png') }}" alt="">
                                    <div class="seo-preview__social-body">
                                        <div class="seo-preview__domain" id="pvDomain">{{ parse_url(url('/'), PHP_URL_HOST) }}</div>
                                        <p class="seo-preview__title" id="pvOgTitle">SEO Title</p>
                                        <p class="seo-preview__desc" id="pvOgDesc">Meta description preview…</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Twitter card --}}
                            <div class="hm-tab-panel" data-preview-panel="twitter">
                                <div class="seo-preview__card seo-preview__social">
                                    <img class="seo-preview__media" id="pvTwImage"
                                         src="{{ $seoPage->twitter_image_url ?: ($seoPage->og_image_url ?: asset('assets/images/branding/logo.png')) }}" alt="">
                                    <div class="seo-preview__social-body">
                                        <div class="seo-preview__domain">{{ parse_url(url('/'), PHP_URL_HOST) }}</div>
                                        <p class="seo-preview__title" id="pvTwTitle">SEO Title</p>
                                        <p class="seo-preview__desc" id="pvTwDesc">Meta description preview…</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Create SEO Record' }}
            </button>
            <a href="{{ route('backend.seo-pages.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('seoForm');
    if (!form) return;

    var BASE = @json(rtrim(url('/'), '/'));

    function $(id) { return document.getElementById(id); }
    function val(id) { var el = $(id); return el ? el.value.trim() : ''; }

    /* ---- Tab switching (used by both the editor and the preview) ---- */
    function wireTabs(btnAttr, panelAttr) {
        var buttons = form.querySelectorAll('[' + btnAttr + ']');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var name = btn.getAttribute(btnAttr);
                buttons.forEach(function (b) {
                    var on = b === btn;
                    b.classList.toggle('is-active', on);
                    b.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                form.querySelectorAll('[' + panelAttr + ']').forEach(function (p) {
                    p.classList.toggle('is-active', p.getAttribute(panelAttr) === name);
                });
            });
        });
    }
    wireTabs('data-tab', 'data-panel');
    wireTabs('data-preview', 'data-preview-panel');

    /* ---- Character counters ---- */
    form.querySelectorAll('[data-count-for]').forEach(function (badge) {
        var field = $(badge.getAttribute('data-count-for'));
        var max = parseInt(badge.getAttribute('data-max'), 10);
        if (!field) return;

        function refresh() {
            var n = field.value.length;
            badge.textContent = n + '/' + max;
            // Green once it is a usable length, red once search engines would cut it.
            badge.classList.toggle('is-over', n > max);
            badge.classList.toggle('is-good', n > 0 && n <= max && n >= Math.round(max * 0.5));
        }
        field.addEventListener('input', refresh);
        refresh();
    });

    /* ---- Live previews ---- */
    function paint(el, text, placeholder) {
        if (!el) return;
        var empty = !text;
        el.textContent = empty ? placeholder : text;
        el.classList.toggle('seo-preview__empty', empty);
    }

    function refreshPreview() {
        var title = val('title');
        var desc  = val('meta_description');
        var key   = val('key');

        paint($('pvUrl'), BASE + (key && key !== 'index' ? '/' + key : ''), BASE);
        paint($('pvTitle'), title, 'SEO Title');
        paint($('pvDesc'), desc, 'Meta description preview…');

        var ogTitle = val('og_title') || title;
        var ogDesc  = val('og_description') || desc;
        paint($('pvOgTitle'), ogTitle, 'SEO Title');
        paint($('pvOgDesc'), ogDesc, 'Meta description preview…');

        paint($('pvTwTitle'), val('twitter_title') || ogTitle, 'SEO Title');
        paint($('pvTwDesc'), val('twitter_description') || ogDesc, 'Meta description preview…');
    }

    ['key', 'title', 'meta_description', 'og_title', 'og_description', 'twitter_title', 'twitter_description']
        .forEach(function (id) {
            var el = $(id);
            if (el) el.addEventListener('input', refreshPreview);
        });
    refreshPreview();

    /* ---- Image previews (thumbnail + share card) ---- */
    function wireImage(inputId, thumbId, cardId, alsoCardId) {
        var input = $(inputId);
        if (!input) return;
        input.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            var url = URL.createObjectURL(this.files[0]);
            var thumb = $(thumbId);
            if (thumb) { thumb.src = url; thumb.style.display = 'block'; }
            if ($(cardId)) $(cardId).src = url;
            // A new OG image also stands in for Twitter until one is uploaded there.
            if (alsoCardId && !val('twitter_image') && $(alsoCardId) && !$(alsoCardId).dataset.own) {
                $(alsoCardId).src = url;
            }
        });
    }
    wireImage('og_image', 'ogPreviewThumb', 'pvOgImage', 'pvTwImage');
    wireImage('twitter_image', 'twPreviewThumb', 'pvTwImage');
    var twInput = $('twitter_image');
    if (twInput) twInput.addEventListener('change', function () {
        if ($('pvTwImage')) $('pvTwImage').dataset.own = '1';
    });

    /* ---- Reveal the tab holding a validation error ---- */
    var firstError = form.querySelector('.is-invalid');
    if (firstError) {
        var panel = firstError.closest('[data-panel]');
        if (panel) {
            var name = panel.getAttribute('data-panel');
            var btn = form.querySelector('[data-tab="' + name + '"]');
            if (btn) btn.click();
        }
    }
})();
</script>
@endpush
