@extends('backend.template.layouts.template-base')

@section('title', 'Social Media Settings')
@section('page_title', 'Settings')
@section('page_sub', 'Social Media')

@php $s = fn ($k, $d = null) => \App\Models\Setting::get($k, $d); @endphp

@section('content')

    <div class="page-head">
        <div><h1 class="page-head__title">Settings</h1><p class="page-head__sub">Social media profile links</p></div>
    </div>

    @include('backend.settings._nav')

    <form method="POST" action="{{ route('backend.settings.social.update') }}">
        @csrf @method('PUT')
        <div class="hm-card">
            <div class="hm-card__body" style="max-width:720px">
                <div class="row g-3">
                    @php
                        $socials = [
                            'social_instagram' => ['Instagram', 'https://www.instagram.com/hireminds_academy/'],
                            'social_facebook'  => ['Facebook',  'https://facebook.com/…'],
                            'social_youtube'   => ['YouTube',   'https://youtube.com/@…'],
                            'social_linkedin'  => ['LinkedIn',  'https://linkedin.com/company/…'],
                            'social_x'         => ['X (Twitter)', 'https://x.com/…'],
                            'social_whatsapp'  => ['WhatsApp',  'https://wa.me/917824094044'],
                        ];
                    @endphp
                    @foreach ($socials as $key => [$label, $ph])
                        <div class="col-12 col-md-6">
                            <div class="form-row">
                                <label class="form-label" for="{{ $key }}">{{ $label }}</label>
                                <input type="url" id="{{ $key }}" name="{{ $key }}" class="form-control-hm @error($key) is-invalid @enderror"
                                       value="{{ old($key, $s($key)) }}" placeholder="{{ $ph }}">
                                @error($key) <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2"><button type="submit" class="btn-brand">Save Social Links</button></div>
            </div>
        </div>
    </form>

@endsection
