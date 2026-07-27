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

    <form method="POST" action="{{ route('backend.settings.seo.update') }}">
        @csrf @method('PUT')
        <div class="hm-card">
            <div class="hm-card__body" style="max-width:720px">
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
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label" for="seo_meta_keywords">Default Meta Keywords</label>
                    <input type="text" id="seo_meta_keywords" name="seo_meta_keywords" class="form-control-hm"
                           value="{{ old('seo_meta_keywords', $s('seo_meta_keywords')) }}" placeholder="training, courses, placement, …">
                </div>
                <div class="mt-3"><button type="submit" class="btn-brand">Save SEO Settings</button></div>
            </div>
        </div>
    </form>

@endsection
