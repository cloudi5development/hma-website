@extends('backend.template.layouts.template-base')

@section('title', 'SEO Settings')
@section('page_title', 'Settings')
@section('page_sub', 'SEO Defaults')

@php $s = fn ($k, $d = null) => \App\Models\Setting::get($k, $d); @endphp

@section('content')

    <div class="page-head">
        <div><h1 class="page-head__title">Settings</h1><p class="page-head__sub">Default meta tags for pages without their own</p></div>
    </div>

    @include('backend.settings._nav')

    <form method="POST" action="{{ route('backend.settings.seo.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="hm-card">
            <div class="hm-card__body" style="max-width:780px">
                <h2 class="form-section__title" style="margin-bottom:12px">Basic SEO</h2>
                <div class="form-row">
                    <label class="form-label" for="seo_meta_title">Default Meta Title</label>
                    <input type="text" id="seo_meta_title" name="seo_meta_title" class="form-control-hm"
                           value="{{ old('seo_meta_title', $s('seo_meta_title')) }}" placeholder="Hire Minds Academy — Learn, Practice, Get Hired">
                </div>
                <div class="form-row">
                    <label class="form-label" for="seo_meta_description">Default Meta Description</label>
                    <textarea id="seo_meta_description" name="seo_meta_description" rows="3" class="form-control-hm"
                              placeholder="A short description used when a page has no meta description of its own…">{{ old('seo_meta_description', $s('seo_meta_description')) }}</textarea>
                </div>
                <div class="form-row">
                    <label class="form-label" for="seo_meta_keywords">Default Meta Keywords</label>
                    <input type="text" id="seo_meta_keywords" name="seo_meta_keywords" class="form-control-hm"
                           value="{{ old('seo_meta_keywords', $s('seo_meta_keywords')) }}" placeholder="training, courses, placement, …">
                </div>
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label" for="seo_meta_robots">Default Robots</label>
                    <input type="text" id="seo_meta_robots" name="seo_meta_robots" class="form-control-hm"
                           value="{{ old('seo_meta_robots', $s('seo_meta_robots', 'index, follow')) }}" placeholder="index, follow">
                </div>

                <h2 class="form-section__title" style="margin:24px 0 12px">Social</h2>
                <div class="form-row">
                    <label class="form-label" for="seo_default_og_image">Default Open Graph Image</label>
                    <input type="file" id="seo_default_og_image" name="seo_default_og_image" accept="image/*"
                           class="form-control-hm" style="height:auto;padding:9px 12px">
                    <p class="form-hint"><strong>1200 × 630 px</strong> (landscape) · WebP / PNG / JPG / AVIF · max 2 MB · used whenever a page has no share image of its own.</p>
                    @if ($s('seo_default_og_image'))
                        <img src="{{ \App\Models\Setting::image('seo_default_og_image', 'assets/images/branding/logo.png') }}"
                             alt="Current default Open Graph image"
                             style="display:block;max-width:220px;margin-top:10px;border-radius:10px;border:1px solid #e5e7eb">
                    @endif
                </div>

                <h2 class="form-section__title" style="margin:24px 0 12px">Verification</h2>
                <div class="form-row">
                    <label class="form-label" for="seo_google_site_verification">Google Site Verification</label>
                    <input type="text" id="seo_google_site_verification" name="seo_google_site_verification" class="form-control-hm"
                           value="{{ old('seo_google_site_verification', $s('seo_google_site_verification')) }}" placeholder="google-site-verification-code">
                </div>
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label" for="seo_bing_site_verification">Bing Site Verification</label>
                    <input type="text" id="seo_bing_site_verification" name="seo_bing_site_verification" class="form-control-hm"
                           value="{{ old('seo_bing_site_verification', $s('seo_bing_site_verification')) }}" placeholder="bing-site-verification-code">
                </div>

                <h2 class="form-section__title" style="margin:24px 0 12px">Analytics</h2>
                <div class="form-row">
                    <label class="form-label" for="seo_google_analytics_id">Google Analytics ID</label>
                    <input type="text" id="seo_google_analytics_id" name="seo_google_analytics_id" class="form-control-hm"
                           value="{{ old('seo_google_analytics_id', $s('seo_google_analytics_id')) }}" placeholder="G-XXXXXXXXXX">
                </div>
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label" for="seo_google_tag_manager_id">Google Tag Manager ID</label>
                    <input type="text" id="seo_google_tag_manager_id" name="seo_google_tag_manager_id" class="form-control-hm"
                           value="{{ old('seo_google_tag_manager_id', $s('seo_google_tag_manager_id')) }}" placeholder="GTM-XXXXXXX">
                </div>

                <div class="mt-4"><button type="submit" class="btn-brand">Save SEO Settings</button></div>
            </div>
        </div>
    </form>

@endsection
