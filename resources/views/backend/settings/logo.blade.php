@extends('backend.template.layouts.template-base')

@section('title', 'Logo Settings')
@section('page_title', 'Settings')
@section('page_sub', 'Logo & Favicon')

@php
    $logo    = \App\Models\Setting::get('site_logo');
    $favicon = \App\Models\Setting::get('site_favicon');
@endphp

@section('content')

    <div class="page-head">
        <div><h1 class="page-head__title">Settings</h1><p class="page-head__sub">The logo in the site header and footer, and the browser tab icon</p></div>
    </div>

    @include('backend.settings._nav')

    <form method="POST" action="{{ route('backend.settings.logo.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- ------------------------------ Logo ------------------------------ --}}
                <div class="form-section">
                    <h2 class="form-section__title">Website Logo</h2>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    {{-- Dark tile: most logos are light-on-transparent, and would vanish on white --}}
                    <span class="hm-media" style="width:200px;min-height:78px;display:flex;align-items:center;justify-content:center;background:#2C2640;padding:10px">
                        <img id="logoPreview" src="{{ \App\Models\Setting::image('site_logo', 'assets/images/branding/logo.png') }}"
                             alt="" style="max-width:100%;max-height:58px;object-fit:contain">
                    </span>
                    <div>
                        <input type="file" id="site_logo" name="site_logo" accept="image/*"
                               class="form-control-hm @error('site_logo') is-invalid @enderror" style="height:auto;padding:9px 12px">
                        <p class="form-hint">
                            WebP / PNG / JPG / SVG · max 2 MB · around 300 × 88 px.
                            A transparent PNG works best. Used in the site header, footer and enquiry emails.
                        </p>
                        @error('site_logo') <p class="form-error">{{ $message }}</p> @enderror
                        @if ($logo)
                            <label class="check-chip mt-1">
                                <input type="checkbox" name="remove_site_logo" value="1">
                                <span class="check-chip__box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span class="check-chip__label">Reset to the default logo</span>
                            </label>
                        @else
                            <p class="form-hint">Currently using the default logo bundled with the theme.</p>
                        @endif
                    </div>
                </div>

                {{-- ---------------------------- Favicon ---------------------------- --}}
                <div class="form-section">
                    <h2 class="form-section__title">Favicon</h2>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="tbl-logo" style="width:64px;height:64px">
                        <img id="faviconPreview" src="{{ \App\Models\Setting::image('site_favicon', 'assets/images/branding/favicon.png') }}"
                             alt="" style="max-width:40px;max-height:40px;object-fit:contain">
                    </span>
                    <div>
                        <input type="file" id="site_favicon" name="site_favicon" accept="image/*"
                               class="form-control-hm @error('site_favicon') is-invalid @enderror" style="height:auto;padding:9px 12px">
                        <p class="form-hint">
                            PNG / ICO / SVG · max 1 MB · a square image, 512 × 512 px or larger.
                            Shown in the browser tab and when the site is bookmarked.
                        </p>
                        @error('site_favicon') <p class="form-error">{{ $message }}</p> @enderror
                        @if ($favicon)
                            <label class="check-chip mt-1">
                                <input type="checkbox" name="remove_site_favicon" value="1">
                                <span class="check-chip__box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span class="check-chip__label">Reset to the default favicon</span>
                            </label>
                        @else
                            <p class="form-hint">Currently using the default favicon bundled with the theme.</p>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Save Logo Settings
            </button>
            <a href="{{ route('frontend.index') }}" target="_blank" rel="noopener" class="btn-ghost">View on site</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        // Live previews for both uploads
        [['site_logo', 'logoPreview'], ['site_favicon', 'faviconPreview']].forEach(function (pair) {
            var input = document.getElementById(pair[0]), img = document.getElementById(pair[1]);
            if (!input || !img) return;
            input.addEventListener('change', function () {
                if (this.files && this.files[0]) img.src = URL.createObjectURL(this.files[0]);
            });
        });
    </script>
@endpush
