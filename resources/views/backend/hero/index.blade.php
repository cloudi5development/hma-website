@extends('backend.template.layouts.template-base')

@section('title', 'Hero Section')
@section('page_title', 'Hero Section')
@section('page_sub', 'The banner at the top of the home page')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Hero Section</h1>
            <p class="page-head__sub">Editable copy, buttons and image · everything else in the banner stays fixed</p>
        </div>
        <div class="d-inline-flex gap-2">
            <a href="{{ route('frontend.index') }}#hero" target="_blank" rel="noopener" class="btn-ghost" aria-label="View on site">
                <span class="act-ico act-ico--view" aria-hidden="true"></span>
                View on site
            </a>
            <a href="{{ route('backend.hero.edit') }}" class="btn-brand" aria-label="Edit hero">
                <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                Edit Hero
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-hm alert-hm--success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="row g-3">
        {{-- Left column copy + buttons --}}
        <div class="col-12 col-lg-7">
            <div class="hm-card">
                <div class="hm-card__head"><h2 class="hm-card__title">Left Column</h2></div>
                <div class="hm-card__body">
                    <div class="hm-field">
                        <p class="hm-field__label">Badge Text</p>
                        <p class="hm-field__value">{{ $hero->badge_text }}</p>
                    </div>
                    <div class="hm-field">
                        <p class="hm-field__label">Main Title</p>
                        <p class="hm-field__value">{{ $hero->title }}</p>
                    </div>
                    <div class="hm-field">
                        <p class="hm-field__label">Description</p>
                        <p class="hm-field__value">{{ $hero->description }}</p>
                    </div>
                    <div class="hm-field">
                        <p class="hm-field__label">Primary Button</p>
                        <p class="hm-field__value">{{ $hero->btn1_text }} <span class="hm-field__value--muted">→ {{ $hero->btn1_url ?: '#courses' }}</span></p>
                    </div>
                    <div class="hm-field">
                        <p class="hm-field__label">Secondary Button</p>
                        <p class="hm-field__value">{{ $hero->btn2_text }} <span class="hm-field__value--muted">→ {{ $hero->btn2_url ?: 'Contact page' }}</span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right column image --}}
        <div class="col-12 col-lg-5">
            <div class="hm-card">
                <div class="hm-card__head"><h2 class="hm-card__title">Right Column Image</h2></div>
                <div class="hm-card__body">
                    @if ($hero->image)
                        <div class="hm-media"><img src="{{ $hero->image_url }}" alt="Hero image"></div>
                    @else
                        <div class="hm-media hm-media--empty">
                            <img src="{{ asset('backend/template/images/actions/product-img.svg') }}" alt="">
                            No image uploaded
                        </div>
                    @endif
                    <p class="form-hint">The floating rating cards and the scroll badge around this image are fixed and can't be edited.</p>
                </div>
            </div>
        </div>
    </div>

@endsection
