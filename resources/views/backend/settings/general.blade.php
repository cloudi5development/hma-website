@extends('backend.template.layouts.template-base')

@section('title', 'General Settings')
@section('page_title', 'Settings')
@section('page_sub', 'General')

@php $s = fn ($k, $d = null) => \App\Models\Setting::get($k, $d); @endphp

@section('content')

    <div class="page-head">
        <div><h1 class="page-head__title">Settings</h1><p class="page-head__sub">General site information</p></div>
    </div>

    @include('backend.settings._nav')

    <form method="POST" action="{{ route('backend.settings.general.update') }}">
        @csrf @method('PUT')
        <div class="hm-card">
            <div class="hm-card__body" style="max-width:640px">
                <div class="form-row">
                    <label class="form-label" for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" class="form-control-hm @error('site_name') is-invalid @enderror"
                           value="{{ old('site_name', $s('site_name', config('app.name'))) }}" required>
                    @error('site_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-row">
                    <label class="form-label" for="site_tagline">Tagline</label>
                    <input type="text" id="site_tagline" name="site_tagline" class="form-control-hm"
                           value="{{ old('site_tagline', $s('site_tagline')) }}" placeholder="Learn, Practice, Get Hired">
                </div>
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label" for="footer_about">Footer About Text</label>
                    <textarea id="footer_about" name="footer_about" rows="3" class="form-control-hm"
                              placeholder="A short line about the academy…">{{ old('footer_about', $s('footer_about')) }}</textarea>
                </div>
                <div class="mt-3"><button type="submit" class="btn-brand">Save General Settings</button></div>
            </div>
        </div>
    </form>

@endsection
