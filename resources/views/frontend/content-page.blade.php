@extends('frontend.layouts.template-base')

@php
    // Same SEO precedence as the other pages: the record's own fields, then its
    // title, then the site-wide SEO Defaults the layout partials fall back to.
    $metaTitle       = $page->seo_title ?: $page->title . ' — Hire Minds Academy';
    $metaDescription = $page->seo_description;
@endphp

{{-- Block sections, not @section('title', $value): the inline form double-escapes
     an "&" (so "Terms & Conditions" would read "Terms &amp; Conditions") and
     leaks an output buffer when the value is null. See course-details for the
     same treatment. --}}
@section('title'){!! $metaTitle !!}@endsection
@section('og_title'){!! $page->title !!}@endsection

@if (filled($metaDescription))
    @section('meta_description'){!! $metaDescription !!}@endsection
    @section('og_description'){!! $metaDescription !!}@endsection
@endif

@if (filled($page->seo_keywords))
    @section('meta_keywords'){!! $page->seo_keywords !!}@endsection
@endif

@push('styles')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/content-page.css') }}?v={{ filemtime(public_path('assets/css/frontend/content-page.css')) }}">
@endpush

@section('content')

    <section class="hm-cp">
        <div class="container">

            {{-- A branded band rather than a photograph. The events banner shot
                 that used to sit here was a stage full of chairs, which reads as
                 an events page, not a legal one. --}}
            <header class="hm-cp__banner">
                <span class="hm-cp__banner-glow" aria-hidden="true"></span>

                <div class="hm-cp__banner-content">
                    <nav aria-label="Breadcrumb">
                        <ol class="hm-cp__crumbs">
                            <li><a href="{{ route('frontend.index') }}">Home</a></li>
                            <li class="hm-cp__crumb-sep" aria-hidden="true">&rsaquo;</li>
                            <li aria-current="page">{{ $page->title }}</li>
                        </ol>
                    </nav>

                    <h1 class="hm-cp__banner-title">{{ $page->title }}</h1>

                    {{-- Up top, not buried at the foot: on a legal page "when did
                         this last change" is one of the first things you look for. --}}
                    @if ($page->updated_at)
                        <p class="hm-cp__stamp">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>
                            </svg>
                            Last updated {{ $page->updated_at->format('j F Y') }}
                        </p>
                    @endif
                </div>
            </header>

            <article class="hm-cp__paper">
                {{-- Admin-authored HTML, exactly as the blog body is rendered.
                     Headings carry ids so a clause can be linked to directly
                     (/terms-conditions#3-events). --}}
                <div class="hm-cp__body">
                    {!! $body !!}
                </div>
            </article>

        </div>
    </section>

@endsection
