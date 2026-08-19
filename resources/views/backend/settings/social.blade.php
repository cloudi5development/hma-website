@extends('backend.template.layouts.template-base')

@section('title', 'Social Media Settings')
@section('page_title', 'Settings')
@section('page_sub', 'Social Media')

@php
    $s = fn ($k, $d = null) => \App\Models\Setting::get($k, $d);

    // Placeholders only — the platform list itself comes from the model, so the
    // form and the footer icons can never drift apart.
    $placeholders = [
        'social_facebook'  => 'https://facebook.com/hiremindsacademy',
        'social_instagram' => 'https://www.instagram.com/hireminds_academy/',
        'social_linkedin'  => 'https://linkedin.com/company/hiremindsacademy',
        'social_youtube'   => 'https://youtube.com/@hiremindsacademy',
        'social_whatsapp'  => '917824094044 or https://wa.me/917824094044',
    ];
@endphp

@section('content')

    <div class="page-head">
        <div><h1 class="page-head__title">Settings</h1><p class="page-head__sub">Profile links behind the footer icons</p></div>
    </div>

    @include('backend.settings._nav')

    <form method="POST" action="{{ route('backend.settings.social.update') }}">
        @csrf @method('PUT')
        <div class="hm-card mb-3">
            <div class="hm-card__body">
                <p class="form-hint" style="margin:0 0 16px">
                    Paste the full profile URL. Leave a platform blank and its icon is hidden on the site
                    — no dead links. Icons open in a new tab.
                </p>

                <div class="row g-3">
                    @foreach (\App\Models\Setting::SOCIAL_PLATFORMS as $key => $meta)
                        <div class="col-12 col-md-6">
                            <div class="form-row" style="margin-bottom:0">
                                <label class="form-label" for="{{ $key }}">
                                    <i class="{{ $meta['icon'] }}" aria-hidden="true" style="width:16px;color:var(--purple)"></i>
                                    {{ $meta['label'] }}
                                </label>
                                {{-- WhatsApp also accepts a plain number, so it is not type=url --}}
                                <input type="{{ $key === 'social_whatsapp' ? 'text' : 'url' }}"
                                       id="{{ $key }}" name="{{ $key }}"
                                       class="form-control-hm @error($key) is-invalid @enderror"
                                       value="{{ old($key, $s($key)) }}"
                                       placeholder="{{ $placeholders[$key] ?? '' }}">
                                @error($key) <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Save Social Links
            </button>
            <a href="{{ route('frontend.index') }}" target="_blank" rel="noopener" class="btn-ghost">View on site</a>
        </div>
    </form>

@endsection
